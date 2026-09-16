@extends('layouts.app')
@section('content')
<div class="container user-dashboard farast-workspace finance-page" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-wallet"></i> حساب مالی</span>
            <h1>کیف پول فراست</h1>
            <p class="dashboard-sub">موجودی، تراکنش‌ها و هزینه خروجی‌ها را شفاف و یکجا ببینید.</p>
        </div>
        <a class="btn" href="{{ route('dashboard') }}"><i class="fa-solid fa-gauge-high"></i> داشبورد</a>
    </header>

    @if(session('status'))
        <div class="finance-alert"><i class="fa-solid fa-circle-info"></i>{{ session('status') }}</div>
    @endif

    <div class="wallet-hero">
        <div>
            <span>موجودی قابل استفاده</span>
            <strong>{{ number_format((int) $wallet->balance_rials) }} <small>ریال</small></strong>
        </div>
        <div class="wallet-icon"><i class="fa-solid fa-wallet"></i></div>
    </div>

    <div class="wallet-grid">
        <section class="checkout-card">
            <div class="card-title">
                <span><i class="fa-solid fa-plus"></i></span>
                <div>
                    <h2>افزایش موجودی</h2>
                    <small>اتصال درگاه بانکی از تنظیمات مالی مدیر انجام می‌شود.</small>
                </div>
            </div>
            <div class="credit-note">
                <i class="fa-solid fa-plug-circle-xmark"></i>
                <p>در نسخه فعلی، درگاه بانکی واقعی هنوز به پذیرنده متصل نشده است؛ بنابراین سامانه هیچ پرداخت جعلی یا ثبت صوری انجام نمی‌دهد. پس از تنظیم درگاه، همین بخش برای افزایش موجودی استفاده خواهد شد.</p>
            </div>
            <button type="button" class="pay-button" disabled>
                <i class="fa-solid fa-lock"></i> درگاه آنلاین در انتظار تنظیم
            </button>
        </section>

        <section class="checkout-card">
            <div class="card-title">
                <span><i class="fa-solid fa-shield-halved"></i></span>
                <div>
                    <h2>اعتبار رایگان</h2>
                    <small>به شماره موبایل حساب وابسته است.</small>
                </div>
            </div>
            <div class="credit-note">
                <i class="fa-solid fa-gift"></i>
                <p>اعتبار آزمایشی هر هفته فقط یک‌بار برای هر شماره موبایل قابل مصرف است و هنگام پرداخت نهایی مصرف می‌شود؛ صرفاً تحلیل یا بازبینی باعث سوختن اعتبار نمی‌شود.</p>
            </div>
        </section>
    </div>

    <section class="checkout-card transactions">
        <div class="card-title">
            <span><i class="fa-solid fa-list"></i></span>
            <div>
                <h2>تراکنش‌های اخیر</h2>
                <small>آخرین ۳۰ رویداد مالی واقعی حساب شما</small>
            </div>
        </div>
        <div class="transaction-list">
            @forelse($transactions as $tx)
                <div class="transaction-row">
                    <span class="tx-icon {{ $tx->type === 'credit' ? 'credit' : 'debit' }}">
                        <i class="fa-solid {{ $tx->type === 'credit' ? 'fa-arrow-down' : 'fa-arrow-up' }}"></i>
                    </span>
                    <div>
                        <b>{{ $tx->description ?: ($tx->type === 'credit' ? 'افزایش موجودی' : 'پرداخت') }}</b>
                        <small>{{ $tx->created_at?->format('Y/m/d H:i') }}</small>
                    </div>
                    <strong class="{{ $tx->type === 'credit' ? 'credit-text' : 'debit-text' }}">
                        {{ $tx->type === 'credit' ? '+' : '-' }}{{ number_format((int) $tx->amount_rials) }} ریال
                    </strong>
                </div>
            @empty
                <div class="empty-finance">هنوز تراکنش مالی ثبت نشده است.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
