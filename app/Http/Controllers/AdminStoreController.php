<?php

namespace App\Http\Controllers;

use App\Models\StoreCategory;
use App\Models\StoreProduct;
use App\Models\StoreProductFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminStoreController extends Controller
{
    public function index()
    {
        return view('admin.store', [
            'categories' => StoreCategory::withCount('products')->orderBy('sort_order')->get(),
            'products' => StoreProduct::with('category')->latest()->paginate(30),
        ]);
    }

    public function category(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:160'],
            'slug' => ['nullable','string','max:180','regex:/^[a-z0-9-]+$/','unique:store_categories,slug'],
            'parent_id' => ['nullable','integer','exists:store_categories,id'],
            'description' => ['nullable','string','max:5000'],
            'sort_order' => ['nullable','integer','min:0','max:999999'],
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        StoreCategory::create($data + ['is_active' => true]);
        return back()->with('status', 'دسته‌بندی فروشگاه ایجاد شد.');
    }

    public function product(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required','integer','exists:store_categories,id'],
            'title' => ['required','string','max:220'],
            'slug' => ['nullable','string','max:240','regex:/^[a-z0-9-]+$/','unique:store_products,slug'],
            'sku' => ['nullable','string','max:80','unique:store_products,sku'],
            'short_description' => ['nullable','string','max:5000'],
            'description' => ['nullable','string','max:100000'],
            'price_rials' => ['required','integer','min:0','max:999999999999'],
            'compare_at_price_rials' => ['nullable','integer','min:0','max:999999999999'],
            'discount_percent' => ['nullable','numeric','min:0','max:100'],
            'version' => ['nullable','string','max:60'],
            'license_type' => ['nullable','string','max:60'],
            'preview_policy' => ['required','in:none,limited,full'],
            'preview_pages' => ['required','integer','min:0','max:999'],
            'download_policy' => ['required','in:signed,limited'],
            'download_limit' => ['nullable','integer','min:1','max:10000'],
            'featured' => ['nullable','boolean'],
            'status' => ['required','in:draft,published'],
            'seo_title' => ['nullable','string','max:220'],
            'seo_description' => ['nullable','string','max:320'],
            'parameters' => ['nullable','json'],
            'cover' => ['nullable','image','max:8192'],
            'file' => ['required','file','max:524288','mimes:pdf,doc,docx,zip,rar,txt'],
            'preview_file' => ['nullable','file','max:524288','mimes:pdf,jpg,jpeg,png,webp'],
        ]);

        $price = (int) $data['price_rials'];
        if (($data['discount_percent'] ?? 0) > 0) {
            $price = (int) round($price * (1 - ((float) $data['discount_percent'] / 100)));
        }
        $slug = $data['slug'] ?: Str::slug($data['title']);
        $sku = $data['sku'] ?: 'FAR-' . strtoupper(Str::random(10));
        $parameters = json_decode($data['parameters'] ?? '[]', true);
        abort_unless(is_array($parameters), 422, 'پارامترهای محصول نامعتبر است.');

        DB::transaction(function () use ($request, $data, $price, $slug, $sku, $parameters) {
            $coverPath = $request->file('cover')?->store('store/covers', 'private');
            $product = StoreProduct::create([
                'category_id' => $data['category_id'], 'seller_id' => auth()->id(), 'title' => $data['title'],
                'slug' => $slug, 'sku' => $sku, 'type' => 'digital_file', 'short_description' => $data['short_description'] ?? null,
                'description' => $data['description'] ?? null, 'price_rials' => $price,
                'compare_at_price_rials' => $data['compare_at_price_rials'] ?? null, 'status' => $data['status'],
                'published_at' => $data['status'] === 'published' ? now() : null, 'featured' => $request->boolean('featured'),
                'seo_title' => $data['seo_title'] ?? null, 'seo_description' => $data['seo_description'] ?? null,
                'cover_path' => $coverPath, 'preview_policy' => $data['preview_policy'], 'preview_pages' => $data['preview_pages'],
                'download_policy' => $data['download_policy'], 'license_type' => $data['license_type'] ?? 'standard',
                'version' => $data['version'] ?? null,
                'metadata' => ['parameters' => $parameters, 'download_limit' => $data['download_limit'] ?? null],
            ]);
            $file = $request->file('file');
            StoreProductFile::create([
                'product_id' => $product->id, 'version' => $data['version'] ?? null, 'disk' => 'private',
                'path' => $file->store('store/products/'.$product->id, 'private'), 'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(), 'size_bytes' => $file->getSize(), 'sha256' => hash_file('sha256', $file->getRealPath()),
                'is_primary' => true, 'is_active' => true,
            ]);
            if ($request->hasFile('preview_file')) {
                $preview = $request->file('preview_file');
                $product->previews()->create([
                    'disk' => 'private', 'path' => $preview->store('store/previews/'.$product->id, 'private'),
                    'mime' => $preview->getMimeType(), 'kind' => str_starts_with((string) $preview->getMimeType(), 'image/') ? 'image' : 'pdf',
                    'watermarked' => true, 'is_active' => true, 'sort_order' => 0,
                ]);
            }
        });
        return back()->with('status', 'محصول دیجیتال ایجاد و فایل خصوصی آن ثبت شد.');
    }

    public function toggleProduct(StoreProduct $product)
    {
        $product->update(['status' => $product->status === 'published' ? 'draft' : 'published', 'published_at' => $product->status === 'published' ? now() : null]);
        return back()->with('status', 'وضعیت انتشار محصول تغییر کرد.');
    }
}
