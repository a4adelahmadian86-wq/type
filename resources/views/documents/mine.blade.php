@extends('layouts.app')
@section('content')
<div class="container user-dashboard farast-workspace" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-folder-open"></i> اسناد</span>
            <h1>{{ $title ?? 'اسناد من' }}</h1>
            <p class="dashboard-sub">{{ $subtitle ?? '' }}</p>
        </div>
        @if(($capabilities['can_type'] ?? false))
            <a class="btn primary" href="{{ route('editor') }}"><i class="fa-solid fa-plus"></i> تایپ جدید</a>
        @endif
    </header>
    <section class="panel">
        @forelse($documents as $doc)
            <article class="doc">
                <div>
                    <b>{{ $doc->title ?: 'بدون عنوان' }}</b>
                    <small>{{ $doc->updated_at?->format('Y/m/d H:i') }}</small>
                </div>
                <a class="btn" href="{{ route('editor') }}?doc={{ $doc->id }}">باز کردن</a>
            </article>
        @empty
            <div class="empty-state"><p>سندی در این فهرست نیست.</p></div>
        @endforelse
        <div class="ws-pagination">{{ $documents->links() }}</div>
    </section>
</div>
@endsection
