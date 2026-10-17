@extends('layouts.app')
@section('title', __('ui.wallet'))

@section('content')
<h1 class="h4 mb-3"><i class="bi bi-wallet2 text-peach me-2"></i>{{ __('ui.wallet') }}</h1>

<div class="row g-3 mb-3">
    <div class="col-md-5">
        <div class="card aw-card aw-wallet h-100">
            <div class="card-body">
                <div class="small text-muted">{{ __('ui.balance') }}</div>
                <div class="aw-balance">{{ money(auth()->user()->wallet_balance) }}</div>
                <p class="small text-muted mb-0 mt-2">{{ __('ui.wallet_help') }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card aw-card h-100">
            <div class="card-header aw-card-header"><i class="bi bi-tags text-violet me-2"></i>{{ __('ui.prices') }}</div>
            <div class="table-responsive">
                <table class="table table-sm aw-table mb-0 small">
                    <tbody>
                    @foreach ($agents as $a)
                        <tr>
                            <td><span class="badge aw-badge tone-{{ $a->kind === 'text' ? 'sky' : 'rose' }}">{{ __('ui.kind_'.$a->kind) }}</span></td>
                            <td>{{ $a->name }}<div class="text-muted">{{ $a->description }}</div></td>
                            <td class="text-end text-nowrap">{{ $a->priceLabel() }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card aw-card">
    <div class="card-header aw-card-header"><i class="bi bi-receipt text-mint me-2"></i>{{ __('ui.transactions') }}</div>
    <div class="table-responsive">
        <table class="table aw-table align-middle mb-0 small">
            <thead><tr><th>{{ __('ui.date') }}</th><th>{{ __('ui.description') }}</th><th class="text-end">{{ __('ui.amount') }}</th><th class="text-end">{{ __('ui.balance') }}</th></tr></thead>
            <tbody>
            @forelse ($transactions as $t)
                <tr>
                    <td class="text-nowrap aw-mono">{{ local_time($t->created_at) }}</td>
                    <td>
                        <span class="badge aw-badge tone-{{ $t->amount >= 0 ? 'mint' : 'rose' }}">{{ __('ui.tx_'.$t->type) }}</span>
                        @if ($t->article)
                            <a href="{{ route('articles.show', $t->article) }}" dir="auto">{{ \Illuminate\Support\Str::limit($t->article->title, 70) }}</a>
                        @else
                            {{ $t->description }}
                        @endif
                    </td>
                    <td class="text-end text-nowrap aw-mono {{ $t->amount >= 0 ? 'text-mint' : 'text-rose' }}" dir="ltr">{{ $t->amount >= 0 ? '+' : '' }}{{ money($t->amount) }}</td>
                    <td class="text-end text-nowrap aw-mono" dir="ltr">{{ money($t->balance_after) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">{{ __('ui.no_transactions') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $transactions->links() }}</div>
@endsection
