@extends('layouts.app')
@section('title', $service->exists ? $service->name : __('ui.new_service'))

@php
    $times = old('schedule_times', $service->schedule_times ?: ['09:00']);
    $type = old('schedule_type', $service->schedule_type ?: 'daily');
    $days = array_map('intval', old('schedule_days', $service->schedule_days ?? []));
    $imgs = array_map('intval', old('images', $service->imageSizeIds()));
    $imageCount = (int) old('image_count', count($imgs));
    $selDest = array_map('intval', old('destinations', $selectedDestinations));
    $cats = old('categories', implode("\n", $service->categories ?? []));
    $weekdays = [0 => __('ui.sun'), 1 => __('ui.mon'), 2 => __('ui.tue'), 3 => __('ui.wed'), 4 => __('ui.thu'), 5 => __('ui.fri'), 6 => __('ui.sat')];
@endphp

@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0 me-auto"><i class="bi bi-lightning-charge text-amber me-2"></i>{{ $service->exists ? __('ui.edit_service') : __('ui.new_service') }}</h1>
    @if ($service->exists)
        {{ icon_btn('eye', __('ui.view'), 'blue', 'href="'.route('services.show', $service).'"', 'a') }}
    @endif
</div>

<form method="post" action="{{ $service->exists ? route('services.update', $service) : route('services.store') }}" id="serviceForm">
    @csrf
    @if ($service->exists) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-xl-6">
            {{-- 1. General --}}
            <div class="card aw-card mb-3">
                <div class="card-header aw-card-header"><span class="aw-step tone-violet">1</span>{{ __('ui.general') }}</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="name">{{ __('ui.name') }}</label>
                        <input class="form-control" id="name" name="name" value="{{ old('name', $service->name) }}" required maxlength="120" placeholder="{{ __('ui.service_name_ph') }}">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $service->is_active))>
                        <label class="form-check-label" for="is_active">{{ __('ui.service_active') }}</label>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="language">{{ __('ui.article_language') }}</label>
                            <select class="form-select" id="language" name="language">
                                @foreach (config('languages.articles') as $code => [$en, $native])
                                    <option value="{{ $code }}" @selected(old('language', $service->language) === $code)>{{ $native }}{{ $native !== $en ? ' — '.$en : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="word_count">{{ __('ui.word_count') }}</label>
                            <div class="input-group aw-num-group">
                                <input class="form-control" type="number" id="word_count" name="word_count" min="100" max="6000" step="50" value="{{ old('word_count', $service->word_count) }}" required>
                                <span class="input-group-text">{{ __('ui.words') }}</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="tone_id">{{ __('ui.tone') }}</label>
                            <select class="form-select" id="tone_id" name="tone_id">
                                <option value="">{{ __('ui.tone_default') }}</option>
                                @foreach ($tones as $tone)
                                    <option value="{{ $tone->id }}" @selected((int) old('tone_id', $service->tone_id) === $tone->id)>{{ $tone->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label" for="instructions">{{ __('ui.extra_instructions') }} <span class="text-muted small">({{ __('ui.optional') }})</span></label>
                        <textarea class="form-control" id="instructions" name="instructions" rows="3" maxlength="3000" placeholder="{{ __('ui.extra_instructions_ph') }}">{{ old('instructions', $service->instructions) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- 2. Titles and categories --}}
            <div class="card aw-card mb-3">
                <div class="card-header aw-card-header"><span class="aw-step tone-mint">2</span>{{ __('ui.titles_categories') }}</div>
                <div class="card-body">
                    <label class="form-label" for="title_list_id">{{ __('ui.title_list') }}</label>
                    <div class="d-flex gap-2 mb-1">
                        <select class="form-select" id="title_list_id" name="title_list_id" required>
                            <option value="">{{ __('ui.choose') }}</option>
                            @foreach ($titleLists as $list)
                                <option value="{{ $list->id }}" @selected((int) old('title_list_id', $service->title_list_id) === $list->id)>{{ $list->name }} ({{ $list->titles_count }})</option>
                            @endforeach
                        </select>
                        {{ icon_btn('plus', __('ui.new_title_list'), 'green', 'data-bs-toggle="modal" data-bs-target="#titleListModal"') }}
                    </div>
                    <div class="form-text mb-3">{{ __('ui.title_list_help') }}</div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="reuse_titles" name="reuse_titles" value="1" @checked(old('reuse_titles', $service->reuse_titles))>
                        <label class="form-check-label" for="reuse_titles">{{ __('ui.reuse_titles') }}</label>
                    </div>
                    <label class="form-label" for="categories">{{ __('ui.categories') }}</label>
                    <textarea class="form-control" id="categories" name="categories" rows="4" placeholder="{{ __('ui.categories_ph') }}">{{ $cats }}</textarea>
                    <div class="form-text">{{ __('ui.categories_help') }}</div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            {{-- 3. AI and images --}}
            <div class="card aw-card mb-3">
                <div class="card-header aw-card-header"><span class="aw-step tone-sky">3</span>{{ __('ui.ai_and_images') }}</div>
                <div class="card-body">
                    <label class="form-label" for="text_agent_id">{{ __('ui.text_agent') }}</label>
                    <select class="form-select mb-3" id="text_agent_id" name="text_agent_id" required>
                        @forelse ($textAgents as $a)
                            <option value="{{ $a->id }}" @selected((int) old('text_agent_id', $service->text_agent_id) === $a->id)>{{ $a->name }} — {{ $a->priceLabel() }}</option>
                        @empty
                            <option value="">{{ __('ui.no_agents') }}</option>
                        @endforelse
                    </select>

                    <div class="row g-3 align-items-end">
                        <div class="col-sm-5">
                            <label class="form-label" for="image_count">{{ __('ui.image_count') }}</label>
                            <select class="form-select" id="image_count" name="image_count">
                                @for ($i = 0; $i <= \App\Models\Service::MAX_IMAGES; $i++)
                                    <option value="{{ $i }}" @selected($imageCount === $i)>{{ $i === 0 ? __('ui.no_images') : $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-sm-7 aw-if-images">
                            <label class="form-label" for="image_agent_id">{{ __('ui.image_agent') }}</label>
                            <select class="form-select" id="image_agent_id" name="image_agent_id">
                                @forelse ($imageAgents as $a)
                                    <option value="{{ $a->id }}" @selected((int) old('image_agent_id', $service->image_agent_id) === $a->id)>{{ $a->name }} — {{ $a->priceLabel() }}</option>
                                @empty
                                    <option value="">{{ __('ui.no_agents') }}</option>
                                @endforelse
                            </select>
                        </div>
                    </div>
                    <div id="imageRows" class="aw-image-rows mt-3 aw-if-images"
                         data-sizes='@json($imageSizes->map(fn ($s) => ['id' => $s->id, 'label' => $s->label(), 'group' => $s->group]))'
                         data-values='@json($imgs)'
                         data-label="{{ __('ui.image_n') }}" data-cover="{{ __('ui.cover') }}"></div>
                </div>
            </div>

            {{-- 4. Schedule --}}
            <div class="card aw-card mb-3">
                <div class="card-header aw-card-header"><span class="aw-step tone-amber">4</span>{{ __('ui.schedule') }}</div>
                <div class="card-body">
                    <label class="form-label">{{ __('ui.run_times') }}</label>
                    <div id="timeRows" class="d-flex flex-wrap gap-2 mb-1">
                        @foreach ($times as $t)
                            <div class="aw-time-row input-group">
                                <input type="time" class="form-control" name="schedule_times[]" value="{{ $t }}" required dir="ltr">
                                {{ icon_btn('trash', __('ui.remove_time'), 'red', 'data-remove-time') }}
                            </div>
                        @endforeach
                        {{ icon_btn('plus', __('ui.add_time'), 'green', 'id="addTime"') }}
                    </div>
                    <div class="form-text mb-3">{{ __('ui.run_times_help') }}</div>

                    <label class="form-label">{{ __('ui.repeat') }}</label>
                    <div class="aw-segbar mb-3" role="radiogroup">
                        @foreach (['daily', 'weekly', 'monthly'] as $t)
                            <input type="radio" name="schedule_type" id="st_{{ $t }}" value="{{ $t }}" @checked($type === $t)>
                            <label for="st_{{ $t }}">{{ __('ui.repeat_'.$t) }}</label>
                        @endforeach
                    </div>

                    <div class="aw-days" data-for="weekly">
                        <div class="d-flex flex-wrap gap-1">
                            @foreach ($weekdays as $n => $label)
                                <input type="checkbox" class="btn-check" name="schedule_days[]" value="{{ $n }}" id="wd{{ $n }}" @checked($type === 'weekly' && in_array($n, $days, true)) autocomplete="off">
                                <label class="btn aw-chip" for="wd{{ $n }}">{{ $label }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div class="aw-days" data-for="monthly">
                        <div class="aw-month-grid">
                            @for ($d = 1; $d <= 31; $d++)
                                <input type="checkbox" class="btn-check" name="schedule_days[]" value="{{ $d }}" id="md{{ $d }}" @checked($type === 'monthly' && in_array($d, $days, true)) autocomplete="off">
                                <label class="btn aw-chip" for="md{{ $d }}">{{ $d }}</label>
                            @endfor
                        </div>
                        <div class="form-text">{{ __('ui.month_days_help') }}</div>
                    </div>

                    <label class="form-label mt-3" for="timezone">{{ __('ui.timezone') }}</label>
                    <select class="form-select" id="timezone" name="timezone">
                        @foreach (DateTimeZone::listIdentifiers() as $tz)
                            <option value="{{ $tz }}" @selected(old('timezone', $service->timezone) === $tz)>{{ $tz }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- 5. Delivery --}}
            <div class="card aw-card mb-3">
                <div class="card-header aw-card-header"><span class="aw-step tone-rose">5</span>{{ __('ui.delivery') }}</div>
                <div class="card-body">
                    <label class="form-label">{{ __('ui.destinations') }}</label>
                    @if ($destinations->isEmpty())
                        <p class="small text-muted mb-2">{{ __('ui.no_destinations_short') }}</p>
                    @else
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            @foreach ($destinations as $d)
                                <input type="checkbox" class="btn-check" name="destinations[]" value="{{ $d->id }}" id="dest{{ $d->id }}" @checked(in_array($d->id, $selDest, true)) autocomplete="off">
                                <label class="btn aw-chip {{ $d->is_active ? '' : 'opacity-50' }}" for="dest{{ $d->id }}"><i class="bi {{ $d->publisher()->icon() }} me-1"></i>{{ $d->name }}</label>
                            @endforeach
                        </div>
                    @endif
                    <a class="small" href="{{ route('destinations.index') }}" target="_blank"><i class="bi bi-plus-circle"></i> {{ __('ui.manage_destinations') }}</a>

                    <hr>
                    <label class="form-label d-flex align-items-center gap-2" for="webhook_url">
                        {{ __('ui.webhook_url') }} <span class="text-muted small">({{ __('ui.optional') }})</span>
                        <button type="button" class="btn btn-link p-0 aw-help" data-bs-toggle="modal" data-bs-target="#webhookHelp" title="{{ __('ui.webhook_help_title') }}" aria-label="{{ __('ui.webhook_help_title') }}">{{ icon('help', 18) }}</button>
                    </label>
                    <input class="form-control" type="url" id="webhook_url" name="webhook_url" value="{{ old('webhook_url', $service->webhook_url) }}" placeholder="https://example.com/article-webhook" dir="ltr">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="webhook_embed_images" name="webhook_embed_images" value="1" @checked(old('webhook_embed_images', $service->webhook_embed_images))>
                        <label class="form-check-label" for="webhook_embed_images">{{ __('ui.embed_images') }}</label>
                    </div>

                    {{-- One sentence with small number boxes: "Retry [5] times (max 10), wait [3] minutes between tries." --}}
                    <label class="form-label mt-3" for="retry_limit">{{ __('ui.retry_label') }}</label>
                    <div class="aw-inline-nums">
                        <input class="form-control aw-num" type="number" id="retry_limit" name="retry_limit" min="0" max="{{ \App\Models\Service::MAX_RETRIES }}" value="{{ old('retry_limit', $service->retry_limit) }}" required>
                        <span>{{ __('ui.retry_times', ['max' => \App\Models\Service::MAX_RETRIES]) }}</span>
                        <input class="form-control aw-num" type="number" id="retry_delay" name="retry_delay" min="1" max="1440" value="{{ old('retry_delay', $service->retry_delay) }}" required aria-label="{{ __('ui.retry_delay') }}">
                        <span>{{ __('ui.retry_minutes') }}</span>
                    </div>
                    <div class="form-text">{{ __('ui.retry_help') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="aw-savebar">
        <button class="btn aw-btn-primary px-4" type="submit"><i class="bi bi-check2 me-1"></i>{{ __('ui.save') }}</button>
        <a class="btn btn-light border" href="{{ $service->exists ? route('services.show', $service) : route('services.index') }}">{{ __('ui.cancel') }}</a>
    </div>
</form>
@endsection

@push('modals')
    @include('services._title-list-modal')
    @include('services._webhook-help')
@endpush
