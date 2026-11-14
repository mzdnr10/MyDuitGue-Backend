<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinanceReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_read_home_and_statistics_for_owned_transactions(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Food', 'type' => 'expense', 'is_system' => true]);
        $user->transactions()->createMany([
            ['category_id' => $category->id, 'title' => 'Salary', 'amount' => 5000000, 'type' => 'income', 'transaction_date' => '2026-09-01'],
            ['category_id' => $category->id, 'title' => 'Lunch', 'amount' => 25000, 'type' => 'expense', 'transaction_date' => '2026-09-02'],
        ]);

        $this->actingAs($user, 'sanctum')->getJson('/api/home')
            ->assertOk()
            ->assertJsonPath('data.balance', 4975000)
            ->assertJsonPath('data.income', 5000000)
            ->assertJsonPath('data.expense', 25000)
            ->assertJsonCount(2, 'data.recent_transactions');

        $this->actingAs($user, 'sanctum')->getJson('/api/statistics?month=9&year=2026')
            ->assertOk()
            ->assertJsonPath('data.total_income', 5000000)
            ->assertJsonPath('data.total_expense', 25000)
            ->assertJsonPath('data.expense_by_category.0.category_name', 'Food');
    }

    public function test_user_can_upload_proof_only_for_owned_transaction(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $other = User::factory()->create();
        $transaction = $user->transactions()->create([
            'title' => 'Dinner',
            'amount' => 10000,
            'type' => 'expense',
            'transaction_date' => '2026-09-27',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/transactions/' . $transaction->id . '/payment-proofs', [
                'file' => UploadedFile::fake()->image('receipt.png'),
            ])->assertCreated()
            ->assertJsonPath('data.mime_type', 'image/png');

        $this->actingAs($other, 'sanctum')
            ->postJson('/api/transactions/' . $transaction->id . '/payment-proofs', [
                'file' => UploadedFile::fake()->image('receipt.png'),
            ])->assertNotFound();
    }

    public function test_staff_can_use_backoffice_reports_but_normal_users_cannot(): void
    {
        $user = User::factory()->create();
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($user, 'sanctum')->getJson('/api/backoffice/users')->assertForbidden();
        $this->actingAs($staff, 'sanctum')->getJson('/api/backoffice/users')->assertOk();
        $this->actingAs($staff, 'sanctum')->getJson('/api/backoffice/home')
            ->assertOk()
            ->assertJsonStructure(['data' => ['users', 'transactions', 'income', 'expense']]);
    }
}
