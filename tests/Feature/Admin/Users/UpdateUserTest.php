<?php

use App\Modules\User\Models\User;

it('lets a super admin update a user', function () {
    $admin = User::factory()->superAdmin()->create();
    $target = User::factory()->customer()->create(['name' => 'Old Name']);

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/admin/users/{$target->id}", ['name' => 'New Name'])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name');

    $this->assertDatabaseHas('users', ['id' => $target->id, 'name' => 'New Name']);
});

it('lets a super admin change a user\'s role', function () {
    $admin = User::factory()->superAdmin()->create();
    $target = User::factory()->customer()->create();

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/admin/users/{$target->id}", ['role' => 'VENUE_MANAGER'])
        ->assertOk()
        ->assertJsonPath('data.roles.0', 'VENUE_MANAGER');

    expect($target->fresh()->hasRole('CUSTOMER'))->toBeFalse();
});

it('lets a super admin suspend a user', function () {
    $admin = User::factory()->superAdmin()->create();
    $target = User::factory()->customer()->create();

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/admin/users/{$target->id}", ['status' => 'SUSPENDED'])
        ->assertOk()
        ->assertJsonPath('data.status', 'SUSPENDED');
});

it('forbids a customer from updating another user', function () {
    $customer = User::factory()->customer()->create();
    $target = User::factory()->customer()->create();

    $this->actingAs($customer, 'sanctum')
        ->putJson("/api/admin/users/{$target->id}", ['name' => 'Hacked'])
        ->assertStatus(403);
});

it('returns 404 for a non-existent user', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin, 'sanctum')
        ->putJson('/api/admin/users/999999', ['name' => 'Nobody'])
        ->assertStatus(404);
});
