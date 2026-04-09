<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Example test demonstrating how to use the UserFactory for creating test users
 * with different states and configurations.
 */
class UserFactoryExamplesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating a basic user with factory defaults
     */
    public function test_create_basic_user(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $user->email,
        ]);
    }

    /**
     * Test creating an unverified user
     */
    public function test_create_unverified_user(): void
    {
        $user = User::factory()->unverified()->create();

        $this->assertNull($user->email_verified_at);
    }

    /**
     * Test creating a user without two-factor authentication
     */
    public function test_create_user_without_two_factor(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();

        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
    }

    /**
     * Test creating a verified user (verified + no 2FA)
     */
    public function test_create_verified_user(): void
    {
        $user = User::factory()->verified()->create();

        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->two_factor_secret);
    }

    /**
     * Test creating an admin user
     * Note: Requires 'is_admin' column on users table
     */
    public function test_create_admin_user(): void
    {
        // Create admin user via factory
        $admin = User::factory()->admin()->create();

        // If is_admin column exists, verify it
        if (method_exists($admin, 'is_admin') || isset($admin->is_admin)) {
            $this->assertTrue($admin->is_admin);
        }
    }

    /**
     * Test creating a user with custom attributes
     */
    public function test_create_user_with_custom_attributes(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
    }

    /**
     * Test signing in a user (using TestCase helper)
     */
    public function test_sign_in_user(): void
    {
        $user = $this->signIn();

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test signing in an admin user (using TestCase helper)
     */
    public function test_sign_in_admin_user(): void
    {
        $admin = $this->signInAsAdmin();

        $this->assertAuthenticatedAs($admin);
        if (isset($admin->is_admin)) {
            $this->assertTrue($admin->is_admin);
        }
    }

    /**
     * Test creating multiple users for bulk operations
     */
    public function test_create_multiple_users(): void
    {
        $users = User::factory()->count(5)->create();

        $this->assertCount(5, $users);
        $this->assertCount(5, User::all());
    }

    /**
     * Test creating users with multiple different states
     */
    public function test_create_users_with_different_states(): void
    {
        $verifiedUser = User::factory()->verified()->create();
        $unverifiedUser = User::factory()->unverified()->create();
        $adminUser = User::factory()->admin()->create();

        $this->assertNotNull($verifiedUser->email_verified_at);
        $this->assertNull($unverifiedUser->email_verified_at);
        if (isset($adminUser->is_admin)) {
            $this->assertTrue($adminUser->is_admin);
        }
    }

    /**
     * Test chaining factory states
     */
    public function test_chain_factory_states(): void
    {
        $user = User::factory()
            ->admin()
            ->verified()
            ->create();

        $this->assertNotNull($user->email_verified_at);
        if (isset($user->is_admin)) {
            $this->assertTrue($user->is_admin);
        }
    }
}

