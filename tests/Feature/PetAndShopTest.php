<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Item;
use App\Models\PetState;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PetAndShopTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;

    private Item $itemTopi;

    private Item $itemKacamata;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $school = School::first();
        $class = SchoolClass::first();

        $this->student = Student::create([
            'school_id' => $school->id,
            'class_id' => $class->id,
            'kode' => 'S1-99',
            'pin_hash' => Hash::make('1234'),
            'pet_name' => 'Dento',
            'consent_confirmed_at' => now(),
        ]);

        PetState::create([
            'student_id' => $this->student->id,
            'kebersihan' => 100,
            'kebahagiaan' => 100,
            'koin' => 50,
            'xp' => 0,
            'level' => 1,
            'last_seen_at' => now(),
            'last_decay_at' => now(),
        ]);

        $this->itemTopi = Item::where('kode', 'topi_dokter')->first();
        $this->itemKacamata = Item::where('kode', 'kacamata_bintang')->first();
    }

    public function test_pet_state_decay_respects_floor_and_max_drop(): void
    {
        $pet = $this->student->petState;

        // Simulate 200 hours absence
        $pet->update([
            'kebersihan' => 100,
            'kebahagiaan' => 100,
            'last_decay_at' => Carbon::now()->subHours(200),
        ]);

        $pet->applyDecay();
        $pet->refresh();

        // Max drop is 40, so 100 - 40 = 60
        $this->assertEquals(60, $pet->kebersihan);
        $this->assertEquals(60, $pet->kebahagiaan);

        // Another massive absence from 60
        $pet->update([
            'last_decay_at' => Carbon::now()->subHours(200),
        ]);
        $pet->applyDecay();
        $pet->refresh();

        // From 60, drop 40 would be 20, but floor is 30, so must stay at 30!
        $this->assertEquals(30, $pet->kebersihan);
        $this->assertEquals(30, $pet->kebahagiaan);
    }

    public function test_student_can_buy_shop_item_with_enough_coins(): void
    {
        $this->student->petState->update(['koin' => 60]);

        $response = $this->actingAs($this->student, 'student')
            ->postJson('/api/shop/buy', [
                'item_id' => $this->itemTopi->id,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('koin', 20); // 60 - 40 = 20

        $this->student->petState->refresh();
        $this->assertEquals(20, $this->student->petState->koin);

        $this->assertDatabaseHas('inventories', [
            'student_id' => $this->student->id,
            'item_id' => $this->itemTopi->id,
            'equipped' => false,
        ]);
    }

    public function test_student_cannot_buy_item_with_insufficient_coins(): void
    {
        $this->student->petState->update(['koin' => 10]);

        $response = $this->actingAs($this->student, 'student')
            ->postJson('/api/shop/buy', [
                'item_id' => $this->itemTopi->id, // harga 40
            ]);

        $response->assertStatus(400);
        $response->assertSee('Koinmu belum cukup');
    }

    public function test_student_cannot_buy_already_owned_item_twice(): void
    {
        $this->student->petState->update(['koin' => 100]);

        // First purchase
        $this->actingAs($this->student, 'student')
            ->postJson('/api/shop/buy', ['item_id' => $this->itemTopi->id])
            ->assertStatus(200);

        // Second purchase of same item
        $response = $this->actingAs($this->student, 'student')
            ->postJson('/api/shop/buy', ['item_id' => $this->itemTopi->id]);

        $response->assertStatus(400);
        $response->assertSee('sudah memiliki');
    }

    public function test_student_can_equip_and_unequip_item(): void
    {
        $inv = Inventory::create([
            'student_id' => $this->student->id,
            'item_id' => $this->itemTopi->id,
            'equipped' => false,
        ]);

        $equipRes = $this->actingAs($this->student, 'student')->postJson('/api/pet/equip', [
            'slot' => 'topi',
            'item_id' => $this->itemTopi->id,
        ]);
        $equipRes->assertStatus(200);

        $inv->refresh();
        $this->assertTrue($inv->equipped);

        // Unequip
        $unequipRes = $this->actingAs($this->student, 'student')->postJson('/api/pet/equip', [
            'slot' => 'topi',
            'item_id' => null,
        ]);
        $unequipRes->assertStatus(200);

        $inv->refresh();
        $this->assertFalse($inv->equipped);
    }

    public function test_daily_claim_awards_coins_once_per_day(): void
    {
        $this->student->petState->update(['koin' => 0]);

        $claimRes = $this->actingAs($this->student, 'student')
            ->postJson('/api/daily-message/claim');

        $claimRes->assertStatus(200);
        $claimRes->assertJsonPath('koin', 5);

        $this->student->petState->refresh();
        $this->assertEquals(5, $this->student->petState->koin);

        // Second claim on same day fails
        $retryRes = $this->actingAs($this->student, 'student')
            ->postJson('/api/daily-message/claim');

        $retryRes->assertStatus(400);
        $this->student->petState->refresh();
        $this->assertEquals(5, $this->student->petState->koin);
    }
}
