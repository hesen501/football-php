<?php

use App\Modules\User\Models\User;

it('lets a super admin view a single user', function () {
    $admin = User::factory()->superAdmin()->create();
    $target = User::factory()->customer()->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson("/api/admin/users/{$target->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $target->id)
        ->assertJsonMissingPath('data.password');
});

it('forbids a customer from viewing another user', function () {
    $customer = User::factory()->customer()->create();
    $target = User::factory()->customer()->create();

    $this->actingAs($customer, 'sanctum')
        ->getJson("/api/admin/users/{$target->id}")
        ->assertStatus(403);
});
