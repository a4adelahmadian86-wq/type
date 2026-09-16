@extends('layouts.app')
@section('content')
<div class="container user-dashboard farast-workspace" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-gauge-high"></i> هوش مصنوعی</span>
            <h1>مصرف و سهمیه AI</h1>
            <p class="dashboard-sub">اعداد از سقف حساب و شمارش واقعی درخواست‌های امروز گرفته شده‌اند.</p>
        </div>
    </header>

    <section class="cards dashboard-cards" aria-label="سهمیه">
        <div><b>{{ !empty($caps['unlimited']) ? 'نامحدود' : number_format((int) ($caps['daily_ai_requests'] ?? 0)) }}</b><span>سقف روزانه</span></div>
        <div><b>{{ number_format($usedToday) }}</b><span>مصرف امروز</span></div>
        <div><b>{{ number_format($usedTotal) }}</b><span>کل عملیات ثبت‌شده</span></div>
        <div><b>{{ !empty($caps['can_ai']) ? 'فعال' : 'غیرفعال' }}</b><span>وضعیت قابلیت AI</span></div>
    </section>

    <section class="panel">
        <div class="panel-heading"><div><h2>توضیح</h2></div></div>
        <p class="dashboard-sub" style="margin:0">اگر سقف روزانه پر شود، درخواست‌های جدید AI تا روز بعد مسدود می‌شوند. ادمین محدودیت روزانه ندارد.</p>
    </section>
</div>
@endsection
