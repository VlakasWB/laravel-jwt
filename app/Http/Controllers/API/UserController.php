<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Actions\Fortify\PasswordValidationRules;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserController extends Controller
{
    use PasswordValidationRules;

    public function register(Request $request)
    {
        $request->validate(
            [
                'username' => 'required|string|max:255',
                'email'    => 'required|string|email|max:255|unique:users|same:email',
                'password' => $this->passwordRules(),
                'password_confirmation' => 'required|same:password'

            ],
            [
                'username.required'      => 'Username wajib diisi.',
                'password.required'      => 'Password wajib diisi.',
                'password_confirmation.required'  => 'Harap konfirmasi password.',
                'password_confirmation.same'      => 'Password tidak sesuai.',
                'email.required'         => 'Email wajib diisi.',
                'email.email'            => 'Email tidak valid.',
                'email.unique'           => 'Email sudah terdaftar.',
                'email.same'             => 'Email sudah terkonfirmasi.'
            ]
        );
        User::create([
            'username' => $request->username,
            'role' => $request->role,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);


        $user = User::where('email', $request->email)->first();

        $token = auth()->attempt($request->only('email', 'password'));

        return ResponseFormatter::success([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 'Pengguna berhasil terdaftar');
    }


    public function login(Request $request)
    {
        $request->validate(
            [
                'email' => 'email|required|same:email',
                'password' => 'required'
            ],
            [
                'email.email' => 'Format email tidak sesuai',
                'email.required' => 'Harap masukkan email',
                'email.same' => 'Email tidak terdaftar',
                'password.required' => 'Harap masukkan password',
            ]
        );
        $credentials = request(['email', 'password']);

        $user = User::where('email', $request->email)->first();
        if ($user == null) {
            return ResponseFormatter::error([
                'message' => ['Email Tidak Terdaftar']
            ], 'Gagal Masuk');
        } else {
            if (!Auth::attempt($credentials)) {
                return ResponseFormatter::error([
                    'message' => ['Password yang anda masukkan salah']
                ], 'Gagal masuk');
            }
        }

        if (!Hash::check($request->password, $user->password, [])) {
            throw new \Exception('Invalid Credentials');
        }

        $token = auth()->attempt($request->only('email', 'password'));

        return ResponseFormatter::success([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 'Anda berhasil masuk');
    }

    public function logout(Request $request)
    {
        $token = auth()->logout();
        return ResponseFormatter::success($token, 'Token Revoked');
    }

    public function fetch(Request $request)
    {
        return ResponseFormatter::success($request->user(), 'Data profile user berhasil diambil');
    }
}
