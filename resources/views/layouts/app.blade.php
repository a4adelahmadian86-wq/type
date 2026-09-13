<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'فراست' }}</title>

    @php
        $isEditor = request()->is('editor');
        $isAdmin = request()->is('admin*');
        $isAuthPage = request()->is('login*') || request()->is('register') || request()->is('forgot-password*');
        $headerAnnouncements = collect();
        $footerSocial = [];
        $farastCapabilities = null;

        if ($isEditor) {
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="/css/farast.css">
    <link rel="stylesheet" href="/css/ui-polish.css">
    <link rel="stylesheet" href="/css/site-premium.css">
    <link rel="stylesheet" href="/css/finance.css">

    @if($isEditor)
        <meta name="farast-capabilities" content='@json($farastCapabilities)'>
        <link rel="stylesheet" href="/css/word-editor.css">
        <link rel="stylesheet" href="/css/word-editor-overrides.css">
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
    <header class="top premium-header">
        <div class="header-inner">
            <a class="brand premium-brand" href="/">
                <span class="brand-mark"><i class="fa-solid fa-layer-group"></i></span>
                <span><b>FARAST</b><small>فراست | تبدیل هوشمند متن</small></span>
            </a>

            <nav class="main-nav">
                <a href="/"><i class="fa-solid fa-house"></i><span>خانه</span></a>
                <a href="/pricing"><i class="fa-solid fa-tags"></i><span>نرخنامه</span></a>
                <a href="/announcements">
                    <i class="fa-solid fa-bullhorn"></i><span>اعلانات</span>
                    @if($headerAnnouncements->isNotEmpty())
                        <b class="nav-count">{{ $headerAnnouncements->count() }}</b>
                    @endif
                </a>

                @auth
                    <a href="/dashboard"><i class="fa-solid fa-gauge-high"></i><span>داشبورد</span></a>
                    <a href="/wallet"><i class="fa-solid fa-wallet"></i><span>کیف پول</span></a>
                    <a href="/support"><i class="fa-solid fa-headset"></i><span>پشتیبانی</span></a>

                    @if(auth()->user()->isAdmin())
                        <a class="admin-link" href="/admin"><i class="fa-solid fa-user-shield"></i><span>مدیریت</span></a>
                    @endif

                    <form method="post" action="/logout" class="inline">
                        @csrf
                        <button class="header-logout" type="submit"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>خروج</span></button>
                    </form>
                @else
                    <a class="login-link" href="/login"><i class="fa-solid fa-right-to-bracket"></i><span>ورود</span></a>
                    <a class="header-cta" href="/editor"><i class="fa-solid fa-wand-magic-sparkles"></i><span>شروع تایپ</span></a>
                @endauth
            </nav>
        </div>
    </header>

    @if($headerAnnouncements->isNotEmpty() && request()->routeIs('home'))
        <div class="announcement-ticker">
            <div>
                <i class="fa-solid fa-bullhorn"></i>
                <b>{{ $headerAnnouncements->first()->title }}</b>
                <span>{{ \Illuminate\Support\Str::limit($headerAnnouncements->first()->body, 120) }}</span>
            </div>
            <a href="/announcements">مشاهده همه <i class="fa-solid fa-arrow-left"></i></a>
        </div>
    @endif
@endif

<main>@yield('content')</main>

@if(! $isEditor && ! $isAdmin)
    <footer class="site-footer" dir="rtl">
        <div class="footer-top">
            <div class="footer-about">
                <div class="footer-brand">
                    <span class="brand-mark"><i class="fa-solid fa-layer-group"></i></span>
                    <b>فراست</b>
                </div>
                <p>فراست، سامانه هوشمند تبدیل تصویر و PDF به متن قابل ویرایش است؛ با تمرکز بر دقت رونویسی، حفظ ساختار و تجربه‌ای نزدیک به Word.</p>

                <div class="footer-social {{ count($footerSocial) < 3 ? 'is-centered' : '' }}">
                    @foreach(array_slice($footerSocial, 0, 3) as $link)
                        <a href="{{ $link['url'] }}" aria-label="{{ $link['title'] ?? 'لینک ارتباطی' }}" target="_blank" rel="noopener noreferrer">
                            <i class="{{ $link['icon'] ?? 'fa-solid fa-link' }}"></i>
                        </a>
                    @endforeach

                    @if(count($footerSocial) > 3)
                        <a class="footer-more-social" href="/social" aria-label="همه نمادها">
                            <i class="fa-solid fa-ellipsis"></i><small>همه نمادها</small>
                        </a>
                    @endif
                </div>
            </div>

            <div class="footer-column">
                <h3>دسترسی سریع</h3>
                <a href="/">صفحه اصلی</a>
                <a href="/editor">شروع تایپ</a>
                <a href="/pricing">نرخنامه</a>
                <a href="/announcements">اعلانات</a>
                <a href="/login">ورود به حساب</a>
            </div>

            <div class="footer-column">
                <h3>خدمات فراست</h3>
                <a href="/editor">تبدیل تصویر به متن</a>
                <a href="/editor">تبدیل PDF به متن</a>
                <a href="/editor">ویرایشگر حرفه‌ای</a>
                <a href="/pricing">محاسبه هزینه</a>
                <a href="/support">پشتیبانی</a>
            </div>

            <div class="footer-column footer-contact">
                <h3>ارتباط با ما</h3>
                <p><i class="fa-solid fa-headset"></i> پشتیبانی آنلاین کاربران</p>
                <p><i class="fa-solid fa-envelope"></i> پاسخگویی از طریق حساب کاربری</p>
                <p><i class="fa-solid fa-clock"></i> ۷ روز هفته</p>
                <div class="footer-trust"><i class="fa-solid fa-shield-halved"></i><span>حریم خصوصی و نگهداری امن فایل‌ها</span></div>
            </div>
        </div>

        <div class="footer-bottom">
            <span>© {{ now()->year }} فراست — تمامی حقوق محفوظ است.</span>
            <div>
                <a href="/privacy">حریم خصوصی</a>
                <a href="/terms">قوانین استفاده</a>
                <a href="/refund-policy">شرایط بازگشت وجه</a>
                <a href="/social">نمادها</a>
            </div>
        </div>
    </footer>
@endif

<script src="/js/farast.js"></script>
@if($isEditor)
    <script src="/js/editor-polish.js"></script>
    <script src="/js/editor-drop-anywhere.js"></script>
    <script src="/js/editor-payment.js"></script>
@endif
@stack('scripts')
<script>document.documentElement.classList.add('js-ready');</script>
</body>
</html>
