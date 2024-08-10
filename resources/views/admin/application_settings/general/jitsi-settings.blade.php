@extends('layouts.admin')

@section('content')
    <!-- Page content area start -->
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="breadcrumb__content">
                        <div class="breadcrumb__content__left">
                            <div class="breadcrumb__title">
                                <h2>{{ __('Application Settings') }}</h2>
                            </div>
                        </div>
                        <div class="breadcrumb__content__right">
                            <nav aria-label="breadcrumb">
                                <ul class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">{{__('Dashboard')}}</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">{{ __(@$title) }}</li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-3 col-md-4">
                    @include('admin.application_settings.sidebar')
                </div>
                <div class="col-lg-9 col-md-8">
                    <div class="email-inbox__area bg-style">
                        <div class="item-top mb-30"><h2>{{ __(@$title) }}</h2></div>
                        <form action="{{route('settings.jitsi-settings.update')}}" method="post" class="form-horizontal">
                            @csrf
                            <div class="row input__group mb-25">
                                <label class="col-lg-3">{{ __('Jitsi Status') }} <span class="text-danger">*</span></label>
                                <div class="col-lg-9">
                                    <select name="jitsi_status" id="" class="form-control" required>
                                        <option value="">{{ __('Select Option') }}</option>
                                        <option value="1" @if(get_option('jitsi_status') == 1) selected @endif>{{ __('Active') }}</option>
                                        <option value="0" @if(get_option('jitsi_status') != 1) selected @endif>{{ __('Disable') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row input__group mb-25">
                                <label class="col-lg-3">{{ __('Jitsi Server Base URL') }}<span class="text-danger">*</span></label>
                                <div class="col-lg-9">
                                    <input type="text" name="jitsi_server_base_url" value="{{ get_option('jitsi_server_base_url') }}" class="form-control" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="input__group general-settings-btn">
                                        <button type="submit" class="btn btn-blue float-right">{{__('Update')}}</button>
                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Page content area end -->
@endsection
