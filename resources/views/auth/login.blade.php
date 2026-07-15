@extends('layouts.auth')
@php
    if (!isset($settings)) { $settings = \App\Models\Utility::settings(); }
@endphp
@section('page-title')
    {{ __('Login') }}
@endsection
@section('language-bar')
    @php
        $languages = App\Models\Utility::languages();

        $lang = \App::getLocale('lang');
        $LangName = \App\Models\Languages::where('code', $lang)->first();
        if (empty($LangName)) {
            $LangName = new App\Models\Utility();
            $LangName->fullName = 'English';
        }

        $settings = App\Models\Utility::settings();
        config([
            'captcha.sitekey' => $settings['google_recaptcha_key'],
            'captcha.secret' => $settings['google_recaptcha_secret'],
            'options' => [
                'timeout' => 30,
            ],
        ]);
    @endphp
    <div class="lang-dropdown-only-desk">
        <li class="dropdown dash-h-item drp-language">
            <a class="dash-head-link dropdown-toggle btn" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="drp-text"> {{ ucfirst($LangName->fullName) }}
                </span>
            </a>
            <div class="dropdown-menu dash-h-dropdown dropdown-menu-end">
                @foreach ($languages as $code => $language)
                    <a href="{{ route('login', $code) }}" tabindex="0"
                        class="dropdown-item {{ $code == $lang ? 'active' : '' }}">
                        <span>{{ ucFirst($language) }}</span>
                    </a>
                @endforeach
            </div>
        </li>
    </div>
@endsection

@if ($settings['cust_darklayout'] == 'on')
    <style>
        .g-recaptcha {
            filter: invert(1) hue-rotate(180deg) !important;
        }
    </style>
@endif


@section('content')
    @php
        $company_logo = \App\Models\Utility::GetLogo();
        $logo_base = rtrim(\App\Models\Utility::get_file('uploads/logo'), '/');
        $logo_src = $logo_base . '/' . (!empty($company_logo) ? $company_logo : 'logo-dark.png') . '?' . time();
    @endphp
    <style>
        .jl-card { padding: 2.75rem 2.75rem 2rem; position:relative; }
        .jl-card::before { content:""; position:absolute; top:0; left:0; right:0; height:5px; background:linear-gradient(90deg,#1e40af 0%,#2563eb 50%,#3b82f6 100%); border-radius:20px 20px 0 0; }
        .jl-head { text-align:left; margin-bottom:2rem; }
        .jl-eyebrow { display:inline-flex; align-items:center; gap:.4rem; font-size:.7rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#1e40af; background:#eff6ff; padding:.4rem .9rem; border-radius:99px; margin-bottom:1.25rem; border:1px solid #bfdbfe; }
        .jl-title { font-size:1.85rem; font-weight:800; color:#0f172a; letter-spacing:-.025em; margin:0 0 .5rem; line-height:1.2; }
        .jl-subtitle { font-size:.92rem; color:#64748b; margin:0; line-height:1.5; }

        .jl-field { margin-bottom:1.15rem; }
        .jl-label { display:flex; align-items:center; justify-content:space-between; font-size:.78rem; font-weight:600; color:#374151; margin-bottom:.5rem; letter-spacing:.005em; }
        .jl-label .jl-hint { font-size:.72rem; font-weight:500; color:#94a3b8; }
        .jl-input-wrap { position:relative; }
        .jl-input-wrap > i.jl-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:1.15rem; pointer-events:none; transition:color .2s; z-index:2; }
        .jl-input { width:100%; padding:.95rem 1rem .95rem 2.85rem; border:1.5px solid #e5e7eb; border-radius:12px; font-size:.95rem; color:#0f172a; background:#fafbfc; outline:none; transition:all .2s; font-family:inherit; box-sizing:border-box; }
        .jl-input::placeholder { color:#cbd5e1; font-weight:400; }
        .jl-input:hover { border-color:#cbd5e1; background:#fff; }
        .jl-input:focus { border-color:#2563eb; background:#fff; box-shadow:0 0 0 4px rgba(37,99,235,.12); }
        .jl-input-wrap:focus-within > i.jl-icon { color:#2563eb; }
        .jl-input.is-invalid { border-color:#ef4444; background:#fef2f2; }
        .jl-input.is-invalid:focus { box-shadow:0 0 0 4px rgba(239,68,68,.12); }
        .jl-pwd-toggle { position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#94a3b8; padding:.5rem; display:flex; align-items:center; justify-content:center; border-radius:8px; transition:all .2s; z-index:2; }
        .jl-pwd-toggle:hover { color:#2563eb; background:#eff6ff; }

        .jl-row { display:flex; align-items:center; justify-content:space-between; margin:0 0 1.6rem; flex-wrap:wrap; gap:.75rem; }
        .jl-check { display:inline-flex; align-items:center; gap:.55rem; cursor:pointer; user-select:none; font-size:.86rem; color:#475569; font-weight:500; }
        .jl-check input { width:17px; height:17px; accent-color:#2563eb; cursor:pointer; margin:0; }
        .jl-forgot { font-size:.86rem; font-weight:600; color:#2563eb; text-decoration:none; transition:color .2s; }
        .jl-forgot:hover { color:#1e40af; text-decoration:underline; }

        .jl-submit { width:100%; background:linear-gradient(135deg,#2563eb 0%,#1e40af 100%); color:#fff; border:none; border-radius:12px; padding:1rem; font-size:.95rem; font-weight:700; cursor:pointer; font-family:inherit; letter-spacing:.01em; display:flex; align-items:center; justify-content:center; gap:.55rem; box-shadow:0 10px 25px -8px rgba(37,99,235,.55), inset 0 1px 0 rgba(255,255,255,.18); transition:transform .15s, box-shadow .25s, opacity .2s; position:relative; overflow:hidden; }
        .jl-submit::before { content:""; position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg,transparent,rgba(255,255,255,.25),transparent); transition:left .6s; }
        .jl-submit:hover::before { left:100%; }
        .jl-submit:hover { transform:translateY(-2px); box-shadow:0 16px 32px -8px rgba(37,99,235,.65), inset 0 1px 0 rgba(255,255,255,.18); }
        .jl-submit:active { transform:translateY(0); }
        .jl-submit:disabled { opacity:.7; cursor:not-allowed; transform:none; }
        .jl-submit i { transition:transform .25s; }
        .jl-submit:hover i { transform:translateX(4px); }

        .jl-divider { display:flex; align-items:center; gap:.85rem; margin:1.6rem 0 1.25rem; color:#94a3b8; font-size:.72rem; font-weight:600; letter-spacing:.08em; text-transform:uppercase; }
        .jl-divider::before, .jl-divider::after { content:""; flex:1; height:1px; background:linear-gradient(90deg,transparent,#e5e7eb,transparent); }

        .jl-social { display:grid; grid-template-columns:1fr 1fr; gap:.65rem; margin-bottom:1.5rem; }
        .jl-social-btn { display:inline-flex; align-items:center; justify-content:center; gap:.5rem; padding:.7rem; background:#fff; border:1.5px solid #e5e7eb; border-radius:11px; font-size:.85rem; font-weight:600; color:#475569; cursor:pointer; text-decoration:none; transition:all .2s; font-family:inherit; }
        .jl-social-btn:hover { border-color:#cbd5e1; background:#f8fafc; transform:translateY(-1px); box-shadow:0 4px 12px rgba(15,23,42,.06); }
        .jl-social-btn svg { width:18px; height:18px; }

        .jl-foot { text-align:center; font-size:.88rem; color:#64748b; margin:0; padding-top:.5rem; border-top:1px dashed #e5e7eb; padding-top:1.25rem; margin-top:.5rem; }
        .jl-foot a { color:#2563eb; font-weight:700; text-decoration:none; transition:color .2s; }
        .jl-foot a:hover { color:#1e40af; text-decoration:underline; }

        .jl-trust { display:flex; align-items:center; justify-content:center; gap:1.25rem; margin-top:1rem; }
        .jl-trust-item { display:inline-flex; align-items:center; gap:.3rem; font-size:.7rem; color:#94a3b8; font-weight:500; }
        .jl-trust-item i { color:#10b981; font-size:.85rem; }

        .jl-err { font-size:.75rem; color:#ef4444; margin-top:.4rem; display:flex; align-items:center; gap:.3rem; font-weight:500; }
        @keyframes jl-spin { to { transform:rotate(360deg); } }
        .jl-spin { animation:jl-spin .8s linear infinite; }
        @media (max-width:480px) { .jl-card { padding:2rem 1.5rem 1.5rem; } .jl-title { font-size:1.55rem; } }
    </style>
    <div class="jl-card">
        <span class="jl-eyebrow"><i class="ti ti-shield-lock"></i> {{ __('Secure Sign In') }}</span>
        <h1 class="jl-title">{{ __('Welcome back') }} 👋</h1>
        <p class="jl-subtitle">{{ __('Sign in to your') }} {{ \App\Models\Utility::getValByName('title_text') ?: config('app.name') }} {{ __('account to continue.') }}</p>

        <form method="POST" action="{{ route('login') }}" class="login-form" novalidate>
            @csrf

            <div class="jl-field">
                <label class="jl-label" for="email">{{ __('Work Email') }}</label>
                <div class="jl-input-wrap">
                    <input id="email" type="email" name="email"
                        class="jl-input @error('email') is-invalid @enderror"
                        placeholder="{{ __('you@company.com') }}"
                        value="{{ old('email') }}" required autofocus autocomplete="email">
                    <i class="ti ti-mail jl-icon"></i>
                </div>
                @error('email')
                    <div class="jl-err"><i class="ti ti-alert-circle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="jl-field">
                <label class="jl-label" for="password">{{ __('Password') }}</label>
                <div class="jl-input-wrap">
                    <input id="password" type="password" name="password"
                        class="jl-input @error('password') is-invalid @enderror"
                        placeholder="{{ __('Enter your password') }}"
                        required autocomplete="current-password" style="padding-right:3rem;">
                    <i class="ti ti-lock jl-icon"></i>
                    <button type="button" class="jl-pwd-toggle" aria-label="Toggle password" onclick="
                        var i=document.getElementById('password');
                        var ic=this.querySelector('i');
                        if(i.type==='password'){i.type='text';ic.className='ti ti-eye-off';}
                        else{i.type='password';ic.className='ti ti-eye';}
                    "><i class="ti ti-eye"></i></button>
                </div>
                @error('password')
                    <div class="jl-err"><i class="ti ti-alert-circle"></i> {{ $message }}</div>
                @enderror
            </div>

            <div class="jl-row">
                <label class="jl-check">
                    <input type="checkbox" name="remember" id="remember">
                    <span>{{ __('Remember me') }}</span>
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request', $lang) }}" class="jl-forgot">{{ __('Forgot password?') }}</a>
                @endif
            </div>

            @if (isset($settings['recaptcha_module']) && $settings['recaptcha_module'] == 'yes')
                @if (isset($settings['google_recaptcha_version']) && $settings['google_recaptcha_version'] == 'v2-checkbox')
                    <div class="jl-field" style="display:flex;justify-content:center;">
                        {!! NoCaptcha::display() !!}
                        @error('g-recaptcha-response')
                            <div class="jl-err"><i class="ti ti-alert-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>
                @else
                    <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response">
                    @error('g-recaptcha-response')
                        <div class="jl-err mb-2"><i class="ti ti-alert-circle"></i> {{ $message }}</div>
                    @enderror
                @endif
            @endif

            <button class="jl-submit login-submit-btn" type="submit">
                <span class="jl-submit-text">{{ __('Sign In') }}</span>
                <i class="ti ti-arrow-right"></i>
            </button>
        </form>

        @if (App\Models\Utility::getValByName('disable_signup_button') != 'on')
            <div class="jl-divider">{{ __('OR') }}</div>
            <p class="jl-foot">
                {{ __("Don't have an account?") }}
                <a href="{{ route('register', $lang) }}">{{ __('Create one — it\'s free') }}</a>
            </p>
        @endif
    </div>
    <script>
        // Loading state on submit
        (function(){
            var f = document.querySelector('.login-form');
            if(!f) return;
            f.addEventListener('submit', function(){
                var btn = f.querySelector('.jl-submit');
                if(!btn) return;
                btn.disabled = true;
                btn.innerHTML = '<i class="ti ti-loader-2 jl-spin"></i> {{ __("Signing in...") }}';
            });
        })();
    </script>
@endsection
@push('custom-scripts')
    <script>
    (function() {
        var blockedScriptUrls = ["envato.appbusket.com/license.js", "envato.workdo.io/verify.js"];
        document.querySelectorAll("script").forEach(function(script) {
            var src = (script.src || script.innerText || "").toString();
            if (blockedScriptUrls.some(function(url) { return src.indexOf(url) !== -1; })) {
                script.remove();
            }
        });
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(m) {
                [].slice.call(m.addedNodes).forEach(function(node) {
                    if (node.tagName === "SCRIPT") {
                        var s = (node.src || node.innerText || "").toString();
                        if (blockedScriptUrls.some(function(url) { return s.indexOf(url) !== -1; })) node.remove();
                    }
                });
            });
        });
        observer.observe(document.documentElement || document.body, { childList: true, subtree: true });
    })();
    </script>
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $(".login-form").on("submit", function() {
                $(this).find(".login-submit-btn").prop("disabled", true).addClass("loading");
            });
        });
    </script>
    


    @if (isset($settings['recaptcha_module']) && $settings['recaptcha_module'] == 'yes')
        @if (isset($settings['google_recaptcha_version']) && $settings['google_recaptcha_version'] == 'v2-checkbox')
            {!! NoCaptcha::renderJs() !!}
        @else
            <script src="https://www.google.com/recaptcha/api.js?render={{ $settings['google_recaptcha_key'] }}"></script>
            <div id="login-recaptcha-config" class="d-none" data-key="{{ $settings['google_recaptcha_key'] ?? '' }}"></div>
            <script>
                $(document).ready(function() {
                    var recaptchaKey = document.getElementById('login-recaptcha-config').getAttribute('data-key');
                    grecaptcha.ready(function() {
                        grecaptcha.execute(recaptchaKey, {
                            action: 'submit'
                        }).then(function(token) {
                            $('#g-recaptcha-response').val(token);
                        });
                    });
                });
            </script>
        @endif
    @endif

@endpush
