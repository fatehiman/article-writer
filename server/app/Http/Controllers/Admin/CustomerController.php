<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Admins create all users; there is no sign-up. */
class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->input('q'));
        $users = User::query()
            ->withCount(['services', 'articles'])
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%{$q}%")
                ->orWhere('username', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->orderByRaw("role = 'admin' desc")->orderBy('name')->paginate(30)->withQueryString();

        return view('admin.customers.index', compact('users', 'q'));
    }

    public function create(): View
    {
        return view('admin.customers.form', ['user' => new User([
            'role' => User::ROLE_CUSTOMER, 'is_active' => true, 'locale' => app()->getLocale(), 'timezone' => auth()->user()->timezone,
        ])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $user = new User($data);
        $user->save();

        return redirect()->route('admin.customers.index')->with('ok', __('ui.saved'));
    }

    public function edit(User $user): View
    {
        $transactions = $user->walletTransactions()->with('creator')->latest('id')->limit(20)->get();

        return view('admin.customers.form', compact('user', 'transactions'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        // An admin cannot lock themself out.
        if ($user->is($request->user())) {
            $data['role'] = User::ROLE_ADMIN;
            $data['is_active'] = true;
        }
        $user->update($data);

        return redirect()->route('admin.customers.index')->with('ok', __('ui.saved'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 422, __('ui.cannot_delete_self'));
        $user->delete();

        return redirect()->route('admin.customers.index')->with('ok', __('ui.deleted'));
    }

    /** Deposit (+) or take (-) money; every change is a wallet transaction. */
    public function wallet(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'not_in:0', 'between:-1000000,1000000'],
            'description' => ['nullable', 'string', 'max:190'],
        ]);
        $amount = (float) $data['amount'];
        Wallet::change($user, $amount, $amount > 0 ? 'deposit' : 'adjust', $data['description'] ?? null, null, $request->user());

        return back()->with('ok', __('ui.wallet_updated', ['balance' => money($user->wallet_balance)]));
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $request->merge(['username' => Str::lower(trim((string) $request->input('username')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'min:3', 'max:64', 'regex:/^[a-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($user?->id)],
            'email' => ['nullable', 'email', 'max:190'],
            'password' => [$user ? 'nullable' : 'required', Password::min(8)],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_CUSTOMER])],
            'locale' => ['required', Rule::in(array_keys(config('languages.ui')))],
            'timezone' => ['required', 'timezone:all'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
