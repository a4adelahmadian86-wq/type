@extends('layouts.app')
@section('content')
<div class="container user-dashboard farast-workspace" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-user-gear"></i> حساب</span>
            <h1>تنظیمات حساب</h1>
        </div>
    </header>
    @if(session('status'))
        <div class="alert success">{{ session('status') }}</div>
    @endif
    <div class="ws-grid two">
        <section class="panel">
            <div class="panel-heading"><h2>اطلاعات پایه</h2></div>
            <form method="post" action="{{ route('account.update') }}" class="account-form">
                @csrf
                @method('PUT')
                <label>نام<input name="name" value="{{ old('name', $user->name) }}" required></label>
                @error('name')<span class="form-error">{{ $message }}</span>@enderror
                <label>ایمیل<input type="email" name="email" value="{{ old('email', $user->email) }}"></label>
                @error('email')<span class="form-error">{{ $message }}</span>@enderror
                <button class="btn primary" type="submit">ذخیره</button>
            </form>
        </section>
        <section class="panel">
            <div class="panel-heading"><h2>تغییر رمز</h2></div>
            <form method="post" action="{{ route('account.password') }}" class="account-form">
                @csrf
                @method('PUT')
                <label>رمز فعلی<input type="password" name="current_password" required></label>
                @error('current_password')<span class="form-error">{{ $message }}</span>@enderror
                <label>رمز جدید<input type="password" name="password" required></label>
                <label>تکرار رمز جدید<input type="password" name="password_confirmation" required></label>
                @error('password')<span class="form-error">{{ $message }}</span>@enderror
                <button class="btn" type="submit">به‌روزرسانی رمز</button>
            </form>
        </section>
    </div>
</div>
@endsection
