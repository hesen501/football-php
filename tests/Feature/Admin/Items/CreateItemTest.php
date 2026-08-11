<?php

use App\Modules\User\Models\User;

it('lets a super admin create an item', function () {
    $admin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/items', [
        'name' => 'Water Bottle',
        'price' => 1.00,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Water Bottle')
        ->assertJsonPath('data.price', '1.00')
        ->assertJsonPath('data.status', 'ACTIVE');

    $this->assertDatabaseHas('items', ['name' => 'Water Bottle', 'price' => 1.00]);
});

it('lets a super admin create an item with an explicit status', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin, 'sanctum')->postJson('/api/admin/items', [
        'name' => 'Discontinued Jersey',
        'price' => 15.00,
        'status' => 'INACTIVE',
    ])->assertCreated()->assertJsonPath('data.status', 'INACTIVE');
});

it('validates required fields when creating an item', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/admin/items', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'price']);
});

it('forbids a venue manager from creating an item', function () {
    $manager = User::factory()->venueManager()->create();

    $this->actingAs($manager, 'sanctum')
        ->postJson('/api/admin/items', ['name' => 'Water Bottle', 'price' => 1.00])
        ->assertStatus(403);
});

it('forbids a customer from creating an item', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer, 'sanctum')
        ->postJson('/api/admin/items', ['name' => 'Water Bottle', 'price' => 1.00])
        ->assertStatus(403);
});
