<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\CartSync;
use App\Traits\ScoreSync;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers,CartSync,ScoreSync;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    // 讓使用者登入後跳回前一頁
    protected function redirectTo()
    {
        return session()->pull('url.intended', '/');
    }

    public function showLoginForm()
    {
        if (!session()->has('url.intended')) {
            session(['url.intended' => url()->previous()]);
        }

        return view('auth.login');
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
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

         // 3. 嘗試登入
        if (Auth::attempt($request->only('email', 'password'), $request->filled('remember'))) {
            return $this->sendLoginResponse($request);
        }
        // 4. 登入失敗
        return back()->withErrors([
            'email' => '登入失敗，請檢查您的 Email 和密碼是否正確',
        ])->withInput();
    }

    // 使用Trait/cartSync當登入時同步購物車
    public function authenticated(Request $request, $user)
    {
        $this->syncCartWithDatabase($user);
        $this->syncScoreWithDatabase($request,$user);
    }
}
