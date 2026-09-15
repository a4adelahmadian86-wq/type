<?php

namespace Tests\Feature;

use App\Models\StoreCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AccountAndAdminStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_open_and_update_profile(): void
    {
        $user = User::factory()->create(['mobile' => '09120000000']);
        $this->actingAs($user)->get(route('account.profile'))->assertOk();
        $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => 'کاربر آزمایشی', 'email' => 'user@example.test', 'bio' => 'حساب FARAST',
        ])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'user@example.test']);
    }

    public function test_non_admin_cannot_manage_store(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.store'))->assertForbidden();
    }

    public function test_admin_can_create_a_private_digital_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = StoreCategory::create(['name' => 'قالب', 'slug' => 'templates', 'is_active' => true]);
        $this->actingAs($admin)->post(route('admin.store.products.create'), [
            'category_id' => $category->id, 'title' => 'قالب نمونه', 'price_rials' => 100000,
            'preview_policy' => 'limited', 'preview_pages' => 3, 'download_policy' => 'signed',
            'status' => 'draft', 'parameters' => json_encode([['name' => 'format', 'type' => 'select', 'required' => true, 'readonly' => false]]),
            'file' => UploadedFile::fake()->create('sample.pdf', 20, 'application/pdf'),
        ])->assertRedirect();
        $this->assertDatabaseHas('store_products', ['title' => 'قالب نمونه', 'price_rials' => 100000, 'status' => 'draft']);
        $this->assertDatabaseCount('store_product_files', 1);
    }
}
