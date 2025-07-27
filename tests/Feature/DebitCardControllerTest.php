<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'number' => (int)$debitCard->number,
        ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $otherUser = User::factory()->create();
        DebitCard::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }

    public function testCustomerCanCreateADebitCard()
    {
        $data = [
            'type' => 'Visa',
        ];

        $response = $this->postJson('/api/debit-cards', $data);

        $response->assertStatus(201);
        $response->assertJsonFragment($data);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'number' => (int)$debitCard->number,
        ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $otherUser = User::factory()->create();
        $debitCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(403);
    }

    public function testCustomerCanActivateADebitCard()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'disabled_at' => Carbon::now(),
        ]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", [
            'is_active' => true,
        ]);

        $response->assertStatus(200);
        $this->assertNull($response->json('disabled_at'));
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id,
            'disabled_at' => null,
        ]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", [
            'is_active' => false,
        ]);

        $response->assertStatus(200);
        $this->assertNotNull(DebitCard::find($debitCard->id)->disabled_at);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", [
            'is_active' => 'invalid_value',
        ]);

        $response->assertStatus(422);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('debit_cards', [
            'id' => $debitCard->id,
        ]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        $debitCard = DebitCard::factory()->create(['user_id' => $this->user->id]);

        DebitCardTransaction::create([
            'debit_card_id' => $debitCard->id,
            'amount' => 10000,
            'currency_code' => 'IDR',
        ]);

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(403);
    }
}
