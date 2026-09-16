@extends('layouts.app')
@section('content')
<div class="container user-dashboard farast-workspace" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-clock-rotate-left"></i> میز کار</span>
            <h1>فعالیت‌های اخیر</h1>
            <p class="dashboard-sub">اسناد، عملیات AI و سفارش‌های اخیر حساب شما.</p>
        </div>
        <a class="btn" href="{{ route('dashboard') }}"><i class="fa-solid fa-arrow-right"></i> داشبورد</a>
    </header>

    <div class="ws-grid two">
        <section class="panel">
            <div class="panel-heading"><h2>اسناد</h2></div>
            @forelse($documents as $d)
                <article class="doc">
                    <span><i class="fa-regular fa-file-lines"></i> {{ $d->title ?: 'بدون عنوان' }}</span>
                    <span>{{ $d->updated_at?->diffForHumans() }}</span>
                    <a href="{{ route('editor') }}?doc={{ $d->id }}">باز کردن</a>
                </article>
            @empty
                <div class="empty-state"><p>سندی نیست.</p></div>
            @endforelse
        </section>
        <section class="panel">
            <div class="panel-heading"><h2>AI اخیر</h2></div>
            @forelse($ai as $row)
                <article class="doc">
                    <span>{{ $row->operation ?? '—' }}</span>
                    <span>{{ $row->status }}</span>
                    <span>{{ $row->created_at?->diffForHumans() }}</span>
                </article>
            @empty
                <div class="empty-state"><p>عملیات AI ثبت نشده.</p></div>
            @endforelse
        </section>
    </div>
</div>
@endsection
