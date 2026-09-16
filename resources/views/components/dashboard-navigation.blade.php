{{-- farast-nav-build: 2026-09-16-v4-restore --}}
@php
    $navigation = app(\App\Services\DashboardNavigationService::class)->forUser(auth()->user());
    $isAdminNavigation = auth()->user()?->isAdmin() === true;
@endphp

<aside class="farast-dashboard-nav" aria-label="ناوبری فضای کاری" data-dashboard-nav>
    <div class="farast-dashboard-nav__brand">
        <span class="farast-dashboard-nav__mark" aria-hidden="true"><i></i><i></i><i></i><i></i><b></b></span>
        <div class="farast-dashboard-nav__brand-copy"><strong>فراست</strong><small>{{ $isAdminNavigation ? 'مرکز عملیات' : 'فضای کاری' }}</small></div>
        <button type="button" class="farast-dashboard-nav__collapse" data-dashboard-nav-toggle aria-expanded="true" aria-controls="farast-dashboard-groups" aria-label="جمع کردن منوی داشبورد" title="جمع کردن منو">
            <i class="fa-solid fa-angles-right" aria-hidden="true"></i>
        </button>
    </div>

    <nav id="farast-dashboard-groups" class="farast-dashboard-nav__groups">
        @foreach($navigation as $group)
            @php $hasActive = collect($group['items'])->contains(fn ($item) => !empty($item['active'])); @endphp
            <details class="farast-dashboard-nav__group" @if($hasActive) open @endif>
                <summary>
                    <span><i class="fa-solid {{ $group['icon'] }}" aria-hidden="true"></i><b>{{ $group['label'] }}</b></span>
                    <i class="fa-solid fa-chevron-down farast-dashboard-nav__chevron" aria-hidden="true"></i>
                </summary>
                <div class="farast-dashboard-nav__items">
                    @foreach($group['items'] as $item)
                        @if(!empty($item['href']) && empty($item['disabled']))
                            <a href="{{ $item['href'] }}" class="{{ !empty($item['active']) ? 'is-active' : '' }}" @if(!empty($item['active'])) aria-current="page" @endif>
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
        <form method="post" action="{{ route('logout') }}">@csrf
            <button type="submit"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i><span>خروج</span></button>
        </form>
    </div>
</aside>

<script>
(function () {
    var nav = document.querySelector('[data-dashboard-nav]');
    if (!nav) return;
    var btn = nav.querySelector('[data-dashboard-nav-toggle]');
    var key = 'farast.dashboard.nav.collapsed';
    try {
        if (localStorage.getItem(key) === '1') {
            nav.classList.add('is-collapsed');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        }
    } catch (e) {}
    if (btn) {
        btn.addEventListener('click', function () {
            var collapsed = nav.classList.toggle('is-collapsed');
            btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            try { localStorage.setItem(key, collapsed ? '1' : '0'); } catch (e) {}
        });
    }
})();
</script>
