<?php

use App\Modules\User\Models\User;

it('lists users with pagination for a super admin', function () {
    $admin = User::factory()->superAdmin()->create();
    User::factory()->count(3)->customer()->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/admin/users?per_page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'per_page', 'total']]);
});

it('filters users by role', function () {
    $admin = User::factory()->superAdmin()->create();
    User::factory()->count(2)->venueManager()->create();
    User::factory()->count(3)->customer()->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/admin/users?role=VENUE_MANAGER')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('searches users by name or email', function () {
    $admin = User::factory()->superAdmin()->create();
    User::factory()->customer()->create(['name' => 'Rashad Aliyev', 'email' => 'rashad@test.com']);
    User::factory()->customer()->create(['name' => 'Someone Else', 'email' => 'else@test.com']);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/admin/users?search=rashad')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.email', 'rashad@test.com');
});

it('forbids a customer from listing users', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/admin/users')
        ->assertStatus(403);
});

it('forbids a venue manager from listing users', function () {
    $manager = User::factory()->venueManager()->create();

    $this->actingAs($manager, 'sanctum')
        ->getJson('/api/admin/users')
        ->assertStatus(403);
});
