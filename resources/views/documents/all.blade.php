@extends('layouts.app')
@section('content')
<div class="container user-dashboard farast-workspace" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-files"></i> ادمین</span>
            <h1>همه اسناد</h1>
            <p class="dashboard-sub">نمای سیستمی تمام اسناد — فقط برای مدیر. بدون داده جعلی.</p>
        </div>
        <span class="dashboard-count">{{ number_format($docs->total()) }} سند</span>
    </header>

    <section class="panel recent-documents">
        @forelse($docs as $d)
            <article class="doc">
                <span><i class="fa-regular fa-file-lines"></i><span>{{ $d->title ?: 'بدون عنوان' }}</span></span>
                <span>{{ $d->user?->name ?? $d->user?->mobile ?? '—' }}</span>
                <span>{{ $d->status }}</span>
                <span>{{ number_format((int) $d->page_count) }} صفحه</span>
                <span>{{ $d->created_at?->format('Y/m/d H:i') }}</span>
            </article>
        @empty
            <div class="empty-state"><i class="fa-regular fa-folder-open"></i><p>سندی در سامانه ثبت نشده است.</p></div>
        @endforelse
        <div class="ws-pagination">{{ $docs->links() }}</div>
    </section>
</div>
@endsection
