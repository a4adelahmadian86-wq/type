@extends('layouts.app')
@section('content')
<div class="container user-dashboard farast-workspace" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-clock-rotate-left"></i> میز کار</span>
            <h1>فعالیت‌های اخیر</h1>
            <p class="dashboard-sub">اسناد، پردازش‌های AI و سفارش‌های مالی مربوط به حساب شما.</p>
        </div>
        <a class="btn primary" href="{{ route('dashboard') }}"><i class="fa-solid fa-gauge-high"></i> نمای کلی</a>
    </header>

    <div class="ws-grid">
        <section class="panel">
            <div class="panel-heading"><div><h2>اسناد</h2><p>آخرین ۲۰ سند</p></div></div>
            @forelse($documents as $d)
                <article class="doc">
                    <span><i class="fa-regular fa-file-lines"></i><span>{{ $d->title }}</span></span>
                    <span>{{ $d->status }}</span>
                    <span>{{ $d->updated_at?->diffForHumans() }}</span>
                    <a href="{{ route('editor', ['document' => $d->id]) }}"><i class="fa-solid fa-arrow-left"></i> باز کردن</a>
                </article>
            @empty
                <div class="empty-state"><i class="fa-regular fa-folder-open"></i><p>سندی ثبت نشده است.</p></div>
            @endforelse
        </section>

        <section class="panel">
            <div class="panel-heading"><div><h2>عملیات AI</h2><p>آخرین ۱۵ درخواست</p></div></div>
            @forelse($ai as $row)
                <article class="doc">
                    <span><i class="fa-solid fa-wand-magic-sparkles"></i><span>{{ $row->operation ?: 'عملیات' }}</span></span>
                    <span>{{ $row->status }}</span>
                    <span>{{ $row->latency_ms ? $row->latency_ms.'ms' : '—' }}</span>
                    <span>{{ $row->created_at?->diffForHumans() }}</span>
                </article>
            @empty
                <div class="empty-state"><i class="fa-solid fa-robot"></i><p>هنوز عملیات AI ثبت نشده است.</p></div>
            @endforelse
        </section>

        <section class="panel">
            <div class="panel-heading"><div><h2>سفارش‌ها</h2><p>آخرین ۱۰ سفارش</p></div></div>
            @forelse($orders as $o)
                <article class="doc">
                    <span><i class="fa-solid fa-receipt"></i><span>#{{ $o->id }}</span></span>
                    <span>{{ $o->status }}</span>
                    <span>{{ number_format((int) $o->total_rials) }} ریال</span>
                    <span>{{ $o->created_at?->diffForHumans() }}</span>
                </article>
            @empty
                <div class="empty-state"><i class="fa-solid fa-receipt"></i><p>سفارشی ثبت نشده است.</p></div>
            @endforelse
        </section>
    </div>
</div>
@endsection
