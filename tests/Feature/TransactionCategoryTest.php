<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TransactionCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_only_owned_transactions_and_categories(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = $owner->categories()->create([
            'name' => 'Food',
            'type' => 'expense',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/transactions', [
                'user_id' => $otherUser->id,
                'category_id' => $category->id,
                'title' => 'Lunch',
                'amount' => '25000.00',
                'type' => 'expense',
                'transaction_date' => '2026-09-26',
            ])
            ->assertCreated()
            ->assertJsonPath('data.category_id', $category->id);

        $transactionId = $owner->transactions()->firstOrFail()->id;
        $this->assertDatabaseHas('transactions', [
            'id' => $transactionId,
            'user_id' => $owner->id,
            'amount' => '25000.00',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->patchJson('/api/categories/' . $category->id, ['type' => 'income'])
            ->assertUnprocessable()
            ->assertJsonPath('responseCode', 'VALIDATION_422');

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/transactions')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($owner, 'sanctum')
            ->patchJson('/api/transactions/' . $transactionId, ['title' => 'Updated lunch'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated lunch');

        $this->actingAs($otherUser, 'sanctum')
            ->getJson('/api/transactions/' . $transactionId)
            ->assertNotFound();

        $this->actingAs($otherUser, 'sanctum')
            ->putJson('/api/transactions/' . $transactionId, ['title' => 'Tampered'])
            ->assertNotFound();

        $this->actingAs($otherUser, 'sanctum')
            ->deleteJson('/api/transactions/' . $transactionId)
            ->assertNotFound();

        $this->actingAs($otherUser, 'sanctum')
            ->postJson('/api/transactions', [
                'category_id' => $category->id,
                'title' => 'Not allowed',
                'amount' => 100,
                'type' => 'expense',
                'transaction_date' => '2026-09-26',
            ])
            ->assertUnprocessable();

        $this->actingAs($owner, 'sanctum')
            ->deleteJson('/api/transactions/' . $transactionId)
            ->assertOk();
    }

    public function test_category_type_and_access_are_validated_and_global_categories_are_read_only(): void
    {
        $user = User::factory()->create();
        $globalCategory = Category::create([
            'name' => 'Salary',
            'type' => 'income',
            'is_system' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/categories', [
                'user_id' => 999,
                'name' => 'Personal income',
                'type' => 'income',
            ])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.is_system', false);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/transactions', [
                'category_id' => $globalCategory->id,
                'title' => 'Wrong category type',
                'amount' => 100,
                'type' => 'expense',
                'transaction_date' => '2026-09-26',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('responseCode', 'VALIDATION_422')
            ->assertJsonPath('data.errors.category_id.0', 'The selected category does not support this transaction type.');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/categories')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Salary']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/categories/' . $globalCategory->id, ['name' => 'Changed'])
            ->assertNotFound();

        $personalCategory = $user->categories()->create([
            'name' => 'Transport',
            'type' => 'expense',
        ]);
        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/categories/' . $personalCategory->id, ['name' => 'Travel'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Travel');
    }

    public function test_role_middleware_distinguishes_backoffice_users_from_machine_clients(): void
    {
        Route::middleware(['auth:sanctum', 'role:staff,admin'])
            ->get('/api/test/backoffice', fn() => response()->json(['ok' => true]));

        $user = User::factory()->create(['role' => 'user']);
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/test/backoffice')
            ->assertForbidden();

        $this->actingAs($staff, 'sanctum')
            ->getJson('/api/test/backoffice')
            ->assertOk();
    }

    public function test_finance_routes_require_sanctum_authentication(): void
    {
        $this->getJson('/api/transactions')->assertUnauthorized();
        $this->getJson('/api/categories')->assertUnauthorized();

        $client = ApiClient::create([
            'name' => 'Finance API client',
            'client_id' => 'finance-client',
            'client_secret' => 'hashed-secret',
            'is_active' => true,
        ]);
        $token = $client->createToken('machine')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/transactions')
            ->assertForbidden();
    }
}
