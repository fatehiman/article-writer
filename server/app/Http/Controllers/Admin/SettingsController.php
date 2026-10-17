<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['currency' => ['required', 'string', 'max:12']]);
        Setting::put('currency', strtoupper(trim($data['currency'])));
        Setting::put('low_balance_block', $request->boolean('low_balance_block') ? '1' : '0');

        return back()->with('ok', __('ui.saved'));
    }
}
