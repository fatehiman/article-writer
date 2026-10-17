<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;

abstract class Controller
{
    /** Customers only see their own rows. Others get 404, not 403, so ids leak nothing. */
    protected function own(Model $model): void
    {
        abort_unless((int) $model->getAttribute('user_id') === (int) auth()->id(), 404);
    }
}
