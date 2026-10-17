<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190'],
            'locale' => ['required', Rule::in(array_keys(config('languages.ui')))],
            'timezone' => ['required', 'timezone:all'],
            'current_password' => ['nullable', 'required_with:password', 'string'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        if (! empty($data['password'])) {
            if (! Hash::check((string) $data['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => __('ui.wrong_current_password')]);
            }
            $user->password = $data['password'];
        }
        $user->fill(collect($data)->only(['name', 'email', 'locale', 'timezone'])->all())->save();
        $request->session()->put('locale', $user->locale);

        return back()->with('ok', __('ui.saved'));
    }
}
