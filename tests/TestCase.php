<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Feature\GenerateCoupons;

abstract class TestCase extends BaseTestCase
{
    //
    /**
     * Create and authenticate a user.
     *
     * @param \App\Models\User|null $user Optional user instance to authenticate
     * @param array $attributes Additional attributes to override on the user
     * @return \App\Models\User
     */
    protected function signIn($user = null, array $attributes = [])
    {
        $user = $user ?: User::factory()->create($attributes);
        $this->actingAs($user);
        return $user;
    }

    /**
     * Create and authenticate an admin user.
     *
     * @param \App\Models\User|null $user Optional user instance to authenticate as admin
     * @param array $attributes Additional attributes to override on the admin user
     * @return \App\Models\User
     */
    protected function signInAsAdmin($user = null, array $attributes = [])
    {
        $attributes = array_merge(['is_admin' => true], $attributes);
        return $this->signIn($user, $attributes);
    }
}
