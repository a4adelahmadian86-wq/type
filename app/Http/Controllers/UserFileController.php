<?php

namespace App\Http\Controllers;

use App\Models\UserFile;
use App\Services\CapabilityService;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserFileController extends Controller
{
    public function index()
    {
        $files = UserFile::where('user_id', auth()->id())->latest()->get()->map(fn (UserFile $file) => $this->present($file));
        return response()->json(['ok' => true, 'files' => $files]);
    }

    public function upload(Request $request, PricingService $pricing, CapabilityService $capabilities)
    {
        $caps = $capabilities->forUser(auth()->user());
        $limit = max(1, min((int) ($caps['max_file_mb'] ?? 50), 2048));
        $request->validate(['file' => 'required|file|max:'.($limit * 1024).'|mimes:jpg,jpeg,png,webp,pdf,zip,doc,docx']);
        $file = $request->file('file');
        $bytes = $file->getSize() ?: 0;
        $path = $file->store('user-files/'.auth()->id(), 'private');
        $pages = $this->estimatePages($file->getMimeType(), $file->getClientOriginalName(), $path);
        $quote = $pricing->estimateByPages($pages);
        $now = now();
        $record = UserFile::create([
            'user_id' => auth()->id(), 'original_name' => $file->getClientOriginalName(), 'path' => $path,
            'disk' => 'private', 'mime' => $file->getMimeType(), 'size_bytes' => $bytes,
            'page_count' => $pages, 'estimated_price_rials' => $quote['price_rials'], 'status' => 'local',
            'uploaded_at' => $now, 'local_expires_at' => $now->copy()->addDays((int) env('FILES_TTL_DAYS', 14)),
            'remote_expires_at' => $now->copy()->addDays(90),
        ]);
        return response()->json(['ok' => true, 'file' => $this->present($record), 'estimate' => $quote]);
    }

    public function destroy(UserFile $file)
    {
        abort_unless($file->user_id === auth()->id(), 403);
        Storage::disk($file->disk ?: 'private')->delete($file->path);
        if ($file->remote_path) Storage::disk('farast_remote')->delete($file->remote_path);
        $file->delete();
        return response()->json(['ok' => true]);
    }

    private function present(UserFile $file): array
    {
        return [
            'id' => $file->id, 'name' => $file->original_name, 'mime' => $file->mime,
            'size_bytes' => $file->size_bytes, 'size' => $this->humanSize($file->size_bytes),
            'pages' => $file->page_count, 'estimated_price' => number_format($file->estimated_price_rials).' ریال',
            'status' => $file->status, 'uploaded_at' => optional($file->uploaded_at)->toIso8601String(),
            'local_expires_at' => optional($file->local_expires_at)->toIso8601String(),
            'remote_expires_at' => optional($file->remote_expires_at)->toIso8601String(),
        ];
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) return $bytes.' بایت';
        if ($bytes < 1048576) return round($bytes / 1024, 1).' کیلوبایت';
        if ($bytes < 1073741824) return round($bytes / 1048576, 1).' مگابایت';
        return round($bytes / 1073741824, 2).' گیگابایت';
    }

    private function estimatePages(?string $mime, string $name, string $path): int
    {
        $bytes = Storage::disk('private')->get($path);
        if ($mime === 'application/pdf' || Str::endsWith(strtolower($name), '.pdf')) {
            $count = preg_match_all('/\/Type\s*\/Page\b/u', $bytes, $m) ?: 0;
            return max(1, $count);
        }
        if (in_array($mime, ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/msword'], true)) {
            if (class_exists('ZipArchive')) {
                $tmp = tempnam(sys_get_temp_dir(), 'farast-doc');
                file_put_contents($tmp, $bytes);
                $zip = new \ZipArchive();
                $pages = 1;
                if ($zip->open($tmp) === true) {
                    $xml = $zip->getFromName('word/document.xml') ?: '';
                    $words = preg_match_all('/[\p{L}\p{N}]+/u', strip_tags($xml), $m) ?: 0;
                    $pages = max(1, (int) ceil($words / 500));
                    $zip->close();
                }
                @unlink($tmp);
                return $pages;
            }
        }
        return max(1, (int) ceil(($this->imagePageHint($mime, $bytes) ?: 1)));
    }

    private function imagePageHint(?string $mime, string $bytes): int
    {
        return str_starts_with((string) $mime, 'image/') ? 1 : 1;
    }
}
