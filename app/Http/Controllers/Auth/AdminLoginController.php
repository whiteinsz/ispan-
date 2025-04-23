<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AdminLoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check() && Auth::user()->role === 'admin') {
            return redirect()->route('admin.dashboard.index'); // 已登入管理員直接進入後台
        }
        return view('auth.admin-login'); // 指向管理員登入頁面
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'g-recaptcha-response' => app()->environment('local') ? 'nullable' : 'required',
        ]);

        $this->checkLoginAttempts($request); // ✅ 檢查登入次數，超過限制則鎖定

        if (app()->environment('local') && !$request->input('g-recaptcha-response')) {
            return back()->withErrors(['captcha' => '請勾選 reCAPTCHA 驗證'])->with('error', '請勾選 reCAPTCHA 驗證');
        }

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

        $credentials = $request->only('email', 'password');

        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            $this->incrementLoginAttempts($request);
        
            $remaining = RateLimiter::remaining($this->getThrottleKey($request), 3);
        
            return back()->withErrors([
                'email' => '帳號或密碼錯誤。你還可以嘗試 ' . $remaining . ' 次。',
            ]);
        }
        

        if ($user->role !== 'admin') {
            return redirect()->route('home.index'); // 無權限
        }

        if (Auth::attempt($credentials)) {
            $this->clearLoginAttempts($request); // ✅ 成功登入後清除錯誤次數
            return redirect()->route('admin.dashboard.index');
        }

        $this->incrementLoginAttempts($request);

        $remaining = RateLimiter::remaining($this->getThrottleKey($request), 3);
        
        return back()->withErrors([
            'email' => '帳號或密碼錯誤。你還可以嘗試 ' . $remaining . ' 次。',
        ]);
        
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('admin.login');
    }

    /**
     * ✅ 取得登入嘗試的 key
     */
    protected function getThrottleKey(Request $request)
    {
        return Str::lower($request->input('email')) . '|' . $request->ip();
    }

    /**
     * ✅ 檢查登入嘗試次數，超過限制則鎖定
     */
    protected function checkLoginAttempts(Request $request)
    {
        $key = $this->getThrottleKey($request);
        if (RateLimiter::tooManyAttempts($key, 3)) {
            Log::warning("管理者帳號被鎖定: {$request->input('email')} IP: {$request->ip()}");
            throw ValidationException::withMessages([
                'email' => ['您已嘗試登入過多次，請在 ' . ceil(RateLimiter::availableIn($key) / 60) . ' 分鐘後再試。'
],
            ]);
            
        }
    }

    /**
     * ✅ 增加錯誤登入次數
     */
    protected function incrementLoginAttempts(Request $request)
    {
        RateLimiter::hit($this->getThrottleKey($request), 60 * 5); // 5 分鐘內最多 5 次
    }

    /**
     * ✅ 成功登入後清除錯誤次數
     */
    protected function clearLoginAttempts(Request $request)
    {
        RateLimiter::clear($this->getThrottleKey($request));
    }
}

