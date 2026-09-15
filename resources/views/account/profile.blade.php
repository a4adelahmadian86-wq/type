@extends('layouts.app')
@section('content')
<div class="container farast-account" dir="rtl">
    <header class="account-hero">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-user-shield"></i> حساب کاربری</span>
            <h1>پروفایل و تنظیمات</h1>
            <p>اطلاعات حساب، امنیت، اعلان‌ها و حریم خصوصی خود را از یک فضای واحد مدیریت کنید.</p>
        </div>
        <div class="account-avatar" aria-hidden="true">{{ mb_substr($user->name ?: 'ف', 0, 1) }}</div>
    </header>

    @if(session('status')) <div class="account-alert success"><i class="fa-solid fa-circle-check"></i>{{ session('status') }}</div> @endif
    @if($errors->any()) <div class="account-alert error"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $errors->first() }}</span></div> @endif

    <div class="account-grid">
        <section class="panel account-card">
            <div class="panel-heading"><div><h2>اطلاعات شخصی</h2><p>شماره موبایل شناسه اصلی حساب است و از این فرم تغییر نمی‌کند.</p></div></div>
            <form method="POST" action="{{ route('account.profile.update') }}" class="account-form">
                @csrf @method('PUT')
                <label>نام و نام خانوادگی<input name="name" value="{{ old('name', $user->name) }}" required maxlength="120"></label>
                <label>ایمیل<input type="email" name="email" value="{{ old('email', $user->email) }}" dir="ltr" maxlength="255"></label>
                <label>شماره موبایل<input value="{{ $user->mobile }}" dir="ltr" disabled></label>
                <label class="full">درباره من<textarea name="bio" rows="4" maxlength="1000">{{ old('bio', $user->bio) }}</textarea></label>
                <button class="btn primary"><i class="fa-solid fa-floppy-disk"></i> ذخیره پروفایل</button>
            </form>
        </section>

        <section class="panel account-card">
            <div class="panel-heading"><div><h2>امنیت حساب</h2><p>برای تغییر رمز، رمز فعلی را دوباره تأیید کنید.</p></div></div>
            <form method="POST" action="{{ route('account.password.update') }}" class="account-form">
                @csrf @method('PUT')
                <label>رمز فعلی<input type="password" name="current_password" autocomplete="current-password" required></label>
                <label>رمز جدید<input type="password" name="password" autocomplete="new-password" minlength="8" required></label>
                <label>تکرار رمز جدید<input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required></label>
                <button class="btn outline"><i class="fa-solid fa-key"></i> تغییر رمز عبور</button>
            </form>
        </section>

        <section class="panel account-card">
            <div class="panel-heading"><div><h2>اعلان و حریم خصوصی</h2><p>انتخاب کنید چه به‌روزرسانی‌هایی دریافت کنید.</p></div></div>
            @php($notify=$user->notification_preferences ?: []) @php($privacy=$user->privacy_preferences ?: [])
            <form method="POST" action="{{ route('account.preferences.update') }}" class="preference-list">
                @csrf @method('PUT')
                @foreach(['email_updates'=>'به‌روزرسانی‌های حساب از طریق ایمیل','ticket_updates'=>'اعلان پاسخ و تغییر وضعیت پشتیبانی','product_updates'=>'خبرهای محصولات و خدمات جدید','analytics'=>'اجازه تحلیل فنی ناشناس برای بهبود سرویس'] as $key=>$label)
                    <label class="preference"><span>{{ $label }}</span><input type="hidden" name="{{ $key }}" value="0"><input type="checkbox" name="{{ $key }}" value="1" {{ ($key==='analytics' ? ($privacy[$key] ?? false) : ($notify[$key] ?? false)) ? 'checked' : '' }}><b></b></label>
                @endforeach
                <button class="btn outline"><i class="fa-solid fa-check"></i> ذخیره تنظیمات</button>
            </form>
        </section>

        <section class="panel account-card account-links">
            <div class="panel-heading"><div><h2>فضای مالی و خرید</h2><p>دسترسی سریع به داده‌های تجاری حساب.</p></div></div>
            <a href="{{ route('library') }}"><i class="fa-solid fa-folder-open"></i><span><b>کتابخانه من</b><small>فایل‌های خریداری‌شده</small></span><i class="fa-solid fa-arrow-left"></i></a>
            <a href="{{ route('wallet') }}"><i class="fa-solid fa-wallet"></i><span><b>کیف پول</b><small>{{ number_format((int) ($user->wallet->balance_rials ?? 0)) }} ریال</small></span><i class="fa-solid fa-arrow-left"></i></a>
            <a href="{{ route('support') }}"><i class="fa-solid fa-headset"></i><span><b>پشتیبانی</b><small>مشاهده و پیگیری درخواست‌ها</small></span><i class="fa-solid fa-arrow-left"></i></a>
        </section>
    </div>
</div>
@endsection
