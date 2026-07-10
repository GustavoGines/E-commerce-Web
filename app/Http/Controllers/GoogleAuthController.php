<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CartService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    private function configureSslClient(): void
    {
        // En entorno local (Windows/Laragon), los certificados SSL del sistema
        // no siempre incluyen la cadena completa para googleapis.com.
        // Esto es seguro porque solo aplica en local y en producción se usan
        // los certificados del servidor real.
        $verify = app()->isProduction()
            ? (ini_get('curl.cainfo') ?: true)
            : false;

        $guzzle = new \GuzzleHttp\Client(['verify' => $verify]);
        Socialite::driver('google')->setHttpClient($guzzle);
    }

    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirect(): \Illuminate\Http\RedirectResponse
    {
        try {
            // Store intended URL if not auth pages
            $previousUrl = url()->previous();
            if (! Str::contains($previousUrl, ['/login', '/register', '/auth'])) {
                session(['url.intended' => $previousUrl]);
            }

            $this->configureSslClient();
            return Socialite::driver('google')->redirect();
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error en redirect Google: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Error de conexión con Google. Por favor, intenta nuevamente.');
        }
    }

    /**
     * Obtain the user information from Google.
     */
    public function callback(): \Illuminate\Http\RedirectResponse
    {
        try {
            $this->configureSslClient();
            $googleUser = Socialite::driver('google')->user();

            // Check if user already exists
            $user = User::where('google_id', $googleUser->id)->first();

            if ($user) {
                $this->loginAndMergeCart($user);
            } else {
                // If user doesn't exist by google_id, check by email
                $existingUserByEmail = User::where('email', $googleUser->email)->first();

                if ($existingUserByEmail) {
                    // Update existing user with google_id
                    $existingUserByEmail->update([
                        'google_id' => $googleUser->id,
                    ]);
                    $this->loginAndMergeCart($existingUserByEmail);
                } else {
                    // Create new user
                    $newUser = User::create([
                        'name' => $googleUser->name,
                        'email' => $googleUser->email,
                        'google_id' => $googleUser->id,
                        'password' => null,
                    ]);
                    $this->loginAndMergeCart($newUser);
                }
            }

            return redirect()->intended(route('home'));

        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('GOOGLE CALLBACK ERROR: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('login')->with('error', 'Error al iniciar sesión con Google. Por favor, intenta nuevamente.');
        }
    }

    /**
     * Logs the user in and merges the guest cart into the user's cart.
     */
    private function loginAndMergeCart(User $user): void
    {
        $oldSessionId = Session::getId();
        Auth::login($user);
        request()->session()->regenerate();
        app(CartService::class)->mergeGuestCartIntoUserCart($user, $oldSessionId);
    }
}
