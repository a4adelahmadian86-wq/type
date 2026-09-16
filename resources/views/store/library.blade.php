@extends('layouts.app')

@section('content')
<div class="container user-dashboard farast-workspace" dir="rtl">
    <header class="page-head">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-folder-open"></i> کتابخانه دیجیتال</span>
            <h1>فایل‌های خریداری‌شده</h1>
            <p class="dashboard-sub">فقط فایل‌هایی که برای حساب شما مجاز شده‌اند نمایش داده می‌شوند.</p>
        </div>
        <div class="page-head-actions">
            <a class="btn" href="{{ route('store') }}"><i class="fa-solid fa-store"></i> فروشگاه</a>
            <a class="btn" href="{{ route('dashboard') }}"><i class="fa-solid fa-gauge-high"></i> داشبورد</a>
        </div>
    </header>

    @if(session('status'))
        <div class="finance-alert"><i class="fa-solid fa-circle-check"></i>{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="finance-alert" style="border-color:#fecaca;background:#fef2f2;color:#991b1b">
            <i class="fa-solid fa-triangle-exclamation"></i>{{ $errors->first() }}
        </div>
    @endif

    @if($items->count())
        <div class="library-grid">
            @foreach($items as $item)
                <article class="library-card">
                    <div class="library-card__cover">
                        @if($item->product?->cover_path)
                            <img src="{{ asset($item->product->cover_path) }}" alt="{{ $item->product->title }}" loading="lazy">
                        @else
                            <i class="fa-solid fa-file-lines"></i>
                        @endif
                    </div>
                    <div class="library-card__body">
                        <small>{{ $item->product?->category?->name ?? 'فایل دیجیتال' }}</small>
                        <h2>{{ $item->product?->title ?? 'محصول حذف‌شده' }}</h2>
                        <p>نسخه {{ $item->product?->version ?: 'اصلی' }} · دسترسی از {{ optional($item->granted_at)->format('Y/m/d') }}</p>
                        <button type="button" class="btn primary library-download" data-library-download="{{ $item->id }}">
                            <i class="fa-solid fa-download"></i> دریافت امن
                        </button>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="ws-pagination">{{ $items->links() }}</div>
    @else
        <section class="panel">
            <div class="empty-state">
                <i class="fa-solid fa-box-open"></i>
                <p>کتابخانه هنوز خالی است.</p>
                <small>پس از خرید از فروشگاه، فایل‌ها اینجا قرار می‌گیرند.</small>
                <a href="{{ route('store') }}">مشاهده فروشگاه</a>
            </div>
        </section>
    @endif
</div>
@endsection

@push('scripts')
<script>
(() => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  document.querySelectorAll('.library-download').forEach((btn) => {
    btn.addEventListener('click', async () => {
      btn.disabled = true;
      const original = btn.innerHTML;
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> آماده‌سازی...';
      try {
        const r = await fetch('/library/' + btn.dataset.libraryDownload + '/download', {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
        });
        const data = await r.json();
        if (!r.ok || !data.url) throw new Error(data.message || 'امکان دریافت فایل وجود ندارد.');
        location.href = data.url;
      } catch (e) {
        alert(e.message || 'خطا در دریافت فایل.');
        btn.disabled = false;
        btn.innerHTML = original;
      }
    });
  });
})();
</script>
@endpush
