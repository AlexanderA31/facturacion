<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuppliersTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_all_suppliers()
    {
        $user = User::factory()->create();
        Supplier::factory()->count(3)->for($user)->create();

        $response = $this->actingAs($user)->getJson('/api/suppliers');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data.data');
    }

    public function test_can_create_a_supplier()
    {
        $user = User::factory()->create();
        $supplierData = [
            'tipo_id' => '01',
            'identificacion' => '0102030405001',
            'razon_social' => 'Test Supplier',
        ];

        $response = $this->actingAs($user)->postJson('/api/suppliers', $supplierData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('suppliers', $supplierData);
    }

    public function test_can_get_a_supplier()
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->for($user)->create();

        $response = $this->actingAs($user)->getJson("/api/suppliers/{$supplier->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $supplier->id]);
    }

    public function test_can_update_a_supplier()
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->for($user)->create();
        $updateData = ['razon_social' => 'Updated Supplier Name'];

        $response = $this->actingAs($user)->putJson("/api/suppliers/{$supplier->id}", $updateData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('suppliers', $updateData);
    }

    public function test_can_delete_a_supplier()
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->for($user)->create();

        $response = $this->actingAs($user)->deleteJson("/api/suppliers/{$supplier->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }
}
