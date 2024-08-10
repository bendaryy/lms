<?php

namespace App\Http\Controllers;

use App\Traits\General;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use File;
use ZipArchive;

class VersionUpdateController extends Controller
{
    use General;

    protected $logger;

    public function __construct()
    {
        $this->logger = new Logger(storage_path('logs/update.log'));
    }

    public function versionUpdate(Request $request)
    {
        $data['title'] = __('Version Update');
        return view('zainiklab.installer.version-update', $data);
    }

    public function processUpdate(Request $request)
    {
        $request->validate([
            'email' => 'bail|required|email'
        ], [
            'email.required' => 'Customer email field is required',
            'email.email' => 'Customer email field must be a valid email'
        ]);

        // Removed the HTTP Post Request
        Artisan::call('migrate', [
            '--force' => true
        ]);

        $installedLogFile = storage_path('installed');
        if (file_exists($installedLogFile)) {
            $data = json_decode(file_get_contents($installedLogFile));
            if (!is_null($data) && isset($data->d)) {
                $data->u = date('ymdhis');
            } else {
                $data = [
                    'd' => base64_encode(get_domain_name(request()->fullUrl())),
                    'i' => date('ymdhis'),
                    'p' => '',  // Skipping purchase code
                    'u' => date('ymdhis'),
                ];
            }

            file_put_contents($installedLogFile, json_encode($data));
        }

        return redirect()->route('main.index');
    }

    public function versionFileUpdate(Request $request)
    {
        if (!auth()->user()->can('manage_version_update')) {
            abort('403');
        }

        $data['title'] = __('Version Update');
        $data['subNavVersionUpdateActiveClass'] = 'mm-active';

        // Removed the HTTP Post Request

        $path = storage_path('app/source-code.zip');

        if (file_exists($path)) {
            $data['uploadedFile'] = 'source-code.zip';
        } else {
            $data['uploadedFile'] = '';
        }

        try {
            $results = DB::select(DB::raw('select version()'));
            $data['mysql_version'] = $results[0]->{'version()'};
            $data['databaseType'] = 'MySQL Version';

            if (str_contains($data['mysql_version'], 'Maria')) {
                $data['databaseType'] = 'MariaDB Version';
            }
        } catch (\Exception $e) {
            $data['mysql_version'] = null;
        }

        return view('admin.version_update.create', $data);
    }

    public function versionFileUpdateStore(Request $request)
    {
        $request->validate([
            'update_file' => 'bail|required|mimes:zip'
        ]);

        set_time_limit(1200);
        $path = storage_path('app/source-code.zip');

        if (file_exists($path)) {
            File::delete($path);
        }

        try {
            $request->update_file->storeAs('/', 'source-code.zip', 'local');
        } catch (\Exception $e) {
            return response()->json(
                $e->getMessage(),
                500
            );
        }
    }

    public function executeUpdate()
    {
        set_time_limit(1200);
        $path = storage_path('app/source-code.zip');
        $demoPath = storage_path('app/updates');

        $response['success'] = false;
        $response['message'] = 'File not exist on storage!';

        $this->logger->log('Update Start', '==========');
        if (file_exists($path)) {
            $this->logger->log('File Found', 'Success');
            $zip = new ZipArchive;

            if (is_dir($demoPath)) {
                $this->logger->log('Updates directory', 'exist');
                $this->logger->log('Updates directory', 'deleting');
                File::deleteDirectory($demoPath);
                $this->logger->log('Updates directory', 'deleted');
            }

            $this->logger->log('Updates directory', 'creating');
            File::makeDirectory($demoPath, 0777, true, true);
            $this->logger->log('Updates directory', 'created');

            $this->logger->log('Zip', 'opening');
            $res = $zip->open($path);

            if ($res === true) {
                $this->logger->log('Zip', 'Open successfully');
                try {
                    $this->logger->log('Zip Extracting', 'Start');
                    $res = $zip->extractTo($demoPath);
                    $this->logger->log('Zip Extracting', 'END');
                    $this->logger->log('Get update note', 'START');
                    $versionFile = file_get_contents($demoPath . DIRECTORY_SEPARATOR . 'update_note.json');
                    $updateNote = json_decode($versionFile);
                    $this->logger->log('Get update note', 'END');
                    $this->logger->log('Get Build Version from update note', 'START');
                    $codeVersion = $updateNote->build_version;
                    $this->logger->log('Get Build Version from update note', 'END');
                    $this->logger->log('Get Root Path from update note', 'START');
                    $codeRootPath = $updateNote->root_path;
                    $this->logger->log('Get Root Path from update note', 'END');
                    $this->logger->log('Get current version', 'START');
                    $currentVersion = getCustomerCurrentBuildVersion();
                    $this->logger->log('Get current version', 'END');

                    if ($codeVersion > $currentVersion) {
                        $this->logger->log('Copy file', 'START');

                        $allMoveFilePath = (array) ($updateNote->code_path);
                        foreach ($allMoveFilePath as $filePath => $type) {
                            $this->logger->log('Copy file', 'Start ' . $demoPath . DIRECTORY_SEPARATOR . $codeRootPath . DIRECTORY_SEPARATOR . $filePath . ' to ' . base_path($filePath));
                            if ($type == 'file') {
                                File::copy($demoPath . DIRECTORY_SEPARATOR . $codeRootPath . DIRECTORY_SEPARATOR . $filePath, base_path($filePath));
                            } else {
                                File::copyDirectory($demoPath . DIRECTORY_SEPARATOR . $codeRootPath . DIRECTORY_SEPARATOR . $filePath, base_path($filePath));
                            }
                            $this->logger->log('Copy file', 'END');
                        }

                        $this->logger->log('Copy file', 'END');
                        if (property_exists($updateNote, 'delete')) {
                            $this->logger->log('Delete files', 'Start');
                            $allDeleteFilePath = (array) ($updateNote->delete);
                            foreach ($allDeleteFilePath as $filePath => $type) {
                                if ($type == 'file') {
                                    File::delete(base_path($filePath));
                                } else {
                                    File::deleteDirectory(base_path($filePath));
                                }
                            }
                            $this->logger->log('Delete files', 'END');
                        }

                        Artisan::call('migrate', [
                            '--force' => true
                        ]);

                        foreach ($updateNote->commands as $command) {
                            if (!Artisan::call($command)) {
                                break;
                            }
                        }

                        $installedLogFile = storage_path('installed');
                        if (file_exists($installedLogFile)) {
                            $data = json_decode(file_get_contents($installedLogFile));
                            if (!is_null($data) && isset($data->d)) {
                                $data->u = date('ymdhis');
                            } else {
                                $data = [
                                    'd' => base64_encode(get_domain_name(request()->fullUrl())),
                                    'i' => date('ymdhis'),
                                    'p' => '',  // Skipping purchase code
                                    'u' => date('ymdhis'),
                                ];
                            }

                            file_put_contents($installedLogFile, json_encode($data));
                        }
                        $response['success'] = true;
                        $response['message'] = 'Your application updated successfully.';
                    } else {
                        $response['message'] = 'You are using an invalid file!';
                    }
                } catch (\Exception $e) {
                    $response['message'] = $e->getMessage();
                }

                $zip->close();
            } else {
                $this->logger->log('Zip', 'Not open successfully');
            }
        } else {
            $this->logger->log('File Not Found', 'Failed');
        }

        return response()->json($response);
    }
}
