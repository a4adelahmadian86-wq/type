@extends('layouts.app')
@section('content')
<div class="container user-dashboard farast-workspace" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-user-gear"></i> تنظیمات</span>
            <h1>تنظیمات حساب</h1>
            <p class="dashboard-sub">اطلاعات هویتی و رمز عبور حساب شما.</p>
        </div>
    </header>

    @if(session('status'))
        <div class="finance-alert"><i class="fa-solid fa-circle-check"></i>{{ session('status') }}</div>
    @endif

    <div class="ws-grid two">
        <section class="panel">
            <div class="panel-heading"><div><h2>اطلاعات پایه</h2></div></div>
            <form method="post" action="{{ route('account.update') }}" class="account-form">
                @csrf
                @method('PUT')
                <label>نام
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="120">
                </label>
                <label>موبایل
                    <input type="text" value="{{ $user->mobile }}" disabled>
                    <small>شماره موبایل قابل تغییر از این صفحه نیست.</small>
                </label>
                <label>ایمیل
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" maxlength="190">
                </label>
                <label>نقش
                    <input type="text" value="{{ $user->role ?: 'member' }}" disabled>
                </label>
                <label>وضعیت تأیید
                    <input type="text" value="{{ $user->is_verified ? 'تأییدشده' : 'تأییدنشده' }}" disabled>
                </label>
                @error('name')<div class="form-error">{{ $message }}</div>@enderror
                @error('email')<div class="form-error">{{ $message }}</div>@enderror
                <button class="btn primary" type="submit">ذخیره تغییرات</button>
            </form>
        </section>

        <section class="panel">
            <div class="panel-heading"><div><h2>تغییر رمز عبور</h2></div></div>
            <form method="post" action="{{ route('account.password') }}" class="account-form">
                @csrf
                @method('PUT')
                <label>رمز فعلی
                    <input type="password" name="current_password" required autocomplete="current-password">
                </label>
                <label>رمز جدید
                    <input type="password" name="password" required autocomplete="new-password">
                </label>
                <label>تکرار رمز جدید
                    <input type="password" name="password_confirmation" required autocomplete="new-password">
                </label>
                @error('current_password')<div class="form-error">{{ $message }}</div>@enderror
                @error('password')<div class="form-error">{{ $message }}</div>@enderror
                <button class="btn primary" type="submit">به‌روزرسانی رمز</button>
            </form>

            <div class="panel-heading" style="margin-top:22px"><div><h2>قابلیت‌های فعال</h2><p>خواندنی — از سمت سامانه محاسبه می‌شود.</p></div></div>
            <div class="capability-chips">
                @foreach(['can_type'=>'تایپ','can_ai'=>'AI','can_voice'=>'صوتی','can_export_docx'=>'Word','can_export_pdf'=>'PDF','can_feedback'=>'بازخورد','can_support'=>'پشتیبانی'] as $key=>$label)
                    <span class="cap-chip {{ !empty($capabilities[$key]) ? 'on' : 'off' }}">
                        <i class="fa-solid {{ !empty($capabilities[$key]) ? 'fa-check' : 'fa-lock' }}"></i>{{ $label }}
                    </span>
                @endforeach
            </div>
        </section>
    </div>
</div>
@endsection
