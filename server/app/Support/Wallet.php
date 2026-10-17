<?php

namespace App\Support;

use App\Models\Article;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

/** Pay-as-you-go wallet. All changes go through here so balance_after stays right. */
final class Wallet
{
    public static function change(User $user, float $amount, string $type, ?string $description = null,
        ?Article $article = null, ?User $by = null): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $type, $description, $article, $by) {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $balance = round((float) $locked->wallet_balance + $amount, 4);
            $locked->forceFill(['wallet_balance' => $balance])->save();
            $user->wallet_balance = $balance;

            return WalletTransaction::query()->create([
                'user_id' => $user->id,
                'amount' => round($amount, 4),
                'balance_after' => $balance,
                'type' => $type,
                'description' => $description,
                'article_id' => $article?->id,
                'created_by' => $by?->id,
            ]);
        });
    }
}
