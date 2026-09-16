<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><meta name="theme-color" content="#0b1734"><meta name="mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"><meta name="apple-mobile-web-app-title" content="فراست"><title>{{ $title ?? 'فراست' }}</title>
@php
    $isEditor = request()->is('editor');
    $isAdmin = request()->is('admin*');
    $isDashboard = auth()->check() && (
        request()->routeIs('dashboard')
        || request()->routeIs('wallet')
        || request()->routeIs('support')
        || request()->routeIs('library')
        || request()->routeIs('announcements')
        || request()->routeIs('checkout')
        || request()->routeIs('workspace.*')
        || request()->routeIs('documents.*')
        || request()->routeIs('account.*')
        || request()->routeIs('ai.*')
        || request()->routeIs('modules.show')
    );
    $isAuthPage = request()->is('login*') || request()->is('register') || request()->is('forgot-password*');
    $headerAnnouncements = collect();
    $footerSocial = [];
    $farastCapabilities = null;
    if ($isEditor && auth()->check()) {
        $farastCapabilities = app(\App\Services\CapabilityService::class)->forUser(auth()->user());
    }
    if (! $isEditor && ! $isAdmin && ! $isDashboard) {
        if (\Illuminate\Support\Facades\Schema::hasTable('announcements')) {
            $headerAnnouncements = \App\Models\Announcement::visible()->latest()->limit(5)->get();
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('site_settings')) {
            $footerRaw = \App\Models\SiteSetting::read('social_links', '[]');
            $decodedSocial = json_decode((string) $footerRaw, true);
            $footerSocial = is_array($decodedSocial) ? $decodedSocial : [];
        }
    }
@endphp
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="/css/farast.css">
@if($isDashboard)
<link rel="stylesheet" href="/css/dashboard-navigation.css">
<link rel="stylesheet" href="/css/workspace-pages.css">
@endif
@if($isEditor)<link rel="stylesheet" href="/css/editor.css">@endif
@if($isAdmin)<link rel="stylesheet" href="/css/admin.css">@endif
@if(request()->is('store*') || request()->is('library') || request()->is('checkout*'))
<link rel="stylesheet" href="/css/store.css">
@endif
@if(!$isEditor && !$isAdmin && !$isDashboard)
<link rel="stylesheet" href="/css/site.css">
@endif
@stack('styles')
@if($isEditor && !empty($openDocument))
<script>window.__openDoc = @json($openDocument);</script>
@endif
</head>
<body class="{{ $isEditor ? 'is-editor' : '' }} {{ $isAdmin ? 'is-admin' : '' }} {{ $isDashboard ? 'is-dashboard' : '' }} {{ $isAuthPage ? 'is-auth' : '' }}">
@if(!$isEditor && !$isAdmin && !$isDashboard)
<header class="site-header" dir="rtl">
    <div class="header-inner">
        <a class="brand" href="/">
            <span class="brand-mark farast-symbol"><i></i><i></i><i></i><i></i><b></b></span>
            <span class="brand-text">فراست</span>
        </a>
        <nav class="main-nav">
            <a href="/#farastStore"><i class="fa-solid fa-store"></i><span>فروشگاه</span></a>
            <a href="/editor"><i class="fa-solid fa-keyboard"></i><span>ویرایشگر</span></a>
            <a href="/pricing"><i class="fa-solid fa-tags"></i><span>قیمت</span></a>
            <a href="/support"><i class="fa-solid fa-headset"></i><span>پشتیبانی</span></a>
            @auth
                <a href="{{ route('dashboard') }}"><i class="fa-solid fa-gauge-high"></i><span>داشبورد</span></a>
                <form method="post" action="{{ route('logout') }}" class="header-logout">@csrf<button type="submit"><i class="fa-solid fa-right-from-bracket"></i><span>خروج</span></button></form>
            @else
                <a href="{{ route('login') }}"><i class="fa-solid fa-right-to-bracket"></i><span>ورود</span></a>
            @endauth
        </nav>
    </div>
</header>
@endif
<main id="app-main" class="{{ $isDashboard ? 'dashboard-shell' : '' }}">
@if($isDashboard && auth()->check())
<div class="dashboard-shell-inner">
    <x-dashboard-navigation />
    <div class="dashboard-shell-content">
@endif
@yield('content')
@if($isDashboard && auth()->check())
    </div>
</div>
@endif
</main>
@if(!$isEditor && !$isAdmin && !$isDashboard)
<footer class="site-footer" dir="rtl">
    <div class="footer-top">
        <div class="footer-about">
            <div class="footer-brand"><span class="brand-mark farast-symbol"><i></i><i></i><i></i><i></i><b></b></span><b>فراست</b></div>
            <p>فراست یک فضای یکپارچه برای فروش فایل‌های دیجیتال و ارائه خدمات تایپ، تبدیل، ویرایش و پردازش هوشمند است.</p>
        </div>
        <div class="footer-column">
            <h3>محصول و خدمات</h3>
            <a href="/#farastStore">فروش فایل</a>
            <a href="/editor">تایپ و تبدیل</a>
            <a href="/pricing">قیمت‌گذاری</a>
            <a href="/support">پشتیبانی</a>
        </div>
        <div class="footer-column">
            <h3>فضای کاربر</h3>
            <a href="/dashboard">داشبورد</a>
            <a href="/wallet">کیف پول</a>
            <a href="/announcements">اعلانات</a>
        </div>
    </div>
    <div class="footer-bottom">
        <span>© {{ now()->year }} فراست — تمامی حقوق محفوظ است.</span>
        <div>
            <a href="/privacy">حریم خصوصی</a>
            <a href="/terms">قوانین استفاده</a>
            <a href="/refund-policy">شرایط بازگشت وجه</a>
        </div>
    </div>
</footer>
@endif
<script src="/js/farast-tab-lock.js"></script>
<script src="/js/farast.js"></script>
<script src="/js/farast-sound.js"></script>
@if(!$isAdmin)<script src="/js/farast-app.js"></script>@endif
@if($isEditor)
<script src="/js/editor-polish.js"></script>
<script src="/js/editor-payment.js"></script>
<script src="/js/editor-redesign.js"></script>
<script src="/js/editor-redesign-guard.js"></script>
<script src="/js/editor-tools-patch.js"></script>
@endif
@stack('scripts')
@if($isEditor && !empty($openDocument))
<script src="/js/editor-open-document.js"></script>
@endif
<script>document.documentElement.classList.add('js-ready');</script>
</body>
</html>
