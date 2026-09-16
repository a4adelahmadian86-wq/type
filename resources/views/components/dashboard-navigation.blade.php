@php
    $navigation = app(\App\Services\DashboardNavigationService::class)->forUser(auth()->user());
    $isAdminNavigation = auth()->user()?->isAdmin() === true;
@endphp

<aside class="farast-dashboard-nav" aria-label="ناوبری فضای کاری">
    <div class="farast-dashboard-nav__brand">
        <span class="farast-dashboard-nav__mark" aria-hidden="true"><i></i><i></i><i></i><i></i><b></b></span>
        <div><strong>فراست</strong><small>{{ $isAdminNavigation ? 'مرکز عملیات' : 'فضای کاری' }}</small></div>
    </div>

    <nav class="farast-dashboard-nav__groups">
        @foreach($navigation as $group)
            @php $hasActive = collect($group['items'])->contains(fn ($item) => !empty($item['active'])); @endphp
            <details class="farast-dashboard-nav__group" @if($hasActive) open @endif>
                <summary>
                    <span><i class="fa-solid {{ $group['icon'] }}" aria-hidden="true"></i>{{ $group['label'] }}</span>
                    <i class="fa-solid fa-chevron-down farast-dashboard-nav__chevron" aria-hidden="true"></i>
                </summary>
                <div class="farast-dashboard-nav__items">
                    @foreach($group['items'] as $item)
                        @if(!empty($item['href']) && empty($item['disabled']))
                            <a href="{{ $item['href'] }}" class="{{ !empty($item['active']) ? 'is-active' : '' }}">
                                <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @else
                            <span class="is-disabled" aria-disabled="true" title="این بخش هنوز در سامانه پیاده‌سازی نشده است">
                                <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                                <span>{{ $item['label'] }}</span>
                                <small>به‌زودی</small>
                            </span>
                        @endif
                    @endforeach
                </div>
            </details>
        @endforeach
    </nav>

    <div class="farast-dashboard-nav__footer">
        <a href="{{ route('home') }}"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i><span>بازگشت به سایت</span></a>
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i><span>خروج از حساب</span></button>
        </form>
    </div>
</aside>
