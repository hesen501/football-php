<?php

use App\Modules\User\Models\User;

it('lets a super admin create a user with a role', function () {
    $admin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/users', [
        'name' => 'New Manager',
        'email' => 'new-manager@test.com',
        'password' => 'password123',
        'role' => 'VENUE_MANAGER',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.email', 'new-manager@test.com')
        ->assertJsonPath('data.roles.0', 'VENUE_MANAGER');

    $this->assertDatabaseHas('users', ['email' => 'new-manager@test.com']);
});

it('validates required fields when creating a user', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/admin/users', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
});

it('rejects a duplicate email', function () {
    $admin = User::factory()->superAdmin()->create();
    User::factory()->customer()->create(['email' => 'taken@test.com']);

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/admin/users', [
            'name' => 'Someone',
            'email' => 'taken@test.com',
            'password' => 'password123',
            'role' => 'CUSTOMER',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('forbids a venue manager from creating users', function () {
    $manager = User::factory()->venueManager()->create();

    $this->actingAs($manager, 'sanctum')
        ->postJson('/api/admin/users', [
            'name' => 'Someone',
            'email' => 'someone@test.com',
            'password' => 'password123',
            'role' => 'CUSTOMER',
        ])
        ->assertStatus(403);
});
