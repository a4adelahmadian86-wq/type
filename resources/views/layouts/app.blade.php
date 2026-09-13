<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b1734">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="فراست">
    <title>{{ $title ?? 'فراست' }}</title>

    @php
        $isEditor = request()->is('editor');
        $isAdmin = request()->is('admin*');
        $isAuthPage = request()->is('login*') || request()->is('register') || request()->is('forgot-password*');
        $headerAnnouncements = collect();
        $footerSocial = [];
        $farastCapabilities = null;

        if ($isEditor && auth()->check()) {
            $farastCapabilities = app(\App\Services\CapabilityService::class)->forUser(auth()->user());
        }

        if (! $isEditor && ! $isAdmin) {
            if (\Illuminate\Support\Facades\Schema::hasTable('announcements')) {
                $headerAnnouncements = \App\Models\Announcement::visible()->latest()->limit(5)->get();
            }

            if (\Illuminate\Support\Facades\Schema::hasTable('site_settings')) {
                $footerRaw = \App\Models\SiteSetting::read('social_links', '[]');
                $decodedSocial = json_decode((string) $footerRaw, true);
                if (is_array($decodedSocial)) {
                    $footerSocial = array_values(array_filter($decodedSocial, static function ($item) {
                        return is_array($item) && filter_var($item['url'] ?? '', FILTER_VALIDATE_URL);
                    }));
                }
            }
        }
    @endphp

    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="/css/farast.css">
    <link rel="stylesheet" href="/css/ui-polish.css">
    <link rel="stylesheet" href="/css/site-premium.css">
    <link rel="stylesheet" href="/css/farast-app.css">
    <link rel="stylesheet" href="/css/finance.css">

    @if($isEditor)
        <meta name="farast-capabilities" content='@json($farastCapabilities)'>
        <link rel="stylesheet" href="/css/voice.css">
        <link rel="stylesheet" href="/css/word-editor.css">
        <link rel="stylesheet" href="/css/word-editor-overrides.css">
        <link rel="stylesheet" href="/css/editor-pro.css">
        <link rel="stylesheet" href="/css/editor-workspace.css">
    @endif

    @if($isAdmin)
        <link rel="stylesheet" href="/css/admin.css">
    @endif

    @if($isAuthPage)
        <link rel="stylesheet" href="/css/auth.css">
    @endif
</head>
<body class="{{ $isEditor ? 'editor-page-body' : '' }} {{ $isAuthPage ? 'auth-page' : '' }}">

@if(! $isEditor && ! $isAdmin)
    <header class="top premium-header" data-app-header>
        <div class="header-inner">
            <a class="brand premium-brand" href="/" aria-label="فراست">
                <span class="brand-mark farast-symbol" aria-hidden="true"><i></i><i></i><i></i><i></i><b></b></span>
                <span><b>FARAST</b><small>فراست | فروش فایل و خدمات هوشمند</small></span>
            </a>

            <div class="header-search" role="search">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input id="farastGlobalSearch" type="search" placeholder="جستجوی فایل، خدمت یا موضوع..." autocomplete="off" aria-label="جستجو">
                <button type="button" id="farastVoiceSearch" aria-label="جستجوی صوتی" title="جستجوی صوتی"><i class="fa-solid fa-microphone"></i></button>
            </div>

            <nav class="main-nav" aria-label="ناوبری اصلی">
                <a href="/"><i class="fa-solid fa-house"></i><span>خانه</span></a>
                <a href="/#farastStore"><i class="fa-solid fa-store"></i><span>فروشگاه</span></a>
                <a href="/editor"><i class="fa-solid fa-pen-ruler"></i><span>تایپ و خدمات</span></a>
                <a href="/pricing"><i class="fa-solid fa-tags"></i><span>قیمت</span></a>
                @auth
                    <a href="/dashboard"><i class="fa-solid fa-table-cells-large"></i><span>فضای من</span></a>
                    <a href="/support"><i class="fa-solid fa-headset"></i><span>پشتیبانی</span></a>
                    @if(auth()->user()->isAdmin())
                        <a class="admin-link" href="/admin"><i class="fa-solid fa-user-shield"></i><span>مدیریت</span></a>
                    @endif
                @else
                    <a class="login-link" href="/login"><i class="fa-solid fa-right-to-bracket"></i><span>ورود</span></a>
                @endauth
            </nav>

            <div class="header-actions">
                <button type="button" class="header-icon-button" id="farastCartButton" aria-label="سبد خرید" title="سبد خرید"><i class="fa-solid fa-bag-shopping"></i><b id="farastCartCount">0</b></button>
                <a class="header-cta" href="/editor"><i class="fa-solid fa-bolt"></i><span>شروع کار</span></a>
            </div>
        </div>
    </header>

    @if($headerAnnouncements->isNotEmpty() && request()->routeIs('home'))
        <div class="announcement-ticker">
            <div><i class="fa-solid fa-bullhorn"></i><b>{{ $headerAnnouncements->first()->title }}</b><span>{{ \Illuminate\Support\Str::limit($headerAnnouncements->first()->body, 120) }}</span></div>
            <a href="/announcements">مشاهده همه <i class="fa-solid fa-arrow-left"></i></a>
        </div>
    @endif
@endif

<main id="app-main">@yield('content')</main>

@if(! $isEditor && ! $isAdmin)
    <aside class="farast-cart-drawer" id="farastCartDrawer" aria-hidden="true" aria-label="سبد خرید">
        <div class="farast-cart-head"><div><small>سبد خرید</small><strong>انتخاب‌های شما</strong></div><button type="button" data-cart-close aria-label="بستن"><i class="fa-solid fa-xmark"></i></button></div>
        <div class="farast-cart-body" id="farastCartBody"><div class="farast-cart-empty"><i class="fa-solid fa-bag-shopping"></i><strong>سبد خرید خالی است</strong><span>محصولات دیجیتال را از ویترین انتخاب کنید.</span></div></div>
        <div class="farast-cart-foot"><span>جمع</span><strong id="farastCartTotal">۰ تومان</strong><button type="button" id="farastCartCheckout" disabled>ادامه پرداخت</button></div>
    </aside>
    <div class="farast-cart-backdrop" id="farastCartBackdrop" hidden></div>

    <div class="farast-connectivity" id="farastConnectivity" aria-live="polite" hidden>
        <div class="farast-connectivity-card" role="status">
            <div class="farast-connectivity-copy"><strong id="farastConnectivityTitle">اتصال اینترنت در دسترس نیست</strong><span id="farastConnectivityMessage">بخش‌های ذخیره‌شده در دسترس می‌مانند؛ اتصال دوباره به‌صورت خودکار بررسی می‌شود.</span></div>
            <div class="farast-connectivity-action"><span class="farast-spinner" id="farastConnectivitySpinner" role="progressbar" aria-label="در حال بررسی اتصال"></span><button type="button" id="farastConnectivityRetry" aria-label="تلاش دوباره" title="تلاش دوباره"><i class="fa-solid fa-rotate-right"></i></button></div>
        </div>
    </div>

    <button type="button" id="farastSoundToggle" class="farast-sound-toggle" aria-label="فعال یا غیرفعال کردن صدای رابط" aria-pressed="false" title="کنترل صدای رابط"><i class="fa-solid fa-volume-high"></i></button>
@endif

@if(! $isEditor && ! $isAdmin)
    <footer class="site-footer" dir="rtl">
        <div class="footer-top">
            <div class="footer-about"><div class="footer-brand"><span class="brand-mark farast-symbol" aria-hidden="true"><i></i><i></i><i></i><i></i><b></b></span><b>فراست</b></div><p>فراست یک فضای یکپارچه برای فروش فایل‌های دیجیتال و ارائه خدمات تایپ، تبدیل، ویرایش و پردازش هوشمند است؛ یک محصول واحد، نه چند صفحه جدا از هم.</p><div class="footer-social {{ count($footerSocial) < 3 ? 'is-centered' : '' }}">@foreach(array_slice($footerSocial, 0, 3) as $link)<a href="{{ $link['url'] }}" aria-label="{{ $link['title'] ?? 'لینک ارتباطی' }}" target="_blank" rel="noopener noreferrer"><i class="{{ $link['icon'] ?? 'fa-solid fa-link' }}"></i></a>@endforeach @if(count($footerSocial) > 3)<a class="footer-more-social" href="/social" aria-label="همه نمادها"><i class="fa-solid fa-ellipsis"></i><small>همه نمادها</small></a>@endif</div></div>
            <div class="footer-column"><h3>محصول و خدمات</h3><a href="/#farastStore">فروش فایل</a><a href="/editor">تایپ و تبدیل</a><a href="/pricing">قیمت‌گذاری</a><a href="/support">پشتیبانی</a></div>
            <div class="footer-column"><h3>فضای کاربر</h3><a href="/dashboard">داشبورد</a><a href="/wallet">کیف پول</a><a href="/announcements">اعلانات</a><a href="/login">ورود به حساب</a></div>
            <div class="footer-column footer-contact"><h3>ارتباط و اعتماد</h3><p><i class="fa-solid fa-headset"></i> پشتیبانی آنلاین</p><p><i class="fa-solid fa-shield-halved"></i> حریم خصوصی و امنیت</p><p><i class="fa-solid fa-wifi"></i> پایش اتصال سرویس</p><div class="footer-trust"><i class="fa-solid fa-circle-check"></i><span>تجربه یکپارچه و اپلیکیشن‌محور</span></div></div>
        </div>
        <div class="footer-bottom"><span>© {{ now()->year }} فراست — تمامی حقوق محفوظ است.</span><div><a href="/privacy">حریم خصوصی</a><a href="/terms">قوانین استفاده</a><a href="/refund-policy">شرایط بازگشت وجه</a><a href="/social">نمادها</a></div></div>
    </footer>
@endif

<script src="/js/farast-tab-lock.js"></script>
<script src="/js/farast.js"></script>
<script src="/js/farast-sound.js"></script>
@if(! $isAdmin)
    <script src="/js/farast-app.js"></script>
@endif
@if($isEditor)
    <script src="/js/editor-polish.js"></script>
    <script src="/js/editor-drop-anywhere.js"></script>
    <script src="/js/editor-payment.js"></script>
    <script src="/js/editor-pro.js"></script>
    <script src="/js/editor-workspace.js"></script>
    <script src="/js/editor-word-extended.js"></script>
@endif
@stack('scripts')
@if($isEditor)
    <script src="/js/farast-voice.js"></script>
@endif
<script>document.documentElement.classList.add('js-ready');</script>
</body>
</html>
