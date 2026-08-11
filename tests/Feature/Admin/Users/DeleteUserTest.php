<?php

use App\Modules\User\Models\User;

it('lets a super admin soft-delete a user', function () {
    $admin = User::factory()->superAdmin()->create();
    $target = User::factory()->customer()->create();

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/admin/users/{$target->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('users', ['id' => $target->id]);
});

it('prevents an admin from deleting themselves', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/admin/users/{$admin->id}")
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'CANNOT_DELETE_SELF');

    $this->assertDatabaseHas('users', ['id' => $admin->id, 'deleted_at' => null]);
});

it('forbids a venue manager from deleting a user', function () {
    $manager = User::factory()->venueManager()->create();
    $target = User::factory()->customer()->create();

    $this->actingAs($manager, 'sanctum')
        ->deleteJson("/api/admin/users/{$target->id}")
        ->assertStatus(403);
});
