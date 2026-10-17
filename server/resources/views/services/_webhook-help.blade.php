<div class="modal fade" id="webhookHelp" tabindex="-1" aria-labelledby="webhookHelpTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content aw-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="webhookHelpTitle"><i class="bi bi-broadcast text-violet me-2"></i>{{ __('ui.webhook_help_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.close') }}"></button>
            </div>
            <div class="modal-body">
                @include('docs._webhook')
            </div>
            <div class="modal-footer">
                <a class="btn btn-light border" href="{{ route('docs') }}" target="_blank"><i class="bi bi-book me-1"></i>{{ __('ui.full_docs') }}</a>
                <button type="button" class="btn aw-btn-primary" data-bs-dismiss="modal">{{ __('ui.ok') }}</button>
            </div>
        </div>
    </div>
</div>
