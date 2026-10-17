<div class="modal fade" id="titleListModal" tabindex="-1" aria-labelledby="titleListModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content aw-modal" id="titleListForm" data-url="{{ route('title-lists.quick') }}">
            <div class="modal-header">
                <h5 class="modal-title" id="titleListModalTitle"><i class="bi bi-card-list text-mint me-2"></i>{{ __('ui.new_title_list') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="alert aw-alert-err small d-none" data-error></div>
                <label class="form-label" for="tl_name">{{ __('ui.name') }}</label>
                <input class="form-control mb-3" id="tl_name" name="name" required maxlength="120" placeholder="{{ __('ui.title_list_name_ph') }}">
                <label class="form-label" for="tl_titles">{{ __('ui.titles') }}</label>
                <textarea class="form-control" id="tl_titles" name="titles" rows="10" required placeholder="{{ __('ui.titles_ph') }}" dir="auto"></textarea>
                <div class="form-text">{{ __('ui.titles_help') }} <span data-count>0</span></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                <button type="submit" class="btn aw-btn-primary">{{ __('ui.save') }}</button>
            </div>
        </form>
    </div>
</div>
