<?php

namespace App\Http\Controllers;

use App\Actions\Auth\CasClient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CasController extends Controller
{
    public function __construct(private readonly CasClient $casClient)
    {
    }

    public function redirect(Request $request): RedirectResponse
    {
        $returnTo = $this->normalizeReturnTo(
            $request,
            (string) $request->query('return', route('dashboard', absolute: false)),
        );

        $request->session()->put('cas.return_to', $returnTo);

        return redirect()->away($this->casClient->buildLoginUrl($this->serviceUrl($returnTo)));
    }

    public function callback(Request $request): RedirectResponse
    {
        $returnTo = $this->normalizeReturnTo(
            $request,
            (string) $request->query('return', (string) $request->session()->get('cas.return_to', route('dashboard', absolute: false))),
        );

        $ticket = trim((string) $request->query('ticket', ''));
        if ($ticket === '') {
            return redirect()->route('login')->withErrors([
                'cas' => 'CAS login failed: missing ticket.',
            ]);
        }

        $validated = $this->casClient->validateTicket($this->serviceUrl($returnTo), $ticket);
        if ($validated === null) {
            return redirect()->route('login')->withErrors([
                'cas' => 'CAS login failed: ticket validation failed.',
            ]);
        }

        $user = $this->resolveUser($validated['username'], $validated['attributes']);

        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->put('cas.user', [
            'username' => $validated['username'],
            'ticket' => $ticket,
        ]);

        return redirect()->to($returnTo);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->away($this->casClient->buildLogoutUrl(route('home', absolute: true)));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function resolveUser(string $username, array $attributes): User
    {
        $idAttribute = (string) config('cas.id_attribute', 'gh');
        $nameAttribute = (string) config('cas.name_attribute', 'name');
        $emailAttribute = (string) config('cas.email_attribute', 'email');

        $staffNo = trim((string) ($attributes[$idAttribute] ?? ''));
        $displayName = trim((string) ($attributes[$nameAttribute] ?? ''));
        $email = trim((string) ($attributes[$emailAttribute] ?? ''));

        if ($displayName === '') {
            $displayName = $username;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailDomain = trim((string) config('cas.email_domain', 'cas.local'));
            $normalizedUsername = Str::lower(Str::slug($username, '.'));
            $email = $normalizedUsername.'@'.$emailDomain;
        }

        $query = User::query()->where('email', $email);
        if ($staffNo !== '') {
            $query->orWhere('student_id', $staffNo);
        }

        $user = $query->first();
        if ($user !== null) {
            $user->fill([
                'name' => $displayName,
                'email' => $email,
                'student_id' => $staffNo !== '' ? $staffNo : $user->student_id,
            ])->save();

            return $user;
        }

        if (! (bool) config('cas.auto_register', true)) {
            abort(403, 'CAS account is not bound to a local user.');
        }

        $user = User::query()->create([
            'name' => $displayName,
            'email' => $email,
            'password' => Hash::make(Str::password(24)),
            'student_id' => $staffNo !== '' ? $staffNo : null,
        ]);

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }

    private function normalizeReturnTo(Request $request, string $returnTo): string
    {
        if ($returnTo === '') {
            return route('dashboard', absolute: false);
        }

        if (Str::startsWith($returnTo, ['/'])) {
            return $returnTo;
        }

        if (Str::startsWith($returnTo, ['http://', 'https://'])) {
            $host = parse_url($returnTo, PHP_URL_HOST);
            $port = parse_url($returnTo, PHP_URL_PORT);

            $requestHost = $request->getHost();
            $requestPort = $request->getPort();

            if ($host === $requestHost && ((int) ($port ?? $requestPort) === (int) $requestPort)) {
                return (string) Str::after($returnTo, $request->getSchemeAndHttpHost());
            }
        }

        return route('dashboard', absolute: false);
    }

    private function serviceUrl(string $returnTo): string
    {
        return route('cas.callback', [
            'return' => $returnTo,
        ], absolute: true);
    }
}

