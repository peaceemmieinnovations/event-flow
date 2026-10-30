@extends('layouts.admin')
@section('title', $mode === 'create' ? 'Create event' : 'Edit event')
@section('eyebrow', 'Event management')
@section('actions')
    <a class="ef-button ef-button--secondary" href="{{ route('admin.events.index') }}">Back to events</a>
@endsection

@section('content')
    <form
        class="ef-form ef-form--event"
        method="POST"
        enctype="multipart/form-data"
        action="{{ $mode === 'create' ? route('admin.events.store') : route('admin.events.update', $event) }}"
        data-pricing-form
    >
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <section class="ef-panel" aria-labelledby="basics-heading">
            <header class="ef-panel__header">
                <h2 class="ef-heading-md" id="basics-heading">The basics</h2>
                <p class="ef-meta">What attendees see first.</p>
            </header>

            <div class="ef-form__grid">
                <div class="ef-field ef-field--wide">
                    <label class="ef-label" for="title">Event title</label>
                    <input class="ef-input" id="title" name="title" type="text" required maxlength="255" value="{{ old('title', $event->title) }}" placeholder="Design Assembly 2026">
                    @error('title')<p class="ef-field__error">{{ $message }}</p>@enderror
                </div>

                <div class="ef-field">
                    <label class="ef-label" for="category">Category</label>
                    <select class="ef-select" id="category" name="category">
                        <option value="">Uncategorised</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected(old('category', $event->category) === $category)>{{ ucwords(str_replace('-', ' ', $category)) }}</option>
                        @endforeach
                    </select>
                    @error('category')<p class="ef-field__error">{{ $message }}</p>@enderror
                </div>

                <div class="ef-field">
                    <label class="ef-label" for="slug">URL slug</label>
                    <input class="ef-input" id="slug" name="slug" type="text" maxlength="255" value="{{ old('slug', $event->slug) }}" placeholder="auto-generated-from-title" data-slug-source="#title">
                    <p class="ef-field__hint">Leave blank to generate it from the title.</p>
                    @error('slug')<p class="ef-field__error">{{ $message }}</p>@enderror
                </div>

                <div class="ef-field ef-field--wide">
                    <label class="ef-label" for="description">Description</label>
                    <textarea class="ef-textarea" id="description" name="description" rows="6" required>{{ old('description', $event->description) }}</textarea>
                    <p class="ef-field__hint">Tell people what to expect, who it is for, and what to bring.</p>
                    @error('description')<p class="ef-field__error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="ef-panel" aria-labelledby="schedule-heading">
            <header class="ef-panel__header">
                <h2 class="ef-heading-md" id="schedule-heading">When and where</h2>
            </header>

            <div class="ef-form__grid">
                <div class="ef-field">
                    <label class="ef-label" for="event_date">Event date</label>
                    <input class="ef-input" id="event_date" name="event_date" type="date" required value="{{ old('event_date', $event->event_date?->format('Y-m-d')) }}">
                    @error('event_date')<p class="ef-field__error">{{ $message }}</p>@enderror
                </div>

                <div class="ef-field">
                    <label class="ef-label" for="start_time">Start time</label>
                    <input class="ef-input" id="start_time" name="start_time" type="time" value="{{ old('start_time', $event->start_time ? \Illuminate\Support\Carbon::parse($event->start_time)->format('H:i') : '') }}">
                    @error('start_time')<p class="ef-field__error">{{ $message }}</p>@enderror
                </div>

                <div class="ef-field">
                    <label class="ef-label" for="end_time">End time</label>
                    <input class="ef-input" id="end_time" name="end_time" type="time" value="{{ old('end_time', $event->end_time ? \Illuminate\Support\Carbon::parse($event->end_time)->format('H:i') : '') }}">
                    @error('end_time')<p class="ef-field__error">{{ $message }}</p>@enderror
                </div>

                <div class="ef-field">
                    <label class="ef-label" for="registration_deadline">Registration deadline</label>
                    <input class="ef-input" id="registration_deadline" name="registration_deadline" type="datetime-local" value="{{ old('registration_deadline', $event->registration_deadline?->format('Y-m-d\TH:i')) }}">
                    <p class="ef-field__hint">Optional. Blank means registration stays open until the event starts.</p>
                    @error('registration_deadline')<p class="ef-field__error">{{ $message }}</p>@enderror
                </div>

                <div class="ef-field ef-field--wide">
                    <label class="ef-label" for="location">Venue</label>
                    <input class="ef-input" id="location" name="location" type="text" required maxlength="255" value="{{ old('location', $event->location) }}">
                    @error('location')<p class="ef-field__error">{{ $message }}</p>@enderror
                </div>

                <div class="ef-field">
                    <label class="ef-label" for="address">Street address</label>
                    <input class="ef-input" id="address" name="address" type="text" maxlength="255" value="{{ old('address', $event->address) }}">
                </div>

                <div class="ef-field">
                    <label class="ef-label" for="city">City</label>
                    <input class="ef-input" id="city" name="city" type="text" maxlength="255" value="{{ old('city', $event->city) }}">
                </div>

                <div class="ef-field">
                    <label class="ef-label" for="state">State or region</label>
                    <input class="ef-input" id="state" name="state" type="text" maxlength="255" value="{{ old('state', $event->state) }}">
                </div>

                <div class="ef-field">
                    <label class="ef-label" for="country">Country</label>
                    <input class="ef-input" id="country" name="country" type="text" maxlength="255" value="{{ old('country', $event->country) }}">
                </div>
            </div>
        </section>

        <section class="ef-panel" aria-labelledby="organizer-heading">
            <header class="ef-panel__header">
                <h2 class="ef-heading-md" id="organizer-heading">Organizer contact</h2>
                <p class="ef-meta">Shown to attendees who need to reach you.</p>
            </header>

            <div class="ef-form__grid">
                <div class="ef-field">
                    <label class="ef-label" for="organizer_name">Organizer name</label>
                    <input class="ef-input" id="organizer_name" name="organizer_name" type="text" maxlength="255" value="{{ old('organizer_name', $event->organizer_name) }}">
                </div>

                <div class="ef-field">
                    <label class="ef-label" for="organizer_email">Organizer email</label>
                    <input class="ef-input" id="organizer_email" name="organizer_email" type="email" maxlength="255" value="{{ old('organizer_email', $event->organizer_email) }}">
                    @error('organizer_email')<p class="ef-field__error">{{ $message }}</p>@enderror
                </div>

                <div class="ef-field">
                    <label class="ef-label" for="organizer_phone">Organizer phone</label>
                    <input class="ef-input" id="organizer_phone" name="organizer_phone" type="tel" maxlength="40" value="{{ old('organizer_phone', $event->organizer_phone) }}">
                </div>
            </div>
        </section>

        <section class="ef-panel" aria-labelledby="pricing-heading">
            <header class="ef-panel__header">
                <h2 class="ef-heading-md" id="pricing-heading">Tickets and pricing</h2>
                <p class="ef-meta">Free events confirm instantly. Paid events verify the payment on the server before a ticket is issued.</p>
            </header>

            <div class="ef-form__grid">
                <div class="ef-field">
                    <label class="ef-label" for="event_type">Event type</label>
                    <select class="ef-select" id="event_type" name="event_type" data-pricing-type>
                        <option value="free" @selected(old('event_type', $event->event_type) === 'free')>Free</option>
                        <option value="paid" @selected(old('event_type', $event->event_type) === 'paid')>Paid</option>
                    </select>
                    @error('event_type')<p class="ef-field__error">{{ $message }}</p>@enderror
                </div>

                <div class="ef-field" data-pricing-field>
                    <label class="ef-label" for="price_amount">Ticket price</label>
                    <input class="ef-input" id="price_amount" name="price_amount" type="number" min="0" step="0.01" inputmode="decimal" value="{{ old('price_amount', $event->priceInMajorUnits() ?: '') }}" placeholder="0.00">
                    <p class="ef-field__hint">In whole {{ old('currency', $event->currency ?: 'NGN') }} units. The server converts and verifies this amount.</p>
                    @error('price_amount')<p class="ef-field__error">{{ $message }}</p>@enderror
                </div>

                <div class="ef-field" data-pricing-field>
                    <label class="ef-label" for="currency">Currency</label>
                    <select class="ef-select" id="currency" name="currency">
                        @foreach (['NGN' => 'Nigerian Naira (NGN)', 'USD' => 'US Dollar (USD)', 'GBP' => 'Pound Sterling (GBP)', 'EUR' => 'Euro (EUR)', 'GHS' => 'Ghanaian Cedi (GHS)'] as $code => $label)
                            <option value="{{ $code }}" @selected(old('currency', $event->currency ?: 'NGN') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="ef-field">
                    <label class="ef-label" for="capacity">Maximum capacity</label>
                    <input class="ef-input" id="capacity" name="capacity" type="number" min="1" max="1000000" value="{{ old('capacity', $event->capacity) }}">
                    <p class="ef-field__hint">Blank means unlimited. Capacity is enforced when a registration is created.</p>
                    @error('capacity')<p class="ef-field__error">{{ $message }}</p>@enderror
                </div>

                <div class="ef-field">
                    <label class="ef-label" for="status">Status</label>
                    <select class="ef-select" id="status" name="status">
                        @foreach (\App\Models\Event::STATUSES as $status)
                            <option value="{{ $status }}" @selected(old('status', $event->status) === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    @error('status')<p class="ef-field__error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="ef-panel" aria-labelledby="media-heading">
            <header class="ef-panel__header">
                <h2 class="ef-heading-md" id="media-heading">Cover image</h2>
            </header>

            <div class="ef-form__grid">
                <div class="ef-field ef-field--wide">
                    <label class="ef-label" for="image">Upload an image</label>
                    <input class="ef-input" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" data-image-preview-input data-image-preview-target="#image-preview">
                    <p class="ef-field__hint">JPEG, PNG or WebP up to 4&nbsp;MB, at least 400&times;250 pixels. Leave empty to keep the current image.</p>
                    <div class="ef-field--wide" id="image-preview" data-image-preview hidden></div>
                    @error('image')<p class="ef-field__error">{{ $message }}</p>@enderror
                </div>

                @if ($event->image)
                    <div class="ef-field ef-field--wide">
                        <p class="ef-label">Current image</p>
                        <img class="ef-media-preview" src="{{ \Illuminate\Support\Str::startsWith($event->image, ['http://', 'https://', '/']) ? $event->image : asset('storage/'.$event->image) }}" alt="" width="320" height="180" loading="lazy">
                    </div>
                @endif
            </div>
        </section>

        <div class="ef-form__actions">
            <x-ui.button type="submit">{{ $mode === 'create' ? 'Create event' : 'Save changes' }}</x-ui.button>
            <a class="ef-button ef-button--secondary" href="{{ route('admin.events.index') }}">Cancel</a>
        </div>
    </form>
@endsection
