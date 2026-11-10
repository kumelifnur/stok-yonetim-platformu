<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'owner_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'business_name' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['owner_name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $baseSlug = Str::slug($data['business_name']) ?: 'dukkan';
            $slug = $baseSlug;
            $suffix = 2;
            while (Business::where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$suffix++;
            }

            $user->business()->create(['name' => $data['business_name'], 'slug' => $slug]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'İşletme hesabın oluşturuldu. Ürünlerini ekleyerek başlayabilirsin.');
    }
}
