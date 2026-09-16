@extends('layouts.app')
@section('content')
<div class="container user-dashboard farast-workspace" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-folder-open"></i> اسناد</span>
            <h1>{{ $heading }}</h1>
            <p class="dashboard-sub">{{ $subtitle }}</p>
        </div>
        <a class="btn primary" href="{{ route('editor') }}"><i class="fa-solid fa-plus"></i> سند جدید</a>
    </header>

    <section class="panel recent-documents">
        @forelse($documents as $d)
            <article class="doc">
                <span><i class="fa-regular fa-file-lines"></i><span>{{ $d->title }}</span></span>
                <span>{{ number_format((int) $d->page_count) }} صفحه</span>
                <span>{{ $d->status }}</span>
                <span>{{ number_format((int) $d->price_rials) }} ریال</span>
                <a href="{{ route('editor') }}"><i class="fa-solid fa-arrow-left"></i> باز کردن</a>
            </article>
        @empty
            <div class="empty-state"><i class="fa-regular fa-folder-open"></i><p>هنوز سندی ندارید.</p><a href="{{ route('editor') }}">ایجاد اولین سند</a></div>
        @endforelse

        @if(method_exists($documents, 'links'))
            <div class="ws-pagination">{{ $documents->links() }}</div>
        @endif
    </section>
</div>
@endsection
