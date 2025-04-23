<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\CartSync;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class RegisterController extends Controller
{
    use CartSync;

    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        // 驗證輸入，包括 reCAPTCHA
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'g-recaptcha-response' => app()->environment('local') ? 'nullable' : 'required',
        ]);

        // ✅ 本地環境時，沒勾選 reCAPTCHA 也顯示錯誤
        if (app()->environment('local') && !$request->input('g-recaptcha-response')) {
            return back()->withErrors(['captcha' => '請勾選 reCAPTCHA 驗證'])->with('error', '請勾選 reCAPTCHA 驗證');
        }

        // ✅ 正式環境時，才執行 reCAPTCHA API 驗證
        if (!app()->environment('local')) {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret'   => config('services.recaptcha.secret_key'),
                'response' => $request->input('g-recaptcha-response'),
                'remoteip' => $request->ip(),
            ]);

            $responseBody = $response->json();

            if (!$responseBody['success']) {
                $errorMessage = 'reCAPTCHA 驗證失敗，請重試。';
                if (isset($responseBody['error-codes'])) {
                    $errorMessage .= ' (' . implode(', ', $responseBody['error-codes']) . ')';
                }
                return back()->withErrors(['captcha' => $errorMessage]);
            }
        }

        // ✅ 創建新用戶
        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);

        // ✅ 自動登入新用戶
        Auth::login($user);

        // ✅ 同步購物車
        $this->syncCartWithDatabase($user);

        return redirect($this->redirectTo())->with('success', '註冊成功！');
    }

    // 讓使用者註冊後跳回前一頁
    protected function redirectTo()
    {
        return session()->pull('url.intended', '/');
    }
}
