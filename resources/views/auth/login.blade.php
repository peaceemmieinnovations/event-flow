@extends('layouts.app')

@section('title', 'Log in')

@section('content')
    <section class="ef-auth" data-scroll-section>
        <div class="ef-container ef-auth__grid">
            <div class="ef-auth__intro" data-reveal>
                <p class="ef-eyebrow">Welcome back</p>
                <h1 class="ef-heading-xl">Your next experience starts here.</h1>
                <p class="ef-lead">Log in to keep EventFlow close at hand.</p>
            </div>

            <form class="ef-auth__form" method="POST" action="{{ route('login') }}" data-reveal>
                @csrf
                <div class="ef-auth__form-header">
                    <p class="ef-eyebrow">Account access</p>
                    <h2 class="ef-heading-md">Log in</h2>
                </div>

                @if ($errors->any())
                    <div class="ef-alert" data-tone="danger" role="alert">{{ $errors->first() }}</div>
                @endif

                <div class="ef-field">
                    <label for="email">Email address</label>
                    <input class="ef-input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                </div>
                <div class="ef-field">
                    <label for="password">Password</label>
                    <input class="ef-input" id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <label class="ef-auth__remember"><input name="remember" type="checkbox" value="1"> Remember me</label>
                <x-ui.button type="submit">Log in</x-ui.button>
                <p class="ef-meta">New to EventFlow? <a class="ef-link" href="{{ route('register') }}">Create an account</a>.</p>
            </form>
        </div>
    </section>
@endsection
