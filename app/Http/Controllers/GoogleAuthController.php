<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirect()
    {
        try {
            // Store intended URL if not auth pages
            $previousUrl = url()->previous();
            if (!\Illuminate\Support\Str::contains($previousUrl, ['/login', '/register', '/auth'])) {
                session(['url.intended' => $previousUrl]);
            }
            
            return Socialite::driver('google')->redirect();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Error de conexión con Google.');
        }
    }

    /**
     * Obtain the user information from Google.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Check if user already exists
            $user = User::where('google_id', $googleUser->id)->first();

            if ($user) {
                $oldSessionId = \Illuminate\Support\Facades\Session::getId();
                Auth::login($user);
                app(\App\Services\CartService::class)->mergeGuestCartIntoUserCart($user, $oldSessionId);
            } else {
                // If user doesn't exist by google_id, check by email
                $existingUserByEmail = User::where('email', $googleUser->email)->first();

                if ($existingUserByEmail) {
                    // Update existing user with google_id
                    $existingUserByEmail->update([
                        'google_id' => $googleUser->id,
                    ]);
                    $oldSessionId = \Illuminate\Support\Facades\Session::getId();
                    Auth::login($existingUserByEmail);
                    app(\App\Services\CartService::class)->mergeGuestCartIntoUserCart($existingUserByEmail, $oldSessionId);
                } else {
                    // Create new user
                    $newUser = User::create([
                        'name' => $googleUser->name,
                        'email' => $googleUser->email,
                        'google_id' => $googleUser->id,
                        'password' => null,
                    ]);
                    $oldSessionId = \Illuminate\Support\Facades\Session::getId();
                    Auth::login($newUser);
                    app(\App\Services\CartService::class)->mergeGuestCartIntoUserCart($newUser, $oldSessionId);
                }
            }

            return redirect()->intended(route('home'));

        } catch (Exception $e) {
            return redirect()->route('login')->with('error', 'Error al iniciar sesión con Google. ' . $e->getMessage());
        }
    }
}
