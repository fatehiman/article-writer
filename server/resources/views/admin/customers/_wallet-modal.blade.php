<div class="modal fade" id="walletModal" tabindex="-1" aria-labelledby="walletModalTitle" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content aw-modal" method="post" action="{{ isset($user) && $user->exists ? route('admin.customers.wallet', $user) : '#' }}" id="walletForm">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="walletModalTitle"><i class="bi bi-wallet2 text-peach me-2"></i>{{ __('ui.wallet') }} · <span data-wallet-title>{{ isset($user) ? $user->name : '' }}</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.close') }}"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="w_amount">{{ __('ui.amount') }} ({{ \App\Models\Setting::get('currency') }})</label>
                <input class="form-control mb-1" type="number" step="0.0001" id="w_amount" name="amount" required dir="ltr">
                <div class="form-text mb-3">{{ __('ui.amount_help') }}</div>
                <label class="form-label" for="w_desc">{{ __('ui.description') }}</label>
                <input class="form-control" id="w_desc" name="description" maxlength="190">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                <button type="submit" class="btn aw-btn-primary">{{ __('ui.save') }}</button>
            </div>
        </form>
    </div>
</div>
