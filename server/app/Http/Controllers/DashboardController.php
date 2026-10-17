<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Delivery;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $articles = Article::query()->where('user_id', $user->id);

        $stats = [
            'services' => $user->services()->count(),
            'active_services' => $user->services()->where('is_active', true)->count(),
            'articles' => (clone $articles)->where('status', Article::READY)->count(),
            'failed' => (clone $articles)->where('status', Article::FAILED)->where('created_at', '>', now()->subDays(7))->count(),
            'titles_left' => \App\Models\Title::query()->where('used_count', 0)
                ->whereHas('titleList', fn ($q) => $q->where('user_id', $user->id))->count(),
            'deliveries_failed' => Delivery::query()->where('status', Delivery::FAILED)
                ->whereHas('article', fn ($q) => $q->where('user_id', $user->id))->where('created_at', '>', now()->subDays(7))->count(),
        ];

        $upcoming = $user->services()->where('is_active', true)->whereNotNull('next_run_at')->orderBy('next_run_at')->limit(5)->get();
        $recent = (clone $articles)->with('service')->latest('id')->limit(8)->get();

        $admin = $user->isAdmin() ? [
            'customers' => User::query()->count(),
            'articles_today' => Article::query()->where('created_at', '>=', now()->startOfDay())->count(),
            'income_30d' => -1 * (float) \App\Models\WalletTransaction::query()->where('type', 'charge')
                ->where('created_at', '>', now()->subDays(30))->sum('amount'),
        ] : null;

        return view('dashboard', compact('stats', 'upcoming', 'recent', 'admin'));
    }
}
