<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect to Google OAuth.
     */
    public function redirectToGoogle()
    {
        if ($redirect = request()->query('redirect')) {
            session()->put('url.intended', $redirect);
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        // First try to find by google_id, then fall back to email match
        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // Link google_id and update avatar if not already set
            $user->google_id = $user->google_id ?? $googleUser->getId();
            $user->avatar    = $user->avatar ?? $googleUser->getAvatar();
            if (is_null($user->email_verified_at)) {
                $user->email_verified_at = now();
            }
            $user->save();
        } else {
            $user = User::create([
                'google_id'         => $googleUser->getId(),
                'name'              => $googleUser->getName(),
                'email'             => $googleUser->getEmail(),
                'avatar'            => $googleUser->getAvatar(),
                'password'          => null,
                'email_verified_at' => now(),
            ]);
        }

        Auth::login($user, true);

        return redirect()->intended(route('dashboard'));
    }
}
