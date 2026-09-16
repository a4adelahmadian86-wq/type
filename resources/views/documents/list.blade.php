@extends('layouts.app')
@section('content')
<div class="container user-dashboard farast-workspace" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-folder-open"></i> اسناد</span>
            <h1>{{ $heading }}</h1>
            <p class="dashboard-sub">{{ $subtitle }}</p>
        </div>
    </header>

    <section class="panel recent-documents">
        @forelse($documents as $d)
            <article class="doc">
                <span><i class="fa-regular fa-file-lines"></i><span>{{ $d->title }}</span></span>
                <span>{{ number_format((int) ($d->page_count ?? 0)) }} صفحه</span>
                <span>{{ $d->status }}</span>
                <span>{{ $d->updated_at?->diffForHumans() ?? $d->created_at?->diffForHumans() }}</span>
                <a href="{{ route('editor') }}">باز کردن</a>
            </article>
        @empty
            <div class="empty-state"><i class="fa-regular fa-folder-open"></i><p>{{ $empty ?? 'موردی یافت نشد.' }}</p></div>
        @endforelse
    </section>
</div>
@endsection
