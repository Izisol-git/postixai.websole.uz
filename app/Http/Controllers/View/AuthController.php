<?php

namespace App\Http\Controllers\View;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login()
    {
        return view('auth.login');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return back()->withErrors([
                'email' => 'Email yoki parol noto‘g‘ri',
            ]);
        }
        $user = Auth::user();
        if ($user->role->name !== 'admin' && $user->role->name !== 'superadmin') {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Sizda tizimga kirish huquqi yo‘q',
            ]);
        } elseif ($user->role->name === 'superadmin') {
            return redirect('/departments');
        }
        elseif ($user->role->name === 'admin') {
            return redirect('/departments/' . $user->department_id);
        }
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/login');
    }
}
