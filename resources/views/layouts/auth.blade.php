@php
    $company_favicon = \App\Models\Utility::getValByName('company_favicon');
    // $logo = asset(Storage::url('uploads/logo/'));
    $logo = \App\Models\Utility::get_file('uploads/logo');

    $company_logo = \App\Models\Utility::GetLogo();
    $SITE_RTL = \App\Models\Utility::getValByName('SITE_RTL');
    $language = \App\Models\Utility::getValByName('default_language');

    $setting = \App\Models\Utility::colorset();
    $color = !empty($setting['theme_color']) ? $setting['theme_color'] : 'theme-2';

    $getseo = App\Models\Utility::getSeoSetting();
    $metatitle = isset($getseo['meta_title']) ? $getseo['meta_title'] : '';
    $metadesc = isset($getseo['meta_description']) ? $getseo['meta_description'] : '';
    $meta_image = \App\Models\Utility::get_file('uploads/meta/');
    $meta_logo = isset($getseo['meta_image']) ? $getseo['meta_image'] : '';
    $enable_cookie = \App\Models\Utility::getCookieSetting('enable_cookie');
    $lang = \App::getLocale('lang');
    if ($lang == 'ar' || $lang == 'he') {
        $SITE_RTL = 'on';
    }
    elseif($SITE_RTL == 'on') 
    {
        $SITE_RTL = 'on';        
    }
    else {
        $SITE_RTL = 'off';
    }

    if (isset($setting['color_flag']) && $setting['color_flag'] == 'true') {
        $themeColor = 'custom-color';
    } else {
        $themeColor = $color;
    }

@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $SITE_RTL == 'on' ? 'rtl' : '' }}">

<head>
    <title>
        {{ \App\Models\Utility::getValByName('title_text') ? \App\Models\Utility::getValByName('title_text') : config('app.name', 'HRMGo SaaS') }}
        - @yield('page-title')</title>

    <!-- SEO META -->
    <meta name="title" content="{{ $metatitle }}">
    <meta name="description" content="{{ $metadesc }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ env('APP_URL') }}">
    <meta property="og:title" content="{{ $metatitle }}">
    <meta property="og:description" content="{{ $metadesc }}">
    <meta property="og:image"
        content="{{ isset($meta_logo) && !empty(asset('storage/uploads/meta/' . $meta_logo)) ? asset('storage/uploads/meta/' . $meta_logo) : 'hrmgo.png' }}">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ env('APP_URL') }}">
    <meta property="twitter:title" content="{{ $metatitle }}">
    <meta property="twitter:description" content="{{ $metadesc }}">
    <meta property="twitter:image"
        content="{{ isset($meta_logo) && !empty(asset('storage/uploads/meta/' . $meta_logo)) ? asset('storage/uploads/meta/' . $meta_logo) : 'hrmgo.png' }}">


    <!-- HTML5 Shim and Respond.js IE11 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 11]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <!-- Meta -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />

    <meta http-equiv="X-UA-Compatible" content="IE=edge" />


    <meta name="description" content="Dashboard Template Description" />
    <meta name="keywords" content="Dashboard Template" />
    <meta name="author" content="Workdo" />

    <!-- Favicon icon -->
    <link rel="icon" href="{{ $logo . '/favicon.png' . '?' . time() }}" type="image/x-icon" />

    <!-- font css -->
    <link rel="stylesheet" href="{{ asset('assets/fonts/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/material.css') }}">
    {{-- <link rel="stylesheet" href="{{ asset('assets/css/stylesheet.css') }}"> --}}
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <!-- vendor css -->

    <style>
        :root {
            --color-customColor: <?=$color ?>;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/custom-color.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/customizer.css') }}">

    @if ($setting['cust_darklayout'] == 'on')
        @if (isset($SITE_RTL) && $SITE_RTL == 'on')
            <link rel="stylesheet" href="{{ asset('assets/css/style-rtl.css') }}" id="main-style-link">
        @endif
        <link rel="stylesheet" href="{{ asset('assets/css/style-dark.css') }}">
    @else
        @if (isset($SITE_RTL) && $SITE_RTL == 'on')
            <link rel="stylesheet" href="{{ asset('assets/css/style-rtl.css') }}" id="main-style-link">
        @else
            <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" id="main-style-link">
        @endif
    @endif
    @if (isset($SITE_RTL) && $SITE_RTL == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/custom-login-rtl.css') }}" id="main-style-link">
    @else
        <link rel="stylesheet" href="{{ asset('assets/css/custom-login.css') }}" id="main-style-link">
    @endif
    @if (request()->routeIs('login'))
        <link rel="stylesheet" href="{{ asset('assets/css/login-page.css') }}">
    @endif
    @if ($setting['cust_darklayout'] == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/custom-dark.css') }}" id="main-style-link">
    @endif

</head>

<body class="{{ $themeColor }} @if(request()->routeIs('login')) page-login @endif">
    <!-- [custom-login] start -->
    <div class="custom-login">
        <div class="login-bg-img">

            @if (strpos($themeColor, 'theme') === 0)
                <img src="{{ asset('assets/images/' . $themeColor . '.svg') }}" class="login-bg-1">
            @else
                <img src="{{ asset('assets/images/theme-3.svg') }}" class="login-bg-1">
            @endif

            <img src="{{ asset('assets/images/common.svg') }}" class="login-bg-2">
        </div>
        <div class="bg-login bg-primary"></div>
        <div class="custom-login-inner">
            @if(request()->routeIs('login'))
            {{-- ── PREMIUM SPLIT-SCREEN LOGIN ───────────────── --}}
            <main class="login-split-screen">
                {{-- LEFT: Brand / Info Panel --}}
                <div class="login-brand-panel" style="width:60% !important;display:flex !important;flex-direction:column !important;justify-content:space-between !important;align-items:stretch !important;padding:3rem !important;overflow:hidden;position:relative;background:#1e3a8a !important;">
                <style>
                    .login-brand-panel::before, .login-brand-panel::after { display:none !important; }
                    .login-form-panel { background:#f8fafc !important; width:40% !important; }
                    .login-form-panel::before { display:none !important; }
                    .lfp-lang, .lfp-inner .lfp-lang, .jl-foot { display:none !important; }
                    @media (max-width: 992px) {
                        .login-brand-panel, .login-form-panel { width:100% !important; }
                    }
                </style>
                    <style>
                        @keyframes lbp-float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
                        @keyframes lbp-pulse { 0%,100%{opacity:.4;transform:scale(1)} 50%{opacity:.7;transform:scale(1.04)} }
                        .lbp-orb { position:absolute; border-radius:50%; filter:blur(60px); pointer-events:none; animation:lbp-pulse 6s ease-in-out infinite; }
                        .lbp-feat-item { display:flex; align-items:flex-start; gap:.85rem; padding:.85rem 0; border-bottom:1px solid rgba(255,255,255,.1); }
                        .lbp-feat-item:last-child { border-bottom:none; }
                        .lbp-feat-icon { flex-shrink:0; width:38px; height:38px; border-radius:10px; background:rgba(255,255,255,.13); backdrop-filter:blur(10px); display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.05rem; border:1px solid rgba(255,255,255,.18); }
                        .lbp-feat-text h5 { font-size:.92rem; font-weight:700; color:#fff; margin:0 0 .15rem; letter-spacing:-.01em; }
                        .lbp-feat-text p { font-size:.78rem; color:rgba(255,255,255,.7); margin:0; line-height:1.5; }
                        .lbp-stat-card { background:rgba(255,255,255,.1); backdrop-filter:blur(12px); border:1px solid rgba(255,255,255,.18); border-radius:14px; padding:1rem .9rem; text-align:center; }
                        .lbp-stat-num { font-size:1.5rem; font-weight:800; color:#fff; letter-spacing:-.02em; line-height:1; display:block; }
                        .lbp-stat-lbl { font-size:.7rem; color:rgba(255,255,255,.7); margin-top:.3rem; letter-spacing:.04em; text-transform:uppercase; font-weight:600; }
                    </style>

                    {{-- Background — blue gradient base + decorative blue orbs + grid pattern --}}
                    <div style="position:absolute;inset:0;background:linear-gradient(135deg,#1e3a8a 0%,#1d4ed8 45%,#2563eb 100%);z-index:0;"></div>
                    <div style="position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.04) 1px,transparent 1px);background-size:40px 40px;z-index:0;opacity:.5;"></div>
                    <div class="lbp-orb" style="width:380px;height:380px;background:rgba(59,130,246,.55);top:-120px;right:-90px;"></div>
                    <div class="lbp-orb" style="width:320px;height:320px;background:rgba(96,165,250,.45);bottom:-100px;left:-70px;animation-delay:2s;"></div>
                    <div class="lbp-orb" style="width:200px;height:200px;background:rgba(147,197,253,.3);top:50%;left:30%;animation-delay:4s;"></div>

                    {{-- TOP: Logo + Brand (left aligned) --}}
                    <div style="position:relative;z-index:2;display:flex;justify-content:flex-start;width:100%;">
                        <div style="display:inline-flex;align-items:center;gap:.85rem;">
                            <img src="{{ $logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo . '?' . time() : 'logo-dark.png') }}"
                                 alt="Jemini" style="height:44px;width:auto;object-fit:contain;"
                                 onerror="this.style.display='none';">
                            <div style="text-align:left;">
                                <div style="font-size:1.45rem;font-weight:800;color:#fff;letter-spacing:-.02em;line-height:1;display:inline-block;border-bottom:2.5px solid #fff;padding-bottom:2px;">Jemini</div>
                                <div style="font-size:.6rem;color:rgba(255,255,255,.7);letter-spacing:.12em;text-transform:uppercase;margin-top:.25rem;font-weight:600;">By People, For People</div>
                            </div>
                        </div>
                    </div>

                    {{-- MIDDLE: Headline + Features --}}
                    <div style="position:relative;z-index:2;margin:2rem 0;">
                        <span style="display:inline-block;background:rgba(255,255,255,.15);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.22);color:#fff;font-size:.68rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;border-radius:99px;padding:.35rem 1rem;margin-bottom:1rem;">
                            <i class="ti ti-sparkles"></i> {{ __('Modern HR Platform') }}
                        </span>
                        <h1 style="font-size:2.1rem;font-weight:800;color:#fff;letter-spacing:-.025em;line-height:1.15;margin:0 0 .9rem;">
                            {{ __('Your Trusted') }}<br>
                            <span style="background:linear-gradient(90deg,#bfdbfe,#93c5fd 50%,#60a5fa);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;">{{ __('People Partner') }}</span>
                        </h1>
                        <p style="font-size:.92rem;color:rgba(255,255,255,.78);line-height:1.65;margin:0 0 1.5rem;max-width:440px;">
                            {{ __('Streamline HR, payroll, attendance, and compliance in one unified platform. Save 10+ hours every week.') }}
                        </p>

                        {{-- Feature list --}}
                        <div style="max-width:440px;">
                            <div class="lbp-feat-item">
                                <div class="lbp-feat-icon"><i class="ti ti-fingerprint"></i></div>
                                <div class="lbp-feat-text">
                                    <h5>{{ __('Smart Attendance') }}</h5>
                                    <p>{{ __('Web, GPS, biometric & face recognition clock-in.') }}</p>
                                </div>
                            </div>
                            <div class="lbp-feat-item">
                                <div class="lbp-feat-icon"><i class="ti ti-report-money"></i></div>
                                <div class="lbp-feat-text">
                                    <h5>{{ __('Automated Payroll') }}</h5>
                                    <p>{{ __('One-click salary processing with PF, ESI & TDS.') }}</p>
                                </div>
                            </div>
                            <div class="lbp-feat-item">
                                <div class="lbp-feat-icon"><i class="ti ti-shield-check"></i></div>
                                <div class="lbp-feat-text">
                                    <h5>{{ __('Bank-Grade Security') }}</h5>
                                    <p>{{ __('AES-256 encryption, role-based access & full audit logs.') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- BOTTOM: Stats --}}
                    <div style="position:relative;z-index:2;display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;max-width:440px;">
                        <div class="lbp-stat-card">
                            <span class="lbp-stat-num">12+</span>
                            <div class="lbp-stat-lbl">{{ __('Modules') }}</div>
                        </div>
                        <div class="lbp-stat-card">
                            <span class="lbp-stat-num">15+</span>
                            <div class="lbp-stat-lbl">{{ __('Languages') }}</div>
                        </div>
                        <div class="lbp-stat-card">
                            <span class="lbp-stat-num">99.9%</span>
                            <div class="lbp-stat-lbl">{{ __('Uptime') }}</div>
                        </div>
                    </div>


                    {{-- (legacy hidden) --}}
                    <div style="display:none;">

                        {{-- Floating badge top-left --}}
                        <div class="lbp-card1" style="position:absolute;top:0;left:0;background:rgba(255,255,255,.15);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.25);border-radius:12px;padding:.45rem .8rem;display:flex;align-items:center;gap:.5rem;z-index:3;">
                            <div style="width:8px;height:8px;background:#10b981;border-radius:50%;" class="lbp-dot"></div>
                            <span style="font-size:.7rem;font-weight:600;color:#fff;white-space:nowrap;">247 Employees Active</span>
                        </div>

                        {{-- Floating badge top-right --}}
                        <div class="lbp-card2" style="position:absolute;top:4px;right:0;background:rgba(255,255,255,.15);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.25);border-radius:12px;padding:.45rem .8rem;display:flex;align-items:center;gap:.5rem;z-index:3;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z" fill="white"/></svg>
                            <span style="font-size:.7rem;font-weight:600;color:#fff;white-space:nowrap;">Payroll Ready</span>
                        </div>

                        {{-- Main mockup screen --}}
                        <div style="margin-top:28px;background:rgba(15,23,42,.85);backdrop-filter:blur(8px);border-radius:16px;border:1px solid rgba(255,255,255,.15);overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.4);">
                            {{-- Window chrome --}}
                            <div style="background:#0a0f1e;padding:.5rem .75rem;display:flex;align-items:center;gap:.4rem;border-bottom:1px solid rgba(255,255,255,.06);">
                                <div style="width:9px;height:9px;background:#ff5f57;border-radius:50%;"></div>
                                <div style="width:9px;height:9px;background:#febc2e;border-radius:50%;"></div>
                                <div style="width:9px;height:9px;background:#28c840;border-radius:50%;"></div>
                                <div style="flex:1;margin:0 .5rem;background:rgba(255,255,255,.06);border-radius:4px;height:14px;display:flex;align-items:center;padding:0 .4rem;">
                                    <div style="width:60%;height:4px;background:rgba(255,255,255,.1);border-radius:2px;"></div>
                                </div>
                            </div>

                            {{-- App layout --}}
                            <div style="display:flex;">
                                {{-- Sidebar --}}
                                <div style="width:52px;background:#070c18;padding:.6rem .4rem;display:flex;flex-direction:column;gap:.5rem;border-right:1px solid rgba(255,255,255,.05);">
                                    <div style="width:28px;height:28px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);border-radius:8px;margin:0 auto .4rem;"></div>
                                    <div style="width:28px;height:28px;background:rgba(59,130,246,.25);border-radius:7px;margin:0 auto;display:flex;align-items:center;justify-content:center;">
                                        <div style="width:12px;height:2px;background:#60a5fa;border-radius:1px;box-shadow:0 3px 0 #60a5fa,0 6px 0 #60a5fa;"></div>
                                    </div>
                                    <div style="width:28px;height:28px;border-radius:7px;margin:0 auto;display:flex;align-items:center;justify-content:center;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="rgba(255,255,255,.3)"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                    </div>
                                    <div style="width:28px;height:28px;border-radius:7px;margin:0 auto;display:flex;align-items:center;justify-content:center;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="rgba(255,255,255,.3)"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
                                    </div>
                                    <div style="width:28px;height:28px;border-radius:7px;margin:0 auto;display:flex;align-items:center;justify-content:center;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="rgba(255,255,255,.3)"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm4.24 16L12 15.45 7.77 18l1.12-4.81-3.73-3.23 4.92-.42L12 5l1.92 4.53 4.92.42-3.73 3.23L16.23 18z"/></svg>
                                    </div>
                                </div>

                                {{-- Main content --}}
                                <div style="flex:1;padding:.75rem;min-width:0;">
                                    {{-- Page title --}}
                                    <div style="margin-bottom:.6rem;">
                                        <div style="width:90px;height:6px;background:rgba(255,255,255,.3);border-radius:3px;"></div>
                                        <div style="width:55px;height:4px;background:rgba(255,255,255,.1);border-radius:2px;margin-top:.25rem;"></div>
                                    </div>

                                    {{-- KPI row --}}
                                    <div style="display:flex;gap:.4rem;margin-bottom:.6rem;">
                                        <div style="flex:1;background:linear-gradient(135deg,rgba(59,130,246,.35),rgba(29,78,216,.25));border:1px solid rgba(59,130,246,.3);border-radius:8px;padding:.4rem .5rem;">
                                            <div style="width:28px;height:3px;background:rgba(255,255,255,.2);border-radius:2px;margin-bottom:.3rem;"></div>
                                            <div style="width:40px;height:8px;background:rgba(255,255,255,.85);border-radius:3px;margin-bottom:.2rem;"></div>
                                            <div style="display:flex;align-items:center;gap:.2rem;">
                                                <div style="width:0;height:0;border-left:3px solid transparent;border-right:3px solid transparent;border-bottom:4px solid #10b981;"></div>
                                                <div style="width:18px;height:3px;background:#10b981;border-radius:1px;opacity:.8;"></div>
                                            </div>
                                        </div>
                                        <div style="flex:1;background:linear-gradient(135deg,rgba(16,185,129,.25),rgba(5,150,105,.15));border:1px solid rgba(16,185,129,.25);border-radius:8px;padding:.4rem .5rem;">
                                            <div style="width:24px;height:3px;background:rgba(255,255,255,.2);border-radius:2px;margin-bottom:.3rem;"></div>
                                            <div style="width:36px;height:8px;background:rgba(255,255,255,.85);border-radius:3px;margin-bottom:.2rem;"></div>
                                            <div style="display:flex;align-items:center;gap:.2rem;">
                                                <div style="width:0;height:0;border-left:3px solid transparent;border-right:3px solid transparent;border-bottom:4px solid #f59e0b;"></div>
                                                <div style="width:14px;height:3px;background:#f59e0b;border-radius:1px;opacity:.8;"></div>
                                            </div>
                                        </div>
                                        <div style="flex:1;background:linear-gradient(135deg,rgba(139,92,246,.25),rgba(109,40,217,.15));border:1px solid rgba(139,92,246,.25);border-radius:8px;padding:.4rem .5rem;">
                                            <div style="width:30px;height:3px;background:rgba(255,255,255,.2);border-radius:2px;margin-bottom:.3rem;"></div>
                                            <div style="width:32px;height:8px;background:rgba(255,255,255,.85);border-radius:3px;margin-bottom:.2rem;"></div>
                                            <div style="display:flex;align-items:center;gap:.2rem;">
                                                <div style="width:0;height:0;border-left:3px solid transparent;border-right:3px solid transparent;border-top:4px solid #ef4444;"></div>
                                                <div style="width:16px;height:3px;background:#ef4444;border-radius:1px;opacity:.8;"></div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Bottom row: chart + list --}}
                                    <div style="display:flex;gap:.4rem;">
                                        {{-- Chart card --}}
                                        <div style="flex:1.4;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:.5rem .5rem .4rem;">
                                            <div style="width:50px;height:4px;background:rgba(255,255,255,.2);border-radius:2px;margin-bottom:.15rem;"></div>
                                            <div style="width:30px;height:3px;background:rgba(255,255,255,.07);border-radius:1.5px;margin-bottom:.4rem;"></div>
                                            {{-- Bar chart --}}
                                            <div style="display:flex;align-items:flex-end;gap:3px;height:40px;padding:0 2px;">
                                                <div style="flex:1;background:linear-gradient(to top,#2563eb,#60a5fa);border-radius:2px 2px 0 0;height:45%;opacity:.6;"></div>
                                                <div style="flex:1;background:linear-gradient(to top,#2563eb,#60a5fa);border-radius:2px 2px 0 0;height:65%;opacity:.75;"></div>
                                                <div style="flex:1;background:linear-gradient(to top,#2563eb,#60a5fa);border-radius:2px 2px 0 0;height:80%;"></div>
                                                <div style="flex:1;background:linear-gradient(to top,#2563eb,#60a5fa);border-radius:2px 2px 0 0;height:70%;opacity:.85;"></div>
                                                <div style="flex:1;background:linear-gradient(to top,#059669,#34d399);border-radius:2px 2px 0 0;height:55%;opacity:.8;"></div>
                                                <div style="flex:1;background:linear-gradient(to top,#059669,#34d399);border-radius:2px 2px 0 0;height:90%;"></div>
                                                <div style="flex:1;background:linear-gradient(to top,#059669,#34d399);border-radius:2px 2px 0 0;height:75%;opacity:.9;"></div>
                                            </div>
                                        </div>
                                        {{-- Team list --}}
                                        <div style="flex:1;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:.5rem .5rem;">
                                            <div style="width:36px;height:4px;background:rgba(255,255,255,.2);border-radius:2px;margin-bottom:.5rem;"></div>
                                            <div style="display:flex;align-items:center;gap:.3rem;margin-bottom:.35rem;">
                                                <div style="width:18px;height:18px;background:#3b82f6;border-radius:50%;flex-shrink:0;"></div>
                                                <div><div style="width:32px;height:3px;background:rgba(255,255,255,.3);border-radius:2px;"></div><div style="width:22px;height:2px;background:rgba(255,255,255,.1);border-radius:1px;margin-top:2px;"></div></div>
                                                <div style="margin-left:auto;width:20px;height:5px;background:rgba(16,185,129,.4);border-radius:2px;"></div>
                                            </div>
                                            <div style="display:flex;align-items:center;gap:.3rem;margin-bottom:.35rem;">
                                                <div style="width:18px;height:18px;background:#10b981;border-radius:50%;flex-shrink:0;"></div>
                                                <div><div style="width:28px;height:3px;background:rgba(255,255,255,.3);border-radius:2px;"></div><div style="width:18px;height:2px;background:rgba(255,255,255,.1);border-radius:1px;margin-top:2px;"></div></div>
                                                <div style="margin-left:auto;width:20px;height:5px;background:rgba(16,185,129,.4);border-radius:2px;"></div>
                                            </div>
                                            <div style="display:flex;align-items:center;gap:.3rem;">
                                                <div style="width:18px;height:18px;background:#f59e0b;border-radius:50%;flex-shrink:0;"></div>
                                                <div><div style="width:34px;height:3px;background:rgba(255,255,255,.3);border-radius:2px;"></div><div style="width:24px;height:2px;background:rgba(255,255,255,.1);border-radius:1px;margin-top:2px;"></div></div>
                                                <div style="margin-left:auto;width:20px;height:5px;background:rgba(251,191,36,.4);border-radius:2px;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Laptop base --}}
                        <div style="margin:-2px auto 0;width:85%;height:10px;background:rgba(255,255,255,.1);border-radius:0 0 8px 8px;border:1px solid rgba(255,255,255,.12);border-top:none;"></div>
                        <div style="margin:0 auto;width:45%;height:5px;background:rgba(255,255,255,.07);border-radius:0 0 6px 6px;"></div>

                        {{-- Bottom floating badge --}}
                        <div class="lbp-card3" style="position:absolute;bottom:0;right:8px;background:rgba(255,255,255,.15);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.25);border-radius:12px;padding:.45rem .8rem;display:flex;align-items:center;gap:.5rem;z-index:3;">
                            <div style="width:8px;height:8px;background:#3b82f6;border-radius:50%;" class="lbp-dot" style="animation-delay:1s;"></div>
                            <span style="font-size:.7rem;font-weight:600;color:#fff;white-space:nowrap;">93% Attendance Today</span>
                        </div>
                    </div>

                </div>
                {{-- RIGHT: Form Panel --}}
                <div class="login-form-panel" style="background:#f8fafc;">
                    <div class="lfp-inner" style="max-width:440px;width:100%;">
                        <div class="lfp-lang mb-2 d-flex justify-content-end">
                            @yield('language-bar')
                        </div>

                        {{-- Mobile-only brand (visible on small screens where left panel hides) --}}
                        <div class="d-md-none" style="text-align:center;margin-bottom:1.25rem;">
                            <img src="{{ $logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo . '?' . time() : 'logo-dark.png') }}"
                                 alt="{{ config('app.name', 'HRMS') }}"
                                 style="height:44px;width:auto;object-fit:contain;display:block;margin:0 auto .35rem;"
                                 onerror="this.style.display='none';">
                            <div style="font-size:1.3rem;font-weight:800;color:#1e3a8a;letter-spacing:-.01em;display:inline-block;border-bottom:2.5px solid #1e3a8a;padding-bottom:2px;">Jemini</div>
                        </div>

                        <div class="card" style="border:1px solid #e5e7eb;border-radius:20px;box-shadow:0 25px 60px -20px rgba(15,23,42,.18);background:#fff;padding:0;overflow:hidden;">
                            @yield('content')
                        </div>

                        <p class="text-center mt-4" style="font-size:.78rem;color:#94a3b8;margin:0;">
                            &copy; {{ date('Y') }} {{ \App\Models\Utility::getValByName('footer_text') ?: config('app.name', 'HRMS') }}.
                            {{ __('All rights reserved.') }}
                        </p>
                    </div>
                </div>
            </main>

            @else
            {{-- ── OTHER AUTH PAGES (Register, Forgot Password, etc.) ── --}}
            @unless(request()->routeIs('login'))
            <header class="dash-header" style="position:relative;top:auto;left:auto;right:auto;z-index:auto;background:#fff;border-bottom:1px solid #e2e8f0;box-shadow:none;">
                <nav class="navbar navbar-expand-md default">
                    <div class="container">
                        <div class="navbar-brand">
                            <a href="{{ url('/') }}" style="display:inline-flex;align-items:center;gap:.75rem;text-decoration:none;">
                                <img src="{{ $logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo . '?' . time() : 'logo_dark.png' . '?' . time()) }}"
                                    class="logo auth-logo-img" alt="{{ config('app.name', 'HRMGo SaaS') }}"
                                    loading="lazy" style="max-height: 44px;" />
                                <span style="text-align:left;line-height:1;">
                                    <span style="display:block;font-size:1.4rem;font-weight:800;color:#0f172a;letter-spacing:-.02em;border-bottom:2.5px solid #1e3a8a;padding-bottom:2px;">Jemini</span>
                                    <span style="display:block;font-size:.58rem;color:#64748b;letter-spacing:.12em;text-transform:uppercase;margin-top:.25rem;font-weight:600;">By People, For People</span>
                                </span>
                            </a>
                        </div>
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                            data-bs-target="#navbarlogin">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbarlogin">
                            <ul class="navbar-nav align-items-center ms-auto mb-2 mb-lg-0">
                                @include('landingpage::layouts.buttons')
                                @yield('language-bar')
                            </ul>
                        </div>
                    </div>
                </nav>
            </header>
            @endunless
            <main class="custom-wrapper">
                <div class="custom-row">
                    <div class="card">
                        @yield('content')
                    </div>
                </div>
            </main>
            <footer>
                <div class="auth-footer">
                    <div class="container">
                        <div class="row">
                            <div class="col-12">
                                <span>
                                    @if (empty(App\Models\Utility::getValByName('footer_text')))
                                        &copy;{{ date(' Y') }}
                                    @endif
                                    {{ App\Models\Utility::getValByName('footer_text') ? App\Models\Utility::getValByName('footer_text') : config('app.name', 'HRMGo Saas') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
            @endif
        </div>
    </div>
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 99999">
        <div id="liveToast" class="toast text-white  fade" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>
    <!-- [custom-login] end -->

    <!-- Required Js -->
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/vendor-all.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/feather.min.js') }}"></script>
    <script>
        feather.replace();
    </script>

    <input type="checkbox" class="d-none" id="cust-theme-bg"
        {{ \App\Models\Utility::getValByName('cust_theme_bg') == 'on' ? 'checked' : '' }} />
    <input type="checkbox" class="d-none" id="cust-darklayout"
        {{ \App\Models\Utility::getValByName('cust_darklayout') == 'on' ? 'checked' : '' }} />

    {{-- Dark Mode ReCaptcha --}}
    {{-- @if (\App\Models\Utility::getValByName('cust_darklayout') == 'on')
        <style>
            .g-recaptcha {
                filter: invert(1) hue-rotate(180deg) !important;
            }
        </style>
    @endif --}}

    {{-- <script src="{{asset('custom/js/custom.js')}}"></script> --}}
    <script src="{{ asset('js/custom.js') }}"></script>
    @stack('script')
    @stack('custom-scripts')
    @if ($enable_cookie['enable_cookie'] == 'on')
        @include('layouts.cookie_consent')
    @endif

    @if ($message = Session::get('success'))
        <script>
            show_toastr('Success', '{!! $message !!}', 'success');
        </script>
    @endif
    @if ($message = Session::get('error'))
        <script>
            show_toastr('Error', '{!! $message !!}', 'error');
        </script>
    @endif
</body>

</html>
