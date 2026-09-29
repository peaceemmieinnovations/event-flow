@extends('layouts.app')

@section('title', 'Create account')

@section('content')
    <section class="ef-auth" data-scroll-section>
        <div class="ef-container ef-auth__grid">
            <div class="ef-auth__intro" data-reveal>
                <p class="ef-eyebrow">Join EventFlow</p>
                <h1 class="ef-heading-xl">Make room for what moves you.</h1>
                <p class="ef-lead">Create an EventFlow account to begin your personal event journey.</p>
            </div>

            <form class="ef-auth__form" method="POST" action="{{ route('register') }}" data-reveal>
                @csrf
                <div class="ef-auth__form-header">
                    <p class="ef-eyebrow">Create account</p>
                    <h2 class="ef-heading-md">Your details</h2>
                </div>

                @if ($errors->any())
                    <div class="ef-alert" data-tone="danger" role="alert">{{ $errors->first() }}</div>
                @endif

                <div class="ef-field">
                    <label for="name">Name</label>
                    <input class="ef-input" id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required autofocus>
                </div>
                <div class="ef-field">
                    <label for="email">Email address</label>
                    <input class="ef-input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                </div>
                <div class="ef-field">
                    <label for="password">Password</label>
                    <input class="ef-input" id="password" name="password" type="password" autocomplete="new-password" required>
                </div>
                <div class="ef-field">
                    <label for="password_confirmation">Confirm password</label>
                    <input class="ef-input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                </div>
                <x-ui.button type="submit">Create account</x-ui.button>
                <p class="ef-meta">Already have an account? <a class="ef-link" href="{{ route('login') }}">Log in</a>.</p>
            </form>
        </div>
    </section>
@endsection
