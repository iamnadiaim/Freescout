<?php

namespace Modules\PoliwangiSso\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Customer;
use App\Email;
use Modules\PoliwangiPortal\Models\EndUserPortalAccount;

class SsoController extends Controller
{
    /**
     * Redirect awal ke SSO Poliwangi.
     */
    public function redirectToPoliwangiSso(Request $request)
    {
        // Pengecekan URL Redirect secara sederhana
        $redirect = $request->input('redirect', url('/help'));
        if (strpos($redirect, url('/')) !== 0) {
            $redirect = url('/help');
        }
        
        $state = bin2hex(random_bytes(16));
        
        session([
            'end_user_portal_sso_redirect' => $redirect,
            'oauth_state' => $state,
            'oauth_portal' => true,
        ]);

        $query = http_build_query([
            'client_id'     => config('poliwangisso.oidc.client_id'),
            'redirect_uri'  => config('poliwangisso.oidc.redirect_uri') ?: route('poliwangisso.callback'),
            'response_type' => 'code',
            'scope'         => config('poliwangisso.oidc.scope', ''),
            'state'         => $state,
        ]);

        return redirect(config('poliwangisso.oidc.url_authorize') . '?' . $query);
    }

    /**
     * Callback SSO Poliwangi.
     */
    public function handlePoliwangiSsoCallback(Request $request)
    {
        $redirect = session('end_user_portal_sso_redirect', url('/help'));
        
        $state = session()->pull('oauth_state');
        session()->forget('oauth_portal');
        
        if (empty($state) || $state !== $request->get('state')) {
            return redirect()->route('PoliwangiPortal.end_user_portal.login_end_user')->withErrors(['email' => 'Invalid OAuth state.']);
        }

        if ($request->has('error')) {
            return redirect()->route('PoliwangiPortal.end_user_portal.login_end_user')->withErrors(['email' => 'OAuth Authorization denied.']);
        }

        $client = new \GuzzleHttp\Client();

        // 1. Ambil Access Token
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
            \Log::error('EndUser SSO Token Error: ' . $e->getMessage());
            return redirect()->route('PoliwangiPortal.end_user_portal.login_end_user')->withErrors(['email' => 'Gagal mengambil access token dari SSO.']);
        }

        $accessToken = $tokenData['access_token'] ?? null;
        if (!$accessToken) {
            return redirect()->route('PoliwangiPortal.end_user_portal.login_end_user')->withErrors(['email' => 'Access token missing dari respons SSO.']);
        }

        // 2. Ambil Profil User
        try {
            $userResponse = $client->get(config('poliwangisso.oidc.url_resource_owner_details'), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept'        => 'application/json',
                ],
            ]);
            $remoteUser = json_decode($userResponse->getBody()->getContents(), true);
        } catch (\Exception $e) {
            \Log::error('EndUser SSO Profile Error: ' . $e->getMessage());
            return redirect()->route('PoliwangiPortal.end_user_portal.login_end_user')->withErrors(['email' => 'Gagal mengambil data profil dari SSO.']);
        }
        
        $emailValue = strtolower(trim($remoteUser['email'] ?? ''));

        if (!$emailValue) {
            return redirect()->route('PoliwangiPortal.end_user_portal.login_end_user')->withErrors(['email' => 'Email tidak diberikan oleh server SSO.']);
        }

        // 3. Sync ke FreeScout Customer & EndUserPortalAccount
        $emailRow = Email::whereRaw('LOWER(email) = ?', [$emailValue])->first();

        if ($emailRow && $emailRow->customer) {
            $customer = $emailRow->customer;
        } else {
            $customer = Customer::create(
                $emailValue,
                [
                    'first_name' => $remoteUser['name'] ?? 'SSO User',
                    'last_name' => '',
                    'email' => $emailValue,
                    'channel' => 'end_user_portal',
                    'channel_id' => null,
                ]
            );

            $emailRow = Email::whereRaw('LOWER(email) = ?', [$emailValue])->first();
        }

        $account = EndUserPortalAccount::where('email_id', $emailRow->id)->first();

        if (!$account) {
            $account = EndUserPortalAccount::create([
                'customer_id' => $customer->id,
                'email_id' => $emailRow->id,
                'auth_type' => 'sso',
                'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(24)),
                'sso_provider' => 'poliwangi',
                'sso_id' => $remoteUser['id'] ?? null,
                'verification_token' => null,
                'email_verified_at' => now(), // SSO diabaikan verifikasi manual
            ]);
        } else {
            // Update jika sebelumnya manual, bisa login via SSO juga
            if (is_null($account->email_verified_at)) {
                $account->update(['email_verified_at' => now(), 'verification_token' => null]);
            }
        }

        // 4. Log in dengan Session
        session([
            'end_user_portal_email' => $emailRow->email,
            'end_user_portal_customer_id' => $account->customer_id,
        ]);

        return redirect($redirect)->with('success', 'Berhasil login melalui SSO Poliwangi.');
    }
}
