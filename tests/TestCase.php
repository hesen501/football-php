<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Every test gets roles/permissions seeded (RefreshDatabase runs
     * DatabaseSeeder after migrating) — almost every feature test needs
     * SUPER_ADMIN/VENUE_MANAGER/CUSTOMER roles to exist to assign them.
     */
    protected $seed = true;
}
