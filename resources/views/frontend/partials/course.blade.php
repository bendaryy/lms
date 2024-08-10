<div class="card course-item {{ $course->status != STATUS_UPCOMING_APPROVED ? "" : "course-item-upcoming" }} border-0 radius-3 bg-white">
    <div class="course-img-wrap overflow-hidden">
        @if($course->status != STATUS_UPCOMING_APPROVED)
            @if (date('Y-m-d H:i:s', strtotime('-7 days')) >= $course->created_at)
                @if (in_array($course->id, $topCourse))
                    <span class="course-tag badge radius-3 font-12 font-medium position-absolute bg-orange">{{ __('Best Seller') }}</span>
                @endif
            @else
                <span class="course-tag badge radius-3 font-12 font-medium position-absolute bg-green">{{ __('New course') }}</span>
            @endif
            <?php
            $special = @$course->specialPromotionTagCourse->specialPromotionTag->name;
            ?>
            @if($special)
                <span class="course-tag badge radius-3 font-12 font-medium position-absolute bg-orange">
                    {{ __(@$special) }}
                </span>
            @endif
        @else
        <span class="course-tag badge radius-3 font-12 font-medium position-absolute bg-warning">{{ __('Upcoming') }}</span>
        @endif
        <a href="{{ route('course-details', $course->slug) }}"><img src="{{getImageFile($course->image_path)}}" alt="course" class="img-fluid"></a>
        <div class="course-item-hover-btns position-absolute">
                                    <span class="course-item-btn addToWishlist" data-course_id="{{ $course->id }}" data-route="{{ route('student.addToWishlist') }}"
                  title="Add to Wishlist">
                <i data-feather="heart"></i>
            </span>
            @if($course->status != STATUS_UPCOMING_APPROVED)
            <span class="course-item-btn addToCart" data-course_id="{{ $course->id }}" data-route="{{ route('student.addToCart') }}"
                  title="Add to Cart">
                <i data-feather="shopping-bag"></i>
            </span>
            @endif
        </div>
    </div>
    <div class="card-body">
        <h5 class="card-title course-title"><a href="{{ route('course-details', $course->slug) }}">{{ Str::limit($course->title, 40)}}</a></h5>
        <p class="card-text instructor-name-certificate font-medium font-12">
            <a href="{{ route('userProfile',$course->user->id) }}">{{ $course->$userRelation->name }}</a>
            @foreach($course->$userRelation->awards as $award) | {{ $award->name }} @endforeach</p>
        <div class="course-item-bottom">
            <div
                class="course-rating search-instructor-rating w-100 mb-15 d-inline-flex align-items-center">
                <span class="font-medium font-14 me-2">{{ $course->average_rating }}</span>
                <div class="star-ratings" style="width: 83.3125px;">
                    <div class="fill-ratings" style="width: {{ $course->average_rating * 20 }}%">
                        <span>★★★★★</span>
                    </div>
                    <div class="empty-ratings">
                        <span>★★★★★</span>
                    </div>
                </div>
                <span class="rating-count font-14 ms-2">({{ @$course->reviews->count() }})</span>
            </div>
            <div class="instructor-bottom-item font-14 font-semi-bold">

                @if($course->learner_accessibility == 'paid')
                    <?php
                    $startDate = date('d-m-Y H:i:s', strtotime(@$course->promotionCourse->promotion->start_date));
                    $endDate = date('d-m-Y H:i:s', strtotime(@$course->promotionCourse->promotion->end_date));
                    $percentage = @$course->promotionCourse->promotion->percentage;
                    $discount_price = number_format($course->price - (($course->price * $percentage) / 100), 2);
                    ?>

                    @if(now()->gt($startDate) && now()->lt($endDate))
                        <div class="instructor-bottom-item font-14 font-semi-bold">
                            {{ __('Price') }}:
                            <span class="color-hover">
                                @if($currencyPlacement ?? get_currency_placement() == 'after')
                                    {{ $discount_price }} {{ $currencySymbol ?? get_currency_symbol() }}
                                @else
                                    {{ $currencySymbol ?? get_currency_symbol() }} {{ $discount_price }}
                                @endif
                             </span>
                            <span class="text-decoration-line-through fw-normal font-14 color-gray ps-3">
                                @if($currencyPlacement ?? get_currency_placement() == 'after')
                                    {{ $course->price }} {{ $currencySymbol ?? get_currency_symbol() }}
                                @else
                                    {{ $currencySymbol ?? get_currency_symbol() }} {{ $course->price }}
                                @endif
                            </span>
                        </div>
                    @elseif ($course->price <= $course->old_price)
                        <div class="instructor-bottom-item font-14 font-semi-bold">
                            {{ __('Price') }}:
                            <span class="color-hover">
                                @if($currencyPlacement ?? get_currency_placement() == 'after')
                                    {{ $course->price }} {{ $currencySymbol ?? get_currency_symbol() }}
                                @else
                                    {{ $currencySymbol ?? get_currency_symbol() }} {{ $course->price }}
                                @endif
                            </span>
                            <span class="text-decoration-line-through fw-normal font-14 color-gray ps-3">
                                @if($currencyPlacement ?? get_currency_placement() == 'after')
                                    {{ $course->old_price }} {{ $currencySymbol ?? get_currency_symbol() }}
                                @else
                                    {{ $currencySymbol ?? get_currency_symbol() }} {{ $course->old_price }}
                                @endif
                            </span>
                        </div>
                    @else
                        <div class="instructor-bottom-item font-14 font-semi-bold">{{ __('Price') }}:
                            <span class="color-hover">
                                @if($currencyPlacement ?? get_currency_placement() == 'after')
                                    {{ $course->price }} {{ $currencySymbol ?? get_currency_symbol() }}
                                @else
                                    {{ $currencySymbol ?? get_currency_symbol() }} {{ $course->price }}
                                @endif
                            </span>
                        </div>
                    @endif
                    @elseif($course->learner_accessibility == 'free')
                    <div class="instructor-bottom-item font-14 font-semi-bold">
                        {{ __('Free') }}
                    </div>
                    @endif
                    @if($course->learner_accessibility != 'free' && get_option('cashback_system_mode', 0))
                    <div class="bg-light-purple d-flex font-12 justify-content-between mt-2 p-1 rounded">
                        <span class="color-para">
                        {{ __('Cashback') }}:
                        </span>
                        <span class="color-orange">
                            @if($currencyPlacement ?? get_currency_placement() == 'after')
                                {{calculateCashback($course->price) }} {{ $currencySymbol ?? get_currency_symbol() }}
                            @else
                                {{ $currencySymbol ?? get_currency_symbol() }} {{calculateCashback($course->price) }}
                            @endif
                        </span>
                    </div>
                    @endif
                </span>
            </div>
        </div>
    </div>
</div>
