@extends('layouts.app')
@section('content')
<div class="container user-dashboard farast-workspace" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-wand-magic-sparkles"></i> هوش مصنوعی</span>
            <h1>تاریخچه عملیات AI</h1>
        </div>
    </header>
    <section class="panel">
        @forelse($items as $row)
            <article class="doc">
                <span>{{ $row->operation ?? '—' }}</span>
                <span>{{ $row->status ?? '' }}</span>
                <span>{{ $row->created_at?->format('Y/m/d H:i') }}</span>
            </article>
        @empty
            <div class="empty-state"><p>تاریخچه‌ای ثبت نشده است.</p></div>
        @endforelse
        <div class="ws-pagination">{{ $items->links() }}</div>
    </section>
</div>
@endsection
