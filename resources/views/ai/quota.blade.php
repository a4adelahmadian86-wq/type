@extends('layouts.app')
@section('content')
<div class="container user-dashboard farast-workspace" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-gauge-high"></i> هوش مصنوعی</span>
            <h1>مصرف و سهمیه AI</h1>
        </div>
    </header>
    <section class="cards dashboard-cards">
        <div><b>{{ $caps['unlimited'] ?? false ? 'نامحدود' : number_format($caps['daily_ai_requests'] ?? 0) }}</b><span>سقف روزانه</span></div>
        <div><b>{{ number_format($usedToday ?? 0) }}</b><span>مصرف امروز</span></div>
    </section>
</div>
@endsection
