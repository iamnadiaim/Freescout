<?php

namespace Modules\PoliwangiSso\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\User;
use Illuminate\Support\Str;

class PoliwangiSsoController extends Controller
{
    public function redirectToProvider(Request $request)
    {
        $state = bin2hex(random_bytes(16));
        $request->session()->put('oauth_state', $state);

        $query = http_build_query([
            'client_id'     => config('poliwangisso.oidc.client_id'),
            'redirect_uri'  => config('poliwangisso.oidc.redirect_uri') ?: route('poliwangisso.callback'),
            'response_type' => 'code',
            'scope'         => config('poliwangisso.oidc.scope', ''),
            'state'         => $state,
        ]);

        return redirect(config('poliwangisso.oidc.url_authorize') . '?' . $query);
    }

    public function handleProviderCallback(Request $request)
    {
        // Forward ke Portal jika login berasal dari End User Portal
        if (session('oauth_portal') === true) {
            return app(\Modules\PoliwangiPortal\Http\Controllers\EndUserPortalController::class)->handlePoliwangiSsoCallback($request);
        }

        // Validasi state untuk mencegah serangan CSRF
        $state = $request->session()->pull('oauth_state');
        if (empty($state) || $state !== $request->get('state')) {
            return redirect('/login')->with('error', 'Invalid OAuth state.');
        }

        if ($request->has('error')) {
            return redirect('/login')->with('error', 'OAuth Authorization denied.');
        }

        $client = new \GuzzleHttp\Client();

        // 1. Tukar Authorization Code dengan Access Token
        try {
            $response = $client->post(config('poliwangisso.oidc.url_access_token'), [
                'form_params' => [
                    'grant_type'    => 'authorization_code',
                    'client_id'     => config('poliwangisso.oidc.client_id'),
                    'client_secret' => config('poliwangisso.oidc.client_secret'),
                    'redirect_uri'  => config('poliwangisso.oidc.redirect_uri') ?: route('poliwangisso.callback'),
                    'code'          => $request->get('code'),
                ],
            ]);
            $tokenData = json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            \Log::error('SSO Token Error: ' . $e->getMessage());
            return redirect('/login')->with('error', 'Failed to fetch access token.');
        }

        $accessToken = $tokenData['access_token'] ?? null;
        if (!$accessToken) {
            return redirect('/login')->with('error', 'Access token missing from response.');
        }

        // 2. Ambil data profil User dari server Laravel (SSO Poliwangi)
        try {
            $userResponse = $client->get(config('poliwangisso.oidc.url_resource_owner_details'), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept'        => 'application/json',
                ],
            ]);
            $remoteUser = json_decode($userResponse->getBody()->getContents(), true);
        } catch (\Exception $e) {
            \Log::error('SSO Profile Error: ' . $e->getMessage());
            return redirect('/login')->with('error', 'Failed to fetch user profiles.');
        }
        
        // Pastikan server SSO mengembalikan field 'email'
        $email = $remoteUser['email'] ?? null; 

        if (!$email) {
            return redirect('/login')->with('error', 'Email not provided by OAuth server.');
        }

        // 3. Cari Agent di FreeScout berdasarkan email
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Opsi B: Buka komentar ini jika ingin otomatis membuat akun Agent baru saat pertama kali login
            $user = User::create([
                'first_name' => $remoteUser['name'] ?? 'SSO User',
                'last_name'  => '',
                'email'      => $email,
                'password'   => bcrypt(Str::random(24)),
                'role'       => User::ROLE_AGENT, // Sesuaikan role bawaan FreeScout
                'status'     => User::STATUS_ACTIVE,
            ]);
        }

        // 4. Loginkan ke dalam sistem FreeScout
        Auth::login($user, true);

        return redirect('/dashboard');
    }
}
