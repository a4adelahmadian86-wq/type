@extends('layouts.app')
@section('content')
<div class="container user-dashboard farast-workspace" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-files"></i> اسناد</span>
            <h1>همه اسناد (ادمین)</h1>
        </div>
    </header>
    <section class="panel">
        @forelse($documents as $doc)
            <article class="doc">
                <div><b>{{ $doc->title ?: 'بدون عنوان' }}</b><small>#{{ $doc->id }}</small></div>
                <span>{{ $doc->status }}</span>
            </article>
        @empty
            <div class="empty-state"><p>سندی نیست.</p></div>
        @endforelse
        <div class="ws-pagination">{{ $documents->links() }}</div>
    </section>
</div>
@endsection
