@php
    $logo = \App\Models\Utility::get_file('uploads/logo/');
    $favicon = \App\Models\Utility::getValByName('favicon');
    $company_favicon = \App\Models\Utility::getValByName('company_favicon');
    $company_logo = \App\Models\Utility::GetLogo();
    $logo_dark_file = (\Auth::user() && \Auth::user()->type != 'super admin')
        ? \App\Models\Utility::getValByName('company_logo')
        : \App\Models\Utility::getValByName('dark_logo');
    $logo_light_file = (\Auth::user() && \Auth::user()->type != 'super admin')
        ? \App\Models\Utility::getValByName('company_logo_light')
        : \App\Models\Utility::getValByName('light_logo');
    $logo_dark_url = $logo . (!empty($logo_dark_file) ? $logo_dark_file : 'logo-dark.png');
    $logo_light_url = $logo . (!empty($logo_light_file) ? $logo_light_file : 'logo-light.png');
    $SITE_RTL = \App\Models\Utility::getValByName('SITE_RTL');
    $setting = \App\Models\Utility::colorset();
    $color = !empty($setting['theme_color']) ? $setting['theme_color'] : 'theme-2';
    $pusher_setting = \App\Models\Utility::settings();
    $getseo = App\Models\Utility::getSeoSetting();
    $metatitle = isset($getseo['meta_title']) ? $getseo['meta_title'] : '';
    $metadesc = isset($getseo['meta_description']) ? $getseo['meta_description'] : '';
    $meta_image = \App\Models\Utility::get_file('uploads/meta/');
    $meta_logo = isset($getseo['meta_image']) ? $getseo['meta_image'] : '';
    $enable_cookie = \App\Models\Utility::getCookieSetting('enable_cookie');

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


    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Dashboard Template Description" />
    <meta name="keywords" content="Dashboard Template" />
    <meta name="author" content="WorkDo" />


    <!-- Favicon icon -->
    @php
        $faviconFile = (\Auth::user() && \Auth::user()->type === 'super admin')
            ? (!empty($favicon) ? $favicon : 'favicon.png')
            : (!empty($company_favicon) ? $company_favicon : 'favicon.png');
    @endphp
    <link rel="icon" type="image/png" href="{{ $logo . $faviconFile . '?' . time() }}" />
    <link rel="shortcut icon" type="image/png" href="{{ $logo . $faviconFile . '?' . time() }}" />
    <!-- for calender-->
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/main.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/plugins/datepicker-bs5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/style.css') }}">
    <!-- font css -->
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/bootstrap-switch-button.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/material.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    <!-- vendor css -->

    <link rel="stylesheet" href="{{ asset('assets/css/customizer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">


    @if ($SITE_RTL == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/style-rtl.css') }}">
    @endif

    @if ($setting['cust_darklayout'] == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/style-dark.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" id="main-style-link">
    @endif

    <meta name="url" content="{{ url('') . '/' . config('chatify.routes.prefix') }}"
        data-user="{{ Auth::user()->id }}">

    <link rel='stylesheet' href='https://unpkg.com/nprogress@0.2.0/nprogress.css' />

    @if ($setting['cust_darklayout'] == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/custom-dark.css') }}">
    @endif

    <style>
        :root {
            --color-customColor: <?=$color ?>;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/custom-color.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/dashboard-redesign.css') }}">

    @stack('css-page')
</head>



<body class="{{ $themeColor }}">
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>
    <!-- [ Pre-loader ] End -->
    <!-- [ navigation menu ] start -->
    @include('partial.Admin.menu')
    <!-- [ navigation menu ] end -->
    <!-- [ Header ] start -->

    @include('partial.Admin.header')

    <!-- Modal -->
    <div class="modal notification-modal fade" id="notification-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="btn-close float-end" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                    <h6 class="mt-2">
                        <i data-feather="monitor" class="me-2"></i>Desktop settings
                    </h6>
                    <hr />
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="pcsetting1" checked />
                        <label class="form-check-label f-w-600 pl-1" for="pcsetting1">Allow desktop
                            notification</label>
                    </div>
                    <p class="text-muted ms-5">
                        you get lettest content at a time when data will updated
                    </p>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="pcsetting2" />
                        <label class="form-check-label f-w-600 pl-1" for="pcsetting2">Store Cookie</label>
                    </div>
                    <h6 class="mb-0 mt-5">
                        <i data-feather="save" class="me-2"></i>Application settings
                    </h6>
                    <hr />
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="pcsetting3" />
                        <label class="form-check-label f-w-600 pl-1" for="pcsetting3">Backup Storage</label>
                    </div>
                    <p class="text-muted mb-4 ms-5">
                        Automaticaly take backup as par schedule
                    </p>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="pcsetting4" />
                        <label class="form-check-label f-w-600 pl-1" for="pcsetting4">Allow guest to print
                            file</label>
                    </div>
                    <h6 class="mb-0 mt-5">
                        <i data-feather="cpu" class="me-2"></i>System settings
                    </h6>
                    <hr />
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="pcsetting5" checked />
                        <label class="form-check-label f-w-600 pl-1" for="pcsetting5">View other user chat</label>
                    </div>
                    <p class="text-muted ms-5">Allow to show public user message</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-danger btn-sm" data-bs-dismiss="modal">
                        Close
                    </button>
                    <button type="button" class="btn btn-light-primary btn-sm">
                        Save changes
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Header ] end -->


    <!-- [ Main Content ] start -->
    <section class="dash-container">
        <div class="dash-content">
            <!-- [ breadcrumb ] start -->
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="page-header-title">
                                <h4 class="m-b-10">
                                    @yield('page-title')
                                </h4>
                            </div>
                            <ul class="breadcrumb">
                                @yield('breadcrumb')
                            </ul>
                        </div>
                        <div class="col-sm-auto col-md">
                            <div class="float-end "
                                @if ($SITE_RTL == 'on') style=" float: left !important;" @endif>
                                @yield('action-button')
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- [ breadcrumb ] end -->
            <!-- [ Main Content ] start -->
            <!-- [ basic-table ] start -->
            @yield('content')
            <!-- [ basic-table ] end -->
            <!-- [ Main Content ] end -->
        </div>
    </section>
    <div class="modal fade" id="commonModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="body">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="commonModalOver" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                </div>
            </div>
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
    <!-- [ Main Content ] end -->
    <footer class="dash-footer">
        <div class="footer-wrapper">
            <div class="py-1">
                <span class="text-muted">
                    @if (empty(App\Models\Utility::getValByName('footer_text')))
                        &copy;{{ date(' Y') }}
                    @endif
                    {{ App\Models\Utility::getValByName('footer_text') ? App\Models\Utility::getValByName('footer_text') : config('app.name', 'HRMGo SaaS') }}

                    {{-- {{ \App\Models\Utility::getValByName('footer_text') ? \App\Models\Utility::getValByName('footer_text') : '©Copyright HRMGo SaaS' . date(' Y') }} --}}

                </span>
            </div>

        </div>
    </footer>
    <!-- Warning Section start -->
    <!-- Older IE warning message -->
    <!--[if lt IE 11]>
 
<![endif]-->
    <!-- Warning Section Ends -->
    <!-- Required Js -->
    <script src="{{ asset('assets/js/plugins/choices.min.js') }}"></script>
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/jquery.form.js') }}"></script>

    <script src="{{ asset('js/letter.avatar.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/datepicker-full.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/perfect-scrollbar.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/feather.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap-switch-button.min.js') }}"></script>
    <script src="{{ asset('assets/js/dash.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/simple-datatables.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/flatpickr.min.js') }}"></script>

    <script src="{{ asset('js/custom.js') }}"></script>

    <script src="{{ asset('js/chatify/autosize.js') }}"></script>
    <script src='https://unpkg.com/nprogress@0.2.0/nprogress.js'></script>


    {{-- <script>
        if($("#pc-dt-simple").lenght > 0) {
            const dataTable = new simpleDatatables.DataTable("#pc-dt-simple");
        }
    </script> --}}

    <script>
        if (document.getElementById('pc-dt-simple')) {
            const dataTable = new simpleDatatables.DataTable("#pc-dt-simple");
        }
    </script>

    <script>
        feather.replace();
        var pctoggle = document.querySelector("#pct-toggler");
        if (pctoggle) {
            pctoggle.addEventListener("click", function() {
                if (
                    !document.querySelector(".pct-customizer").classList.contains("active")
                ) {
                    document.querySelector(".pct-customizer").classList.add("active");
                } else {
                    document.querySelector(".pct-customizer").classList.remove("active");
                }
            });
        }
        var themescolors = document.querySelectorAll(".themes-color > a");
        for (var h = 0; h < themescolors.length; h++) {
            var c = themescolors[h];
            c.addEventListener("click", function(event) {
                var targetElement = event.target;
                if (targetElement.tagName == "SPAN") {
                    targetElement = targetElement.parentNode;
                }
                var temp = targetElement.getAttribute("data-value");
                removeClassByPrefix(document.querySelector("body"), "theme-");
                document.querySelector("body").classList.add(temp);
            });
        }
        var custthemebg = document.querySelector("#cust_theme_bg");
        if (custthemebg) {
            custthemebg.addEventListener("click", function() {
                if (custthemebg.checked) {
                    document.querySelector(".dash-sidebar").classList.add("transprent-bg");
                    document
                        .querySelector(".dash-header:not(.dash-mob-header)")
                        .classList.add("transprent-bg");
                } else {
                    document.querySelector(".dash-sidebar").classList.remove("transprent-bg");
                    document
                        .querySelector(".dash-header:not(.dash-mob-header)")
                        .classList.remove("transprent-bg");
                }
            });
        }
        var custdarklayout = document.querySelector("#cust_darklayout");
        if (custdarklayout) {
            custdarklayout.addEventListener("click", function() {
                if (custdarklayout.checked) {
                    document
                        .querySelector("#main-style-link")
                        .setAttribute("href", "{{ asset('assets/css/style-dark.css') }}");
                    document
                        .querySelector(".m-header > .b-brand > .logo-lg")
                        .setAttribute("src", "{{ $logo_light_url }}?v={{ time() }}");
                } else {
                    document
                        .querySelector("#main-style-link")
                        .setAttribute("href", "{{ asset('assets/css/style.css') }}");
                    document
                        .querySelector(".m-header > .b-brand > .logo-lg")
                        .setAttribute("src", "{{ $logo_dark_url }}?v={{ time() }}");
                }
            });
        }

        function removeClassByPrefix(node, prefix) {
            for (let i = 0; i < node.classList.length; i++) {
                let value = node.classList[i];
                if (value.startsWith(prefix)) {
                    node.classList.remove(value);
                }
            }
        }
    </script>

    <script>
        // Sidebar dropdown click: prevent page scroll-to-top on href="#"
        $(document).on('click', '.dash-sidebar .dash-hasmenu > a.dash-link', function(e) {
            if (this.getAttribute('href') === '#') {
                e.preventDefault();
                e.stopPropagation();
            }
        });
        // Belt-and-suspenders: any sidebar link with href="#" should not scroll
        $(document).on('click', '.dash-sidebar a[href="#"]', function(e) {
            e.preventDefault();
        });
    </script>

    <script>
        $(document).on('click', '.local_calender .fc-daygrid-event', function(e) {
            e.preventDefault();
            var event = $(this);
            var url = $(this).attr('href');

            // Attendance calendar events should navigate directly, not open in modal
            if ($(this).hasClass('attn-cal-present') || $(this).hasClass('attn-cal-absent') ||
                $(this).hasClass('attn-cal-late') || $(this).hasClass('attn-cal-leave') ||
                $(this).hasClass('attn-cal-halfday') || $(this).hasClass('spectal-leave-cal') ||
                (url && url.indexOf('attendanceemployee') !== -1)) {
                window.location.href = url;
                return;
            }

            var title = $(this).find('.fc-event-title').html();
            var size = 'md';
            $("#commonModal .modal-title ").html(title);
            $("#commonModal .modal-dialog").addClass('modal-' + size);
            $.ajax({
                url: url,
                success: function(data) {
                    $('#commonModal .body').html(data);
                    $("#commonModal").modal('show');
                    if ($(".d_week").length > 0) {
                        $($(".d_week")).each(function(index, element) {
                            var id = $(element).attr('id');

                            (function() {
                                const d_week = new Datepicker(document.querySelector('#' +
                                    id), {
                                    buttonClass: 'btn',
                                    format: 'yyyy-mm-dd',
                                });
                            })();

                        });
                    }

                },
                error: function(data) {
                    var payload = data.responseJSON || {};
                    var msg = payload.error || payload.message || '{{ __('Something went wrong. Please try again.') }}';
                    if (typeof toastrs === 'function') {
                        toastrs('Error', msg, 'error');
                    } else if (typeof show_toastr === 'function') {
                        show_toastr('Error', msg, 'error');
                    } else {
                        alert(msg);
                    }
                }
            });
        });
    </script>

    <script src="https://js.pusher.com/5.0/pusher.min.js"></script>
    
    <!-- Notification Sound & Voice System -->
    <script src="{{ asset('assets/js/notification-sounds.js') }}"></script>
    <script>
        (function() {
            // Notification Sound & Voice Settings
            const NOTIFICATION_SETTINGS = {
                soundEnabled: localStorage.getItem('notification_sound_enabled') !== 'false',
                voiceEnabled: localStorage.getItem('notification_voice_enabled') !== 'false',
                soundType: localStorage.getItem('notification_sound_type') || 'bell',
                volume: parseFloat(localStorage.getItem('notification_volume')) || 0.7,
                voiceSpeed: parseFloat(localStorage.getItem('notification_voice_speed')) || 1.0,
                voicePitch: parseFloat(localStorage.getItem('notification_voice_pitch')) || 1.0
            };
            
            // Initialize Speech Synthesis
            let speechSynthesis = window.speechSynthesis;
            let voices = [];
            
            function loadVoices() {
                voices = speechSynthesis.getVoices();
                // Prefer English voices
                voices = voices.filter(voice => voice.lang.startsWith('en'));
            }
            
            // Load voices
            if (speechSynthesis.onvoiceschanged !== undefined) {
                speechSynthesis.onvoiceschanged = loadVoices;
            }
            loadVoices();
            
            // Play notification sound
            function playNotificationSound() {
                if (!NOTIFICATION_SETTINGS.soundEnabled) return;
                
                try {
                    let sound;
                    switch(NOTIFICATION_SETTINGS.soundType) {
                        case 'modern':
                            sound = notificationSound1;
                            break;
                        case 'bell':
                            sound = notificationSound2;
                            break;
                        case 'chime':
                            sound = notificationSound3;
                            break;
                        default:
                            sound = notificationSound2;
                    }
                    
                    sound.volume = NOTIFICATION_SETTINGS.volume;
                    sound.currentTime = 0;
                    sound.play().catch(err => console.log('Sound play error:', err));
                } catch (error) {
                    console.error('Notification sound error:', error);
                }
            }
            
            // Speak notification text
            function speakNotification(text) {
                if (!NOTIFICATION_SETTINGS.voiceEnabled || !text) return;
                
                try {
                    // Cancel any ongoing speech
                    speechSynthesis.cancel();
                    
                    const utterance = new SpeechSynthesisUtterance(text);
                    utterance.rate = NOTIFICATION_SETTINGS.voiceSpeed;
                    utterance.pitch = NOTIFICATION_SETTINGS.voicePitch;
                    utterance.volume = NOTIFICATION_SETTINGS.volume;
                    
                    // Use a good English voice if available
                    if (voices.length > 0) {
                        utterance.voice = voices[0];
                    }
                    
                    speechSynthesis.speak(utterance);
                } catch (error) {
                    console.error('Voice notification error:', error);
                }
            }
            
            // Combined notification
            window.playNotification = function(message, type = 'info') {
                playNotificationSound();
                
                if (message) {
                    setTimeout(() => {
                        speakNotification(message);
                    }, 300); // Small delay after sound
                }
            }
            
            // Settings panel toggle
            function createNotificationSettingsPanel() {
                const panel = `
                    <div id="notification-settings-panel" style="display: none; position: fixed; top: 80px; right: 20px; z-index: 99999; background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); padding: 20px; min-width: 320px; max-width: 400px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <h5 style="margin: 0; font-weight: 600;">
                                <i class="ti ti-bell-ringing"></i> Notification Settings
                            </h5>
                            <button id="close-notification-settings" style="background: none; border: none; cursor: pointer; font-size: 20px;">×</button>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input type="checkbox" id="notification-sound-toggle" ${NOTIFICATION_SETTINGS.soundEnabled ? 'checked' : ''} style="margin-right: 10px;">
                                <span><i class="ti ti-volume"></i> Enable Sounds</span>
                            </label>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input type="checkbox" id="notification-voice-toggle" ${NOTIFICATION_SETTINGS.voiceEnabled ? 'checked' : ''} style="margin-right: 10px;">
                                <span><i class="ti ti-microphone"></i> Enable Voice Announcements</span>
                            </label>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="font-weight: 500; margin-bottom: 5px; display: block;">Sound Type</label>
                            <select id="notification-sound-type" class="form-control form-control-sm">
                                <option value="bell" ${NOTIFICATION_SETTINGS.soundType === 'bell' ? 'selected' : ''}>Bell</option>
                                <option value="modern" ${NOTIFICATION_SETTINGS.soundType === 'modern' ? 'selected' : ''}>Modern</option>
                                <option value="chime" ${NOTIFICATION_SETTINGS.soundType === 'chime' ? 'selected' : ''}>Chime</option>
                            </select>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="font-weight: 500; margin-bottom: 5px; display: flex; justify-content: between;">
                                <span>Volume</span>
                                <span id="volume-value" style="margin-left: auto;">${Math.round(NOTIFICATION_SETTINGS.volume * 100)}%</span>
                            </label>
                            <input type="range" id="notification-volume" min="0" max="1" step="0.1" value="${NOTIFICATION_SETTINGS.volume}" class="form-range">
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="font-weight: 500; margin-bottom: 5px; display: flex; justify-content: between;">
                                <span>Voice Speed</span>
                                <span id="speed-value" style="margin-left: auto;">${NOTIFICATION_SETTINGS.voiceSpeed}x</span>
                            </label>
                            <input type="range" id="notification-voice-speed" min="0.5" max="2" step="0.1" value="${NOTIFICATION_SETTINGS.voiceSpeed}" class="form-range">
                        </div>
                        
                        <button id="test-notification" class="btn btn-primary btn-sm w-100">
                            <i class="ti ti-player-play"></i> Test Notification
                        </button>
                    </div>
                `;
                
                $('body').append(panel);
                
                // Event listeners
                $('#close-notification-settings').on('click', function() {
                    $('#notification-settings-panel').fadeOut(200);
                });
                
                $('#notification-sound-toggle').on('change', function() {
                    NOTIFICATION_SETTINGS.soundEnabled = this.checked;
                    localStorage.setItem('notification_sound_enabled', this.checked);
                });
                
                $('#notification-voice-toggle').on('change', function() {
                    NOTIFICATION_SETTINGS.voiceEnabled = this.checked;
                    localStorage.setItem('notification_voice_enabled', this.checked);
                });
                
                $('#notification-sound-type').on('change', function() {
                    NOTIFICATION_SETTINGS.soundType = this.value;
                    localStorage.setItem('notification_sound_type', this.value);
                });
                
                $('#notification-volume').on('input', function() {
                    NOTIFICATION_SETTINGS.volume = parseFloat(this.value);
                    localStorage.setItem('notification_volume', this.value);
                    $('#volume-value').text(Math.round(this.value * 100) + '%');
                });
                
                $('#notification-voice-speed').on('input', function() {
                    NOTIFICATION_SETTINGS.voiceSpeed = parseFloat(this.value);
                    localStorage.setItem('notification_voice_speed', this.value);
                    $('#speed-value').text(this.value + 'x');
                });
                
                $('#test-notification').on('click', function() {
                    playNotification('This is a test notification. You have a new message.', 'test');
                });
            }
            
            // Add notification settings icon to header
            function addNotificationSettingsIcon() {
                const icon = `
                    <li class="dash-h-item">
                        <a class="dash-head-link" href="javascript:void(0);" id="notification-settings-icon" title="Notification Settings">
                            <i class="ti ti-bell-ringing nocolor" style="color: #1f2937 !important;"></i>
                        </a>
                    </li>
                `;
                
                $('.dash-header .ms-auto ul.list-unstyled').prepend(icon);
                
                $('#notification-settings-icon').on('click', function() {
                    $('#notification-settings-panel').fadeToggle(200);
                });
            }
            
            // Initialize
            $(document).ready(function() {
                createNotificationSettingsPanel();
                addNotificationSettingsIcon();
                
                // Test notification on initial load (optional)
                // setTimeout(() => playNotification('Welcome! Notifications are enabled.'), 2000);
            });
            
            // Export globally
            window.NOTIFICATION_SETTINGS = NOTIFICATION_SETTINGS;
            window.speakNotification = speakNotification;
            window.playNotificationSound = playNotificationSound;
        })();
    </script>

    @if (\App\Models\Utility::getValByName('gdpr_cookie') == 'on')
        <script type="text/javascript">
            var defaults = {
                'messageLocales': {
                    /*'en': 'We use cookies to make sure you can have the best experience on our website. If you continue to use this site we assume that you will be happy with it.'*/
                    'en': "{{ \App\Models\Utility::getValByName('cookie_text') }}"
                },
                'buttonLocales': {
                    'en': 'Ok'
                },
                'cookieNoticePosition': 'bottom',
                'learnMoreLinkEnabled': false,
                'learnMoreLinkHref': '/cookie-banner-information.html',
                'learnMoreLinkText': {
                    'it': 'Saperne di più',
                    'en': 'Learn more',
                    'de': 'Mehr erfahren',
                    'fr': 'En savoir plus'
                },
                'buttonLocales': {
                    'en': 'Ok'
                },
                'expiresIn': 30,
                'buttonBgColor': '#d35400',
                'buttonTextColor': '#fff',
                'noticeBgColor': '#000',
                'noticeTextColor': '#fff',
                'linkColor': '#009fdd'
            };
        </script>
        <script src="{{ asset('js/cookie.notice.js') }}"></script>
    @endif

    @if (\Auth::user()->type != 'super admin')
        <script>
            $(document).ready(function() {
                pushNotification('{{ Auth::id() }}');
            });

            function pushNotification(id) {

                // ajax setup form csrf token
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                // Global AJAX error handler - redirect to login on session expiry
                $(document).ajaxError(function(event, xhr) {
                    if (xhr.status === 401 || xhr.status === 419) {
                        window.location.href = '{{ route("login") }}';
                    }
                });

                // Pusher — only initialize if app key is configured
                @if(!empty($pusher_setting['pusher_app_key']))
                Pusher.logToConsole = false;

                var pusher = new Pusher('{{ $pusher_setting['pusher_app_key'] }}', {
                    cluster: '{{ $pusher_setting['pusher_app_cluster'] }}',
                    forceTLS: true
                });

                // Pusher Notification
                var channel = pusher.subscribe('send_notification');
                channel.bind('notification', function(data) {
                    if (id == data.user_id) {
                        $(".notification-toggle").addClass('beep');
                        $(".notification-dropdown #notification-list").prepend(data.html);
                    }
                });

                // Pusher Message
                var msgChannel = pusher.subscribe('my-channel');
                msgChannel.bind('my-chat', function(data) {

                    if (id == data.to) {
                        getChat();
                    }
                });
                @endif
            }

            // Get chat for top ox
        </script>
    @endif


    @if ($message = Session::get('success'))
        <script>
            show_toastr('Success', {!! json_encode($message) !!}, 'success');
        </script>
    @endif
    @if ($message = Session::get('error'))
        <script>
            show_toastr('Error', {!! json_encode($message) !!}, 'error');
        </script>
    @endif


    @stack('script-page')
    @if (\Auth::check() && in_array(Request::segment(1), ['branch', 'department', 'designation', 'leavetype', 'document', 'paysliptype', 'allowanceoption', 'loanoption', 'deductionoption', 'goaltype', 'trainer']))
        @include('partials.address_master_dropdown_script')
    @endif

    @stack('scripts')
    @if (Request::segment(1) == 'chats')
        @include('Chatify::layouts.footerLinks')
    @endif

    @if (\Auth::check() && \Auth::user()->type != 'super admin' && Request::segment(1) != 'chats' && Request::segment(1) != 'chat-groups')
        <style>
            /* ── Floating Messenger Button ── */
            .team-chat-fab {
                position: fixed;
                right: 24px;
                bottom: 24px;
                width: 58px;
                height: 58px;
                border: none;
                border-radius: 50%;
                z-index: 1051;
                background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
                color: #fff;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 6px 22px rgba(67, 97, 238, 0.45);
                transition: transform .22s ease, box-shadow .22s ease;
                overflow: visible;
                cursor: pointer;
            }

            .team-chat-fab::after {
                content: '';
                position: absolute;
                inset: -5px;
                border-radius: 50%;
                border: 2px solid rgba(67, 97, 238, 0.35);
                opacity: 0;
                transform: scale(.9);
                transition: transform .3s ease, opacity .3s ease;
                pointer-events: none;
            }

            .team-chat-fab:hover::after {
                opacity: 1;
                transform: scale(1.1);
            }

            .team-chat-fab i {
                font-size: 26px;
                line-height: 1;
                position: relative;
                z-index: 1;
                color: #fff;
            }

            .team-chat-fab .team-chat-counter {
                position: absolute;
                top: -3px;
                right: -3px;
                min-width: 20px;
                height: 20px;
                padding: 0 5px;
                border-radius: 999px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 10px;
                font-weight: 700;
                line-height: 1;
                z-index: 2;
                border: 2px solid #fff;
                box-shadow: 0 1px 4px rgba(0,0,0,.2);
            }

            .team-chat-fab .team-chat-label {
                position: absolute;
                bottom: -22px;
                left: 50%;
                transform: translateX(-50%);
                font-size: 10px;
                font-weight: 700;
                letter-spacing: .4px;
                background: #3a0ca3;
                color: #fff;
                border: none;
                border-radius: 10px;
                padding: 3px 9px;
                box-shadow: 0 2px 6px rgba(58,12,163,.25);
                white-space: nowrap;
                opacity: 0;
                transition: opacity .18s ease;
            }

            .team-chat-fab:hover .team-chat-label {
                opacity: 1;
            }

            .team-chat-fab:hover {
                transform: translateY(-4px) scale(1.06);
                box-shadow: 0 12px 30px rgba(67, 97, 238, 0.55);
            }

            .team-chat-fab:focus-visible {
                outline: 0;
                box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.3), 0 10px 28px rgba(67, 97, 238, 0.5);
            }

            /* ── Chat Panel ── */
            .team-chat-panel {
                position: fixed;
                right: 24px;
                bottom: 98px;
                width: min(380px, calc(100vw - 32px));
                height: min(560px, calc(100vh - 130px));
                border-radius: 18px;
                overflow: hidden;
                background: #fff;
                z-index: 1052;
                border: none;
                box-shadow: 0 12px 48px rgba(67, 97, 238, 0.22), 0 2px 12px rgba(0,0,0,.1);
                display: none;
                flex-direction: column;
                opacity: 0;
                transform: translateY(14px) scale(.97);
                transform-origin: bottom right;
                transition: opacity .22s ease, transform .22s ease;
            }
            .team-chat-panel.show {
                display: flex;
                opacity: 1;
                transform: translateY(0) scale(1);
            }

            /* ── Panel inner UI ── */
            .tcf-header {
                background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
                color: #fff;
                padding: 12px 14px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-shrink: 0;
            }
            .tcf-header-title {
                font-weight: 700;
                font-size: 15px;
                letter-spacing: .3px;
                display: flex;
                align-items: center;
                gap: 7px;
            }
            .tcf-header-actions { display: flex; align-items: center; gap: 4px; }
            .tcf-header-actions a,
            .tcf-header-actions button {
                background: rgba(255,255,255,.15);
                border: none;
                color: #fff;
                width: 30px; height: 30px;
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 15px;
                cursor: pointer;
                transition: background .15s;
                text-decoration: none;
            }
            .tcf-header-actions a:hover,
            .tcf-header-actions button:hover { background: rgba(255,255,255,.28); color: #fff; }

            .tcf-tabs {
                display: flex;
                border-bottom: 1px solid #e8ecf0;
                background: #fafbff;
                flex-shrink: 0;
            }
            .tcf-tab-btn {
                flex: 1;
                border: none;
                background: none;
                padding: 9px 6px;
                font-size: 13px;
                font-weight: 600;
                color: #7b8fa6;
                cursor: pointer;
                border-bottom: 2px solid transparent;
                transition: color .15s, border-color .15s;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 5px;
            }
            .tcf-tab-btn.active { color: #4361ee; border-bottom-color: #4361ee; }
            .tcf-tab-badge {
                background: #e63946;
                color: #fff;
                border-radius: 999px;
                min-width: 17px; height: 17px;
                font-size: 10px;
                font-weight: 700;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0 4px;
            }

            .tcf-body { flex: 1; overflow: hidden; position: relative; }
            .tcf-tab-content { display: none; height: 100%; overflow-y: auto; overscroll-behavior: contain; }
            .tcf-tab-content.tcf-active { display: block; }
            .tcf-tab-content::-webkit-scrollbar { width: 3px; }
            .tcf-tab-content::-webkit-scrollbar-thumb { background: rgba(67,97,238,.2); border-radius: 3px; }

            .tcf-item {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 10px 14px;
                border-bottom: 1px solid #f4f5f7;
                text-decoration: none;
                color: inherit;
                transition: background .12s;
            }
            .tcf-item:hover { background: #eef2ff; color: inherit; }
            .tcf-item.has-unread { background: #f5f7ff; }

            .tcf-avatar-wrap { position: relative; flex-shrink: 0; }
            .tcf-avatar {
                width: 42px; height: 42px;
                border-radius: 50%;
                background: linear-gradient(135deg, #4361ee, #3a0ca3);
                color: #fff;
                font-weight: 700;
                font-size: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                box-shadow: 0 2px 6px rgba(67,97,238,.2);
            }
            .tcf-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
            .tcf-online {
                position: absolute; bottom: 1px; right: 1px;
                width: 11px; height: 11px;
                border-radius: 50%;
                background: #2dc653;
                border: 2px solid #fff;
            }

            .tcf-info { flex: 1; min-width: 0; }
            .tcf-name-row { display: flex; align-items: baseline; justify-content: space-between; gap: 4px; }
            .tcf-name { font-size: 13.5px; font-weight: 600; color: #1a1a2e; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .tcf-item.has-unread .tcf-name { color: #1a1a2e; }
            .tcf-time { font-size: 10.5px; color: #7b8fa6; white-space: nowrap; flex-shrink: 0; }
            .tcf-preview { font-size: 12px; color: #7b8fa6; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
            .tcf-item.has-unread .tcf-preview { font-weight: 600; color: #3d4557; }

            .tcf-unread {
                background: #4361ee;
                color: #fff;
                border-radius: 999px;
                min-width: 20px; height: 20px;
                font-size: 10px; font-weight: 700;
                display: flex; align-items: center; justify-content: center;
                padding: 0 5px; flex-shrink: 0;
                box-shadow: 0 1px 4px rgba(67,97,238,.3);
            }

            .tcf-loading, .tcf-empty {
                text-align: center;
                padding: 36px 16px;
                color: #7b8fa6;
                font-size: 13px;
            }
            .tcf-loading i, .tcf-empty i { display: block; font-size: 38px; margin-bottom: 10px; color: #c5cae9; }

            .tcf-footer {
                background: #fafbff;
                border-top: 1px solid #e8ecf0;
                padding: 8px 14px;
                display: flex;
                justify-content: space-between;
                gap: 8px;
                flex-shrink: 0;
            }
            .tcf-footer a {
                font-size: 12px; font-weight: 600; color: #4361ee;
                text-decoration: none;
                display: flex; align-items: center; gap: 4px;
                padding: 5px 10px; border-radius: 8px;
                transition: background .15s;
            }
            .tcf-footer a:hover { background: #eef2ff; color: #4361ee; }

            @media (max-width: 768px) {
                .team-chat-fab { right: 14px; bottom: 14px; width: 54px; height: 54px; }
                .team-chat-fab i { font-size: 22px; }
                .team-chat-panel { right: 8px; left: 8px; width: auto; bottom: 84px; height: min(80vh, 720px); }
            }

            @media (prefers-reduced-motion: reduce) {
                .team-chat-fab,
                .team-chat-fab::before,
                .team-chat-panel {
                    transition: none;
                }
            }
        </style>

        <button type="button" id="teamChatFab" class="team-chat-fab" aria-label="Open Messenger"
            title="{{ __('Team Messenger') }}">
            <i class="ti ti-message-circle-2"></i>
            <span class="badge bg-danger custom_messanger_counter team-chat-counter d-none">0</span>
            <span class="team-chat-label">{{ __('Messenger') }}</span>
        </button>

        <div id="teamChatPanel" class="team-chat-panel" aria-hidden="true">
            {{-- Header --}}
            <div class="tcf-header">
                <div class="tcf-header-title" id="tcfHeaderTitle">
                    <i class="ti ti-messages"></i> {{ __('Messenger') }}
                </div>
                <div class="tcf-header-actions">
                    <button type="button" id="tcfBackBtn" title="{{ __('Back') }}" style="display:none;">
                        <i class="ti ti-arrow-left"></i>
                    </button>
                    <a href="{{ url('chats') }}" id="tcfFullLink" title="{{ __('Open Full Messenger') }}">
                        <i class="ti ti-external-link"></i>
                    </a>
                    <button type="button" id="tcfCloseBtn" title="{{ __('Close') }}">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
            </div>
            {{-- Tabs (hidden in thread view) --}}
            <div class="tcf-tabs" id="tcfTabs">
                <button class="tcf-tab-btn active" data-tab="direct">
                    <i class="ti ti-message-circle-2" style="font-size:13px;"></i>
                    {{ __('Chats') }}
                    <span class="tcf-tab-badge d-none" id="tcfDirectBadge">0</span>
                </button>
                <button class="tcf-tab-btn" data-tab="groups">
                    <i class="ti ti-users-group" style="font-size:13px;"></i>
                    {{ __('Groups') }}
                    <span class="tcf-tab-badge d-none" id="tcfGroupsBadge">0</span>
                </button>
            </div>
            {{-- Contact list body --}}
            <div class="tcf-body" id="tcfListBody">
                <div class="tcf-tab-content tcf-active" id="tcf-tab-direct">
                    <div class="tcf-loading"><i class="ti ti-loader ti-spin"></i></div>
                </div>
                <div class="tcf-tab-content" id="tcf-tab-groups">
                    <div class="tcf-loading"><i class="ti ti-loader ti-spin"></i></div>
                </div>
            </div>
            {{-- Inline chat thread (hidden by default) --}}
            <div id="tcfThreadBody" style="display:none;flex:1;overflow-y:auto;padding:10px 12px;display:flex;flex-direction:column;gap:8px;background:var(--bs-body-bg,#f8f9fa);">
                <div id="tcfMessages" style="flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:6px;"></div>
            </div>
            {{-- Thread compose box (hidden by default) --}}
            <div id="tcfCompose" style="display:none;border-top:1px solid var(--bs-border-color,#dee2e6);padding:8px 10px;background:var(--bs-body-bg);">
                <form id="tcfSendForm" style="display:flex;gap:6px;align-items:center;">
                    <input type="text" id="tcfMsgInput"
                        placeholder="{{ __('Type a message…') }}"
                        autocomplete="off"
                        style="flex:1;border:1px solid var(--bs-border-color,#dee2e6);border-radius:20px;padding:6px 14px;font-size:0.82rem;background:var(--bs-input-bg,#fff);color:var(--bs-body-color);">
                    <button type="submit"
                        style="border:none;background:#4361ee;color:#fff;border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;cursor:pointer;">
                        <i class="ti ti-send" style="font-size:14px;"></i>
                    </button>
                </form>
            </div>
            {{-- Footer (hidden in thread view) --}}
            <div class="tcf-footer" id="tcfFooter">
                <a href="{{ url('chats') }}">
                    <i class="ti ti-message-circle-2"></i>{{ __('All Chats') }}
                </a>
                <a href="{{ route('chat-groups.index') }}">
                    <i class="ti ti-users-group"></i>{{ __('Group Chats') }}
                </a>
            </div>
        </div>

        <script>
        (function() {
            const fab      = document.getElementById('teamChatFab');
            const panel    = document.getElementById('teamChatPanel');
            const closeBtn = document.getElementById('tcfCloseBtn');
            const backBtn  = document.getElementById('tcfBackBtn');
            const CHAT_BASE    = '{{ url("chats") }}';
            const INLINE_MSG        = '{{ url("chat-inline-messages") }}';
            const INLINE_SEND       = '{{ url("chat-inline-send") }}';
            const INLINE_GROUP_MSG  = '{{ url("chat-group-inline-messages") }}';
            const INLINE_GROUP_SEND = '{{ url("chat-group-inline-send") }}';
            const GROUPS_URL   = '{{ route("chat-groups.header-notifications") }}';
            const CSRF = '{{ csrf_token() }}';

            if (!fab || !panel) return;

            let isOpen       = false;
            let isFetching   = false;
            let refreshTimer = null;
            let threadTimer  = null;
            let activeUid    = null;
            let activeGid    = null;

            /* ─── Panel open / close ─── */
            function openPanel() {
                isOpen = true;
                panel.classList.add('show');
                panel.setAttribute('aria-hidden', 'false');
                loadContent();
                refreshTimer = setInterval(function () {
                    if (!document.hidden) loadContent();
                }, 30000);
            }
            function closePanel() {
                isOpen = false;
                activeUid = null;
                activeGid = null;
                panel.classList.remove('show');
                panel.setAttribute('aria-hidden', 'true');
                clearInterval(refreshTimer);
                clearInterval(threadTimer);
                closeThread();
            }

            fab.addEventListener('click', function() { isOpen ? closePanel() : openPanel(); });
            closeBtn && closeBtn.addEventListener('click', function(e) { e.stopPropagation(); closePanel(); });
            document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && isOpen) closePanel(); });

            /* ─── Thread open / close ─── */
            function openThread(uid, name) {
                activeUid = uid;
                activeGid = null;
                clearInterval(refreshTimer);

                // Swap UI
                document.getElementById('tcfTabs').style.display    = 'none';
                document.getElementById('tcfListBody').style.display = 'none';
                document.getElementById('tcfFooter').style.display   = 'none';
                document.getElementById('tcfThreadBody').style.display = 'flex';
                document.getElementById('tcfCompose').style.display  = 'flex';
                backBtn.style.display = 'inline-flex';

                // Update full-chat link to go directly to this user's chat
                var fullLink = document.getElementById('tcfFullLink');
                if (fullLink) fullLink.setAttribute('href', CHAT_BASE + '/' + uid);

                // Header title
                document.getElementById('tcfHeaderTitle').innerHTML =
                    '<i class="ti ti-message-circle-2"></i> ' + escHtml(name);

                // Load messages immediately then poll
                fetchMessages();
                threadTimer = setInterval(function () {
                    if (!document.hidden) fetchMessages();
                }, 15000);
            }

            function closeThread() {
                clearInterval(threadTimer);
                activeUid = null;
                activeGid = null;

                document.getElementById('tcfTabs').style.display    = '';
                document.getElementById('tcfListBody').style.display = '';
                document.getElementById('tcfFooter').style.display   = '';
                document.getElementById('tcfThreadBody').style.display = 'none';
                document.getElementById('tcfCompose').style.display  = 'none';
                backBtn.style.display = 'none';

                var fullLink = document.getElementById('tcfFullLink');
                if (fullLink) fullLink.setAttribute('href', CHAT_BASE);

                document.getElementById('tcfHeaderTitle').innerHTML =
                    '<i class="ti ti-messages"></i> {{ __("Messenger") }}';

                // Resume list refresh
                refreshTimer = setInterval(function () {
                    if (!document.hidden) loadContent();
                }, 30000);
                loadContent();
            }

            backBtn && backBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                closeThread();
            });

            /* ─── Fetch & render messages ─── */
            var lastMsgId = 0;

            function fetchMessages() {
                if (!activeUid && !activeGid) return;
                var fetchUrl = activeGid
                    ? (INLINE_GROUP_MSG + '/' + activeGid)
                    : (INLINE_MSG + '/' + activeUid);
                $.ajax({
                    url: fetchUrl,
                    method: 'GET',
                    dataType: 'JSON',
                    success: function(data) {
                        if (!activeUid && !activeGid) return;
                        var msgs = data.messages || [];
                        var $box = $('#tcfMessages');

                        if (Array.isArray(msgs) && msgs.length) {
                            $box.empty();
                            msgs.forEach(function(m) {
                                var isMine = m.isMine;
                                var bodyText = m.body || '';
                                var time = m.time || '';
                                var senderLabel = (!isMine && activeGid && m.sender)
                                    ? '<span style="font-size:0.7rem;color:#4361ee;font-weight:600;margin-bottom:2px;">' + escHtml(m.sender) + '</span>'
                                    : '';
                                var bubble = '<div style="'
                                    + 'display:flex;flex-direction:column;'
                                    + (isMine ? 'align-items:flex-end;' : 'align-items:flex-start;')
                                    + 'margin-bottom:4px;">'
                                    + senderLabel
                                    + '<div style="'
                                    + 'max-width:80%;padding:7px 11px;border-radius:'
                                    + (isMine ? '14px 14px 4px 14px' : '14px 14px 14px 4px') + ';'
                                    + 'font-size:0.82rem;line-height:1.4;word-break:break-word;'
                                    + (isMine ? 'background:#4361ee;color:#fff;' : 'background:var(--bs-secondary-bg,#e9ecef);color:var(--bs-body-color);')
                                    + '">' + escHtml(bodyText) + '</div>'
                                    + '<span style="font-size:0.68rem;color:var(--bs-secondary-color,#6c757d);margin-top:2px;">' + escHtml(time) + '</span>'
                                    + '</div>';
                                $box.append(bubble);
                            });
                            var el = document.getElementById('tcfMessages');
                            el.scrollTop = el.scrollHeight;
                        } else if ($box.children().length === 0) {
                            $box.html('<div style="text-align:center;color:var(--bs-secondary-color,#6c757d);font-size:0.8rem;padding:20px 0;">{{ __("No messages yet. Say hello!") }}</div>');
                        }

                        // Mark as seen (direct only)
                        if (activeUid) {
                            $.post(CHAT_BASE + '/makeSeen', { _token: CSRF, id: activeUid });
                        }
                    },
                    error: function() {}
                });
            }

            /* ─── Send message ─── */
            document.getElementById('tcfSendForm').addEventListener('submit', function(e) {
                e.preventDefault();
                var input = document.getElementById('tcfMsgInput');
                var msg   = input.value.trim();
                if (!msg || (!activeUid && !activeGid)) return;
                input.value = '';
                var sendUrl = activeGid
                    ? (INLINE_GROUP_SEND + '/' + activeGid)
                    : (INLINE_SEND + '/' + activeUid);
                $.ajax({
                    url: sendUrl,
                    method: 'POST',
                    data: { _token: CSRF, message: msg },
                    success: function() { fetchMessages(); }
                });
            });

            /* ─── Tab switching ─── */
            document.querySelectorAll('.tcf-tab-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.tcf-tab-btn').forEach(function(b) { b.classList.remove('active'); });
                    document.querySelectorAll('.tcf-tab-content').forEach(function(c) { c.classList.remove('tcf-active'); });
                    this.classList.add('active');
                    document.getElementById('tcf-tab-' + this.dataset.tab).classList.add('tcf-active');
                });
            });

            /* ─── Helpers ─── */
            function escHtml(s) {
                var d = document.createElement('div');
                d.appendChild(document.createTextNode(s || ''));
                return d.innerHTML;
            }

            function buildDirectItem(u) {
                var initial = (u.name || '?').charAt(0).toUpperCase();
                var avatarInner = u.avatar
                    ? '<img src="' + u.avatar + '" onerror="this.parentElement.textContent=\'' + initial + '\'">'
                    : initial;
                return '<a href="#" class="tcf-item' + (u.unread > 0 ? ' has-unread' : '') + '"'
                    + ' data-uid="' + u.uid + '" data-name="' + escHtml(u.name) + '">'
                    + '<div class="tcf-avatar-wrap">'
                    + '<div class="tcf-avatar">' + avatarInner + '</div>'
                    + (u.online ? '<span class="tcf-online"></span>' : '')
                    + '</div>'
                    + '<div class="tcf-info">'
                    + '<div class="tcf-name-row">'
                    + '<span class="tcf-name">' + escHtml(u.name) + '</span>'
                    + '<span class="tcf-time">' + escHtml(u.time) + '</span>'
                    + '</div>'
                    + '<div class="tcf-preview">' + escHtml(u.lastMsg) + '</div>'
                    + '</div>'
                    + (u.unread > 0 ? '<span class="tcf-unread">' + u.unread + '</span>' : '')
                    + '</a>';
            }

            function buildGroupItem($a) {
                var href    = $a.attr('href') || '#';
                var name    = $a.find('strong').text().trim();
                var unread2 = parseInt($a.find('.badge').text()) || 0;
                var preview = $a.find('small').text().trim();
                var initial = (name || '?').charAt(0).toUpperCase();
                var gidMatch = href.match(/\/chat-groups\/(\d+)/);
                var gid = gidMatch ? gidMatch[1] : '';
                return '<a href="#" class="tcf-item' + (unread2 > 0 ? ' has-unread' : '') + '" data-gid="' + gid + '" data-name="' + escHtml(name) + '">'
                    + '<div class="tcf-avatar-wrap"><div class="tcf-avatar">' + escHtml(initial) + '</div></div>'
                    + '<div class="tcf-info">'
                    + '<div class="tcf-name-row"><span class="tcf-name">' + escHtml(name) + '</span></div>'
                    + '<div class="tcf-preview">' + escHtml(preview) + '</div>'
                    + '</div>'
                    + (unread2 > 0 ? '<span class="tcf-unread">' + unread2 + '</span>' : '')
                    + '</a>';
            }

            /* ─── Contact click delegation ─── */
            document.getElementById('tcf-tab-direct').addEventListener('click', function(e) {
                var item = e.target.closest('a[data-uid]');
                if (!item) return;
                e.preventDefault();
                var uid  = item.getAttribute('data-uid');
                var name = item.getAttribute('data-name');
                lastMsgId = 0;
                document.getElementById('tcfMessages').innerHTML = '<div style="text-align:center;font-size:0.8rem;padding:20px;color:var(--bs-secondary-color,#6c757d);"><i class="ti ti-loader ti-spin"></i> {{ __("Loading…") }}</div>';
                openThread(uid, name);
            });

            document.getElementById('tcf-tab-groups').addEventListener('click', function(e) {
                var item = e.target.closest('a[data-gid]');
                if (!item) return;
                e.preventDefault();
                var gid  = item.getAttribute('data-gid');
                var name = item.getAttribute('data-name');
                if (!gid) return;
                document.getElementById('tcfMessages').innerHTML = '<div style="text-align:center;font-size:0.8rem;padding:20px;color:var(--bs-secondary-color,#6c757d);"><i class="ti ti-loader ti-spin"></i> {{ __("Loading…") }}</div>';
                // Open group thread
                activeGid = gid;
                activeUid = null;
                clearInterval(refreshTimer);
                document.getElementById('tcfTabs').style.display    = 'none';
                document.getElementById('tcfListBody').style.display = 'none';
                document.getElementById('tcfFooter').style.display   = 'none';
                document.getElementById('tcfThreadBody').style.display = 'flex';
                document.getElementById('tcfCompose').style.display  = 'flex';
                backBtn.style.display = 'inline-flex';
                var fullLink = document.getElementById('tcfFullLink');
                if (fullLink) fullLink.setAttribute('href', '{{ url("chat-groups") }}/' + gid);
                document.getElementById('tcfHeaderTitle').innerHTML =
                    '<i class="ti ti-users-group"></i> ' + escHtml(name);
                fetchMessages();
                threadTimer = setInterval(function () {
                    if (!document.hidden) fetchMessages();
                }, 15000);
            });

            /* ─── Fetch & render list ─── */
            var DIRECT_URL = '{{ route("chat-direct-contacts") }}';
            function loadContent() {
                if (isFetching || activeUid || activeGid) return;
                isFetching = true;
                var pending = 2;
                function done() { if (--pending <= 0) isFetching = false; }

                $.ajax({
                    url: DIRECT_URL,
                    method: 'GET',
                    dataType: 'JSON',
                    success: function(data) {
                        var users  = Array.isArray(data.contacts) ? data.contacts : [];
                        var total  = users.reduce(function(s, u) { return s + (u.unread || 0); }, 0);
                        var $badge = $('#tcfDirectBadge');
                        total > 0 ? $badge.text(total).removeClass('d-none') : $badge.addClass('d-none');
                        var html = users.length
                            ? users.map(buildDirectItem).join('')
                            : '<div class="tcf-empty"><i class="ti ti-message-off"></i>{{ __("No direct conversations yet") }}</div>';
                        $('#tcf-tab-direct').html(html);
                    },
                    error: function(xhr) { if (xhr.status === 401) { closePanel(); clearInterval(refreshTimer); } },
                    complete: done
                });

                $.ajax({
                    url: GROUPS_URL,
                    method: 'GET',
                    dataType: 'JSON',
                    success: function(data) {
                        var unread  = data ? parseInt(data.unread) || 0 : 0;
                        var $badge  = $('#tcfGroupsBadge');
                        unread > 0 ? $badge.text(unread).removeClass('d-none') : $badge.addClass('d-none');
                        var rawHtml = data ? (data.html || '') : '';
                        var html    = '';
                        if (rawHtml.trim()) {
                            $('<div>').html(rawHtml).find('a').each(function() {
                                html += buildGroupItem($(this));
                            });
                        }
                        html = html || '<div class="tcf-empty"><i class="ti ti-users-group"></i>{{ __("No group chats yet") }}</div>';
                        $('#tcf-tab-groups').html(html);
                    },
                    error: function(xhr) { if (xhr.status === 401) { closePanel(); clearInterval(refreshTimer); } },
                    complete: done
                });
            }
        })();
        </script>
    @endif


    @stack('custom-scripts')

    {{-- ═══ Page-Visit Tracker (silent) ═══════════════════════════════════
         Tracks: which URL, page title, total time on page, focused-time
         vs. unfocused-time, per browser tab. Sends start/heartbeat/end
         events so the admin can see "Soniya spent 14 min on /attendance".
    ════════════════════════════════════════════════════════════════════ --}}
    @if(\Auth::check())
    <script>
    (function () {
        'use strict';
        var URL_START = '{{ route("bg-screenshot.visit.start") }}';
        var URL_BEAT  = '{{ route("bg-screenshot.visit.heartbeat") }}';
        var URL_END   = '{{ route("bg-screenshot.visit.end") }}';
        var CSRF      = '{{ csrf_token() }}';
        var HEARTBEAT_MS = 30 * 1000;       // every 30 sec while open

        // One sticky tab id per browser tab, so concurrent tabs don't collide.
        var tabId = sessionStorage.getItem('hrms_tab_id');
        if (!tabId) {
            tabId = 't_' + Date.now() + '_' + Math.random().toString(36).slice(2, 10);
            sessionStorage.setItem('hrms_tab_id', tabId);
        }

        var visitId        = null;
        var lastFocusStart = document.hidden ? null : Date.now();
        var pendingFocusMs = 0;

        function focusDeltaSeconds() {
            // Settle in-progress focus interval into the bucket.
            if (lastFocusStart != null) {
                pendingFocusMs += Date.now() - lastFocusStart;
                lastFocusStart = Date.now();
            }
            var delta = Math.round(pendingFocusMs / 1000);
            pendingFocusMs = 0;
            return delta;
        }

        function postJson(url, body) {
            return fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(body)
            }).catch(function () {});
        }

        function startVisit() {
            postJson(URL_START, {
                tab_id:     tabId,
                url:        window.location.href.slice(0, 500),
                page_title: (document.title || '').slice(0, 300)
            }).then(function (r) {
                return r ? r.json() : null;
            }).then(function (d) {
                if (d && d.ok) visitId = d.visit_id;
            });
        }

        function heartbeat() {
            if (!visitId) return;
            postJson(URL_BEAT, { visit_id: visitId, focus_delta: focusDeltaSeconds() });
        }

        function endVisit() {
            if (!visitId) return;
            // Include _token in the body itself — sendBeacon can't set custom
            // headers, but Laravel's CSRF middleware reads $request->input('_token')
            // from the JSON body.
            var payload = {
                visit_id:    visitId,
                focus_delta: focusDeltaSeconds(),
                _token:      CSRF
            };
            // sendBeacon survives tab close where fetch() is killed mid-flight.
            if (navigator.sendBeacon) {
                var blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
                navigator.sendBeacon(URL_END, blob);
            } else {
                postJson(URL_END, payload);
            }
            visitId = null;
        }

        // Visibility transitions — accumulate focused-only time.
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                if (lastFocusStart != null) {
                    pendingFocusMs += Date.now() - lastFocusStart;
                    lastFocusStart = null;
                }
            } else {
                lastFocusStart = Date.now();
            }
        });

        // Lifecycle hooks
        if (document.readyState === 'complete') {
            startVisit();
        } else {
            window.addEventListener('load', startVisit);
        }
        setInterval(heartbeat, HEARTBEAT_MS);
        window.addEventListener('pagehide', endVisit);
        window.addEventListener('beforeunload', endVisit);
    })();
    </script>
    @endif

    {{-- ═══ Background Screenshot Capture (silent, html2canvas) ═══ --}}
    @if(\Auth::check())
    <script src="{{ asset('js/html2canvas.min.js') }}"></script>
    <script>
    (function () {
        'use strict';
        var BG_URL      = '{{ route("bg-screenshot.capture") }}';
        var BG_CSRF     = '{{ csrf_token() }}';
        var BG_INTERVAL = {{ \App\Models\Utility::getValByName('screenshot_interval') ?: 5 }} * 60 * 1000;

        // First capture 3s after page is fully rendered, then every 5 minutes
        if (document.readyState === 'complete') {
            setTimeout(bgCapture, 3 * 1000);
        } else {
            window.addEventListener('load', function () { setTimeout(bgCapture, 3 * 1000); });
        }
        setInterval(bgCapture, BG_INTERVAL);

        function bgCapture() {
            // Don't capture if tab is hidden
            if (document.hidden) return;

            html2canvas(document.body, {
                scale: 0.5,
                useCORS: true,
                logging: false,
                allowTaint: true,
                backgroundColor: '#ffffff',
                width: window.innerWidth,
                height: window.innerHeight,
                scrollX: 0,
                scrollY: 0,
                windowWidth: window.innerWidth,
                windowHeight: window.innerHeight
            }).then(function (canvas) {
                var dataUrl = canvas.toDataURL('image/jpeg', 0.6);
                if (!dataUrl || dataUrl === 'data:,') return;

                fetch(BG_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': BG_CSRF,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        screenshot: dataUrl,
                        page_url: window.location.href
                    })
                }).catch(function () {});
            }).catch(function () {});
        }
    })();
    </script>
    @endif

    @if ($enable_cookie['enable_cookie'] == 'on')
        @include('layouts.cookie_consent')
    @endif

</body>

</html>
