@php
    $messages = array_filter([
        'success' => session('status'),
        'danger' => session('error'),
    ]);
@endphp

@if ($messages !== [])
    <div class="ef-flash" role="status" aria-live="polite">
        @foreach ($messages as $tone => $message)
            <p class="ef-alert" data-tone="{{ $tone }}">{{ $message }}</p>
        @endforeach
    </div>
@endif

@if ($errors->any() && $errors->count() < 6)
    <div class="ef-flash" role="alert">
        <div class="ef-alert" data-tone="danger">
            <p><strong>{{ trans_choice('There is :count problem with this form.|There are :count problems with this form.', $errors->count(), ['count' => $errors->count()]) }}</strong></p>
            <ul class="ef-alert__list">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@elseif ($errors->any())
    <div class="ef-flash" role="alert">
        <div class="ef-alert" data-tone="danger">
            <p><strong>{{ trans_choice('There is :count problem with this form.|There are :count problems with this form.', $errors->count(), ['count' => $errors->count()]) }}</strong></p>
        </div>
    </div>
@endif