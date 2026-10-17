<?php

namespace App\Http\Controllers;

use App\Models\AiAgent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function __invoke(Request $request): View
    {
        $transactions = $request->user()->walletTransactions()->with('article')->latest('id')->paginate(30);
        $agents = AiAgent::query()->where('is_active', true)->orderBy('kind')->orderBy('sort')->get();

        return view('wallet', compact('transactions', 'agents'));
    }
}
