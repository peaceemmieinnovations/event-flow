@extends('layouts.app')

@section('title', 'Host an event')

@section('content')
    <section class="ef-host-hero ef-section">
        <div class="ef-container ef-host-hero__grid">
            <div>
                <p class="ef-eyebrow">For event hosts</p>
                <h1 class="ef-heading-xl">Bring your next gathering to life.</h1>
                <p class="ef-lead">Submit your event in a few minutes. Our team reviews every listing before it goes live, keeping EventFlow useful and trusted.</p>
            </div>
            <aside class="ef-host-hero__note">
                <span class="ef-host-hero__number">3-10%</span>
                <p>Service fee on paid tickets only. Free events stay free to list.</p>
            </aside>
        </div>
    </section>

    <section class="ef-section ef-section--compact">
        <div class="ef-container">
            <form class="ef-form ef-form--event" method="POST" enctype="multipart/form-data" action="{{ route('host.events.store') }}" data-pricing-form>
                @csrf
                <div class="ef-host-review-banner">
                    <strong>Every event is reviewed before publishing.</strong>
                    <span>You'll receive an update after the EventFlow team has checked your details.</span>
                </div>

                <section class="ef-panel">
                    <header class="ef-panel__header"><h2 class="ef-heading-md">Tell people what's happening</h2><p class="ef-meta">The essentials attendees see first.</p></header>
                    <div class="ef-form__grid">
                        <div class="ef-field ef-field--wide">
                            <label class="ef-label" for="title">Event title</label>
                            <input class="ef-input" id="title" name="title" required maxlength="255" value="{{ old('title', $event->title) }}" placeholder="Lagos Creators Meetup">
                            @error('title')<p class="ef-field__error">{{ $message }}</p>@enderror
                        </div>
                        <div class="ef-field">
                            <label class="ef-label" for="category">Category</label>
                            <select class="ef-select" id="category" name="category" required>
                                <option value="">Choose a category</option>
                                @foreach ($categories as $category)<option value="{{ $category }}" @selected(old('category') === $category)>{{ ucwords(str_replace('-', ' ', $category)) }}</option>@endforeach
                            </select>
                            @error('category')<p class="ef-field__error">{{ $message }}</p>@enderror
                        </div>
                        <div class="ef-field ef-field--wide">
                            <label class="ef-label" for="description">Description</label>
                            <textarea class="ef-textarea" id="description" name="description" rows="6" required placeholder="What should guests expect?">{{ old('description') }}</textarea>
                            @error('description')<p class="ef-field__error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section class="ef-panel">
                    <header class="ef-panel__header"><h2 class="ef-heading-md">When and where</h2></header>
                    <div class="ef-form__grid">
                        <div class="ef-field"><label class="ef-label" for="event_date">Date</label><input class="ef-input" id="event_date" name="event_date" type="date" required value="{{ old('event_date') }}">@error('event_date')<p class="ef-field__error">{{ $message }}</p>@enderror</div>
                        <div class="ef-field"><label class="ef-label" for="start_time">Start time</label><input class="ef-input" id="start_time" name="start_time" type="time" value="{{ old('start_time') }}"></div>
                        <div class="ef-field"><label class="ef-label" for="end_time">End time</label><input class="ef-input" id="end_time" name="end_time" type="time" value="{{ old('end_time') }}">@error('end_time')<p class="ef-field__error">{{ $message }}</p>@enderror</div>
                        <div class="ef-field ef-field--wide"><label class="ef-label" for="location">Venue name</label><input class="ef-input" id="location" name="location" required value="{{ old('location') }}" placeholder="The venue or online platform">@error('location')<p class="ef-field__error">{{ $message }}</p>@enderror</div>
                        <div class="ef-field"><label class="ef-label" for="city">City</label><input class="ef-input" id="city" name="city" required value="{{ old('city') }}">@error('city')<p class="ef-field__error">{{ $message }}</p>@enderror</div>
                        <div class="ef-field"><label class="ef-label" for="state">State / region</label><input class="ef-input" id="state" name="state" value="{{ old('state') }}"></div>
                        <div class="ef-field"><label class="ef-label" for="country">Country</label><input class="ef-input" id="country" name="country" value="{{ old('country', 'Nigeria') }}"></div>
                        <div class="ef-field ef-field--wide"><label class="ef-label" for="address">Street address</label><input class="ef-input" id="address" name="address" value="{{ old('address') }}"></div>
                    </div>
                </section>

                <section class="ef-panel">
                    <header class="ef-panel__header"><h2 class="ef-heading-md">Tickets and host contact</h2><p class="ef-meta">We deduct a 3-10% service fee from successfully paid tickets, based on the event plan.</p></header>
                    <div class="ef-form__grid">
                        <div class="ef-field"><label class="ef-label" for="event_type">Event type</label><select class="ef-select" id="event_type" name="event_type" data-pricing-type><option value="free" @selected(old('event_type', 'free') === 'free')>Free</option><option value="paid" @selected(old('event_type') === 'paid')>Paid</option></select></div>
                        <div class="ef-field" data-pricing-field><label class="ef-label" for="price_amount">Ticket price</label><input class="ef-input" id="price_amount" name="price_amount" type="number" min="0.01" step="0.01" value="{{ old('price_amount') }}" placeholder="0.00">@error('price_amount')<p class="ef-field__error">{{ $message }}</p>@enderror</div>
                        <div class="ef-field" data-pricing-field><label class="ef-label" for="currency">Currency</label><select class="ef-select" id="currency" name="currency"><option value="NGN" @selected(old('currency', 'NGN') === 'NGN')>Nigerian Naira (NGN)</option><option value="USD" @selected(old('currency') === 'USD')>US Dollar (USD)</option></select></div>
                        <div class="ef-field"><label class="ef-label" for="capacity">Maximum capacity</label><input class="ef-input" id="capacity" name="capacity" type="number" min="1" value="{{ old('capacity') }}" placeholder="Optional"></div>
                        <div class="ef-field"><label class="ef-label" for="organizer_name">Host name</label><input class="ef-input" id="organizer_name" name="organizer_name" required value="{{ old('organizer_name', $event->organizer_name) }}"></div>
                        <div class="ef-field"><label class="ef-label" for="organizer_email">Host email</label><input class="ef-input" id="organizer_email" name="organizer_email" type="email" required value="{{ old('organizer_email', $event->organizer_email) }}"></div>
                        <div class="ef-field"><label class="ef-label" for="organizer_phone">Host phone</label><input class="ef-input" id="organizer_phone" name="organizer_phone" type="tel" value="{{ old('organizer_phone') }}"></div>
                        <div class="ef-field"><label class="ef-label" for="registration_deadline">Registration deadline</label><input class="ef-input" id="registration_deadline" name="registration_deadline" type="datetime-local" value="{{ old('registration_deadline') }}"></div>
                    </div>
                </section>

                <section class="ef-panel">
                    <header class="ef-panel__header"><h2 class="ef-heading-md">Add a cover image</h2><p class="ef-meta">Use a clear image at least 400 x 250 pixels.</p></header>
                    <div class="ef-field"><label class="ef-label" for="image">Event image</label><input class="ef-input" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">@error('image')<p class="ef-field__error">{{ $message }}</p>@enderror</div>
                </section>

                <div class="ef-form__actions"><x-ui.button type="submit">Submit for review</x-ui.button><a class="ef-button ef-button--secondary" href="{{ route('events.index') }}">Cancel</a></div>
            </form>
        </div>
    </section>
@endsection
