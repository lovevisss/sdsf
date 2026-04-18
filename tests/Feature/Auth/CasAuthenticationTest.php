<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CasAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_is_redirected_to_cas_login(): void
    {
        config()->set('cas.base_url', 'https://cas.example.edu/cas');

        $response = $this->get(route('cas.redirect', [
            'return' => '/dashboard',
        ], false));

        $response->assertRedirect();
        $target = $response->headers->get('Location');

        $this->assertNotNull($target);
        $this->assertStringStartsWith('https://cas.example.edu/cas/login?', $target);
        $this->assertStringContainsString(urlencode(route('cas.callback', ['return' => '/dashboard'], true)), $target);
    }

    public function test_user_can_sign_in_through_cas_callback(): void
    {
        config()->set('cas.base_url', 'https://cas.example.edu/cas');

        Http::fake([
            'https://cas.example.edu/cas/serviceValidate*' => Http::response(<<<XML
<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
    <cas:authenticationSuccess>
        <cas:user>teacher001</cas:user>
        <cas:attributes>
            <cas:name>Teacher One</cas:name>
            <cas:gh>20150301</cas:gh>
        </cas:attributes>
    </cas:authenticationSuccess>
</cas:serviceResponse>
XML),
        ]);

        $response = $this->get(route('cas.callback', [
            'return' => '/dashboard',
            'ticket' => 'ST-1-demo',
        ], false));

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'name' => 'Teacher One',
            'student_id' => '20150301',
            'email' => 'teacher001@cas.local',
        ]);
    }

    public function test_invalid_cas_ticket_redirects_back_to_login(): void
    {
        config()->set('cas.base_url', 'https://cas.example.edu/cas');

        Http::fake([
            'https://cas.example.edu/cas/serviceValidate*' => Http::response(<<<XML
<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
    <cas:authenticationFailure code="INVALID_TICKET">Ticket validation failed.</cas:authenticationFailure>
</cas:serviceResponse>
XML),
        ]);

        $response = $this->get(route('cas.callback', [
            'return' => '/dashboard',
            'ticket' => 'ST-invalid',
        ], false));

        $response->assertRedirect(route('login', absolute: false));
        $response->assertSessionHasErrors('cas');
        $this->assertGuest();
    }
}

