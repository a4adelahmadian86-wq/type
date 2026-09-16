@extends('layouts.app')
@section('content')
<div class="container user-dashboard farast-workspace" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-bell-exclamation"></i> میز کار</span>
            <h1>موارد نیازمند اقدام</h1>
            <p class="dashboard-sub">فقط مواردی که واقعاً از داده حساب شما استخراج شده‌اند.</p>
        </div>
    </header>

    @if(count($needsAttention))
        <section class="cards dashboard-cards" aria-label="هشدارها">
            @foreach($needsAttention as $item)
                <div><b><i class="fa-solid {{ $item['icon'] }}"></i></b><span>{{ $item['title'] }} — {{ $item['body'] }}</span></div>
            @endforeach
        </section>
    @endif

    <section class="panel">
        <div class="panel-heading"><div><h2>اسناد نیازمند تسویه</h2><p>اسنادی که قیمت دارند و وضعیت paid نیستند.</p></div></div>
        @forelse($unpaid as $d)
            <article class="doc">
                <span><i class="fa-solid fa-file-invoice-dollar"></i><span>{{ $d->title }}</span></span>
                <span>{{ $d->status }}</span>
                <span>{{ number_format((int) $d->price_rials) }} ریال</span>
                <a href="{{ route('editor') }}">باز کردن</a>
            </article>
        @empty
            <div class="empty-state"><i class="fa-solid fa-check"></i><p>مورد نیازمند اقدام مالی ندارید.</p></div>
        @endforelse
    </section>
</div>
@endsection
