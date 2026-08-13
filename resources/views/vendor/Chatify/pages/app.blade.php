@extends('layouts.admin')
@push('css-page')
    @include('Chatify::layouts.headLinks')

    <style>
        /* ================================================
           WHATSAPP-STYLE CHAT REDESIGN
           Modern, International Chat UI
        ================================================ */

        .dash-sidebar,
        .dash-header,
        .page-header,
        .dash-footer {
            display: none !important;
        }

        .dash-container {
            margin-left: 0 !important;
            padding-top: 0 !important;
        }

        .dash-content {
            padding: 8px !important;
        }

        .cards {
            background: transparent;
            padding: 0;
            border: 0;
        }

        .card-body {
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.12);
            border-radius: 16px;
            overflow: hidden;
        }

        .dropdown-menu {
            padding: 8px 0;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
            border: none;
            border-radius: 12px;
            z-index: 1000;
            min-width: 12rem;
        }

        /* ================================================
           MESSENGER CONTAINER - Increased Size
        ================================================ */
        .messenger {
            display: flex !important;
            height: calc(100vh - 140px) !important;
            min-height: 560px !important;
            max-height: calc(100vh - 100px);
            border-radius: 16px;
            overflow: hidden;
            background: #f0f2f5;
        }

        /* ================================================
           SIDEBAR / LIST VIEW - WhatsApp Style
        ================================================ */
        .messenger-listView {
            background: #ffffff;
            border-right: 1px solid #e9edef;
            width: 35%;
            min-width: 320px;
            max-width: 420px;
            height: 100%;
            flex-shrink: 0;
        }

        .messenger-listView .m-header {
            background: linear-gradient(135deg, #075e54 0%, #128c7e 100%);
            padding: 0 !important;
        }

        .messenger-listView .m-header > nav {
            padding: 12px 16px;
        }

        .messenger-listView .m-header > nav a,
        .messenger-listView .m-header > nav i {
            color: #fff !important;
        }

        /* Search Bar - WhatsApp Style */
        .messenger-search[type="text"] {
            margin: 8px 12px;
            width: calc(100% - 24px);
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            outline: none;
            background: #f0f2f5;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .messenger-search[type="text"]:focus {
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        /* Tabs - WhatsApp Style */
        .messenger-listView-tabs {
            background: linear-gradient(135deg, #075e54 0%, #128c7e 100%);
            padding: 0 12px;
            margin-top: 0;
            box-shadow: none;
        }

        .messenger-listView-tabs a {
            color: rgba(255,255,255,0.7) !important;
            padding: 14px 8px;
            font-size: 13px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .messenger-listView-tabs a span {
            color: rgba(255,255,255,0.8) !important;
            background: transparent !important;
            padding: 0 !important;
        }

        .messenger-listView-tabs a.active-tab {
            color: #ffffff !important;
            border-bottom-color: #ffffff;
        }

        .messenger-listView-tabs a.active-tab span {
            color: #ffffff !important;
            background: transparent !important;
        }

        .messenger-listView-tabs a:hover span {
            color: #ffffff !important;
        }

        /* Contact List Items - WhatsApp Style */
        .messenger-list-item {
            border-bottom: 1px solid #f0f2f5;
            transition: background 0.15s ease;
        }

        .messenger-list-item:hover {
            background: #f0f2f5;
        }

        .messenger-list-item td {
            padding: 12px 16px;
        }

        .messenger-list-item td p {
            font-size: 16px;
            font-weight: 500;
            color: #111b21;
            margin-bottom: 4px;
        }

        .messenger-list-item td span {
            font-size: 13px;
            color: #667781;
        }

        .m-list-active,
        .m-list-active:hover {
            background: #f0f2f5 !important;
        }

        .m-list-active td p,
        .m-list-active td span {
            color: #111b21 !important;
        }

        /* Avatar Styling */
        .avatar {
            border: 2px solid #e9edef;
        }

        .av-m {
            width: 50px;
            height: 50px;
        }

        /* ================================================
           MESSAGING VIEW - WhatsApp Style Chat Area
        ================================================ */
        .messenger-messagingView {
            background: #efeae2;
            position: relative;
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 0;
            height: 100%;
            overflow: hidden;
        }

        /* WhatsApp Chat Background Pattern */
        .messenger-messagingView::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #efeae2;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23d4cfc4' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
            z-index: 0;
            pointer-events: none;
        }

        .messenger-messagingView .m-body {
            position: relative;
            z-index: 1;
            padding: 0;
            background: transparent;
            flex: 1;
            min-height: 0;
            height: auto !important;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .messenger-messagingView .messages {
            flex: 1;
            min-height: 0;
            overflow-x: hidden;
            overflow-y: auto;
            padding: 12px 12px 8px;
            display: flex;
            flex-direction: column;
        }

        .messenger-messagingView .typing-indicator {
            flex-shrink: 0;
        }

        /* Chat Header - WhatsApp Style */
        .m-header-messaging {
            background: linear-gradient(135deg, #075e54 0%, #128c7e 100%) !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.15);
            padding: 10px 16px !important;
            z-index: 2;
            position: relative;
            flex-shrink: 0;
        }

        .m-header-messaging nav {
            display: flex;
            align-items: center;
        }

        .m-header-messaging .show-listView i,
        .m-header-messaging a i {
            color: #ffffff !important;
        }

        .m-header-messaging .user-name {
            color: #ffffff !important;
            font-size: 16px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: min(520px, 55vw);
            display: inline-block;
            word-break: normal;
            word-spacing: normal;
            letter-spacing: normal;
        }

        .m-header-messaging .header-avatar {
            border: 2px solid rgba(255,255,255,0.3);
        }

        .m-header-messaging .m-header-right a {
            background: rgba(255,255,255,0.15);
            padding: 8px;
            border-radius: 50%;
            margin-left: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s ease;
        }

        .m-header-messaging .m-header-right a:hover {
            background: rgba(255,255,255,0.25);
        }

        /* ================================================
           MESSAGE BUBBLES - WhatsApp Style
        ================================================ */
        .message-card {
            margin: 4px 0;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .message-card p {
            max-width: 65%;
            padding: 8px 12px;
            padding-bottom: 18px;
            border-radius: 8px;
            font-size: 14.5px;
            line-height: 1.45;
            position: relative;
            box-shadow: 0 1px 1px rgba(0,0,0,0.08);
        }

        /* Received Messages (Left) */
        .message-card:not(.mc-sender) p {
            background: #ffffff;
            color: #111b21;
            border-top-left-radius: 0;
            margin-left: 0;
        }

        .message-card:not(.mc-sender) p::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 0;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 0 8px 8px 0;
            border-color: transparent #ffffff transparent transparent;
        }

        /* Sent Messages (Right) - WhatsApp Green */
        .mc-sender {
            direction: rtl;
        }

        .mc-sender p {
            direction: ltr;
            background: #d9fdd3 !important;
            color: #111b21 !important;
            border-top-right-radius: 0;
            margin-right: 0;
        }

        .mc-sender p::before {
            content: '';
            position: absolute;
            right: -8px;
            top: 0;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 8px 8px 0 0;
            border-color: #d9fdd3 transparent transparent transparent;
        }

        /* Message Time - WhatsApp Style */
        .message-card p sub,
        .message-time {
            position: absolute;
            bottom: 4px;
            right: 8px;
            font-size: 11px;
            color: #667781 !important;
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .mc-sender p sub {
            color: #667781 !important;
        }

        /* Read Receipts Ticks */
        .message-time .fa-check,
        .message-time .fa-check-double {
            color: #53bdeb !important;
            font-size: 12px;
        }

        /* Image Messages */
        .message-card .image-file,
        .message-card .chat-image {
            border-radius: 8px;
            overflow: hidden;
        }

        /* ================================================
           SEND MESSAGE AREA - WhatsApp Style
        ================================================ */
        .messenger-sendCard {
            background: #f0f2f5;
            padding: 10px 16px;
            border-top: 1px solid #e9edef;
            z-index: 2;
            position: relative !important;
            bottom: auto !important;
            left: auto !important;
            width: 100%;
            flex-shrink: 0;
            margin-top: auto;
        }

        .messenger-sendCard form {
            background: #ffffff;
            border-radius: 24px;
            padding: 4px 8px;
            align-items: center;
            box-shadow: 0 1px 2px rgba(0,0,0,0.08);
        }

        .messenger-sendCard label {
            padding: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .messenger-sendCard label span,
        .messenger-sendCard label .fas,
        .messenger-sendCard label .far {
            color: #54656f !important;
            background: transparent !important;
            padding: 0 !important;
            font-size: 20px;
            transition: color 0.2s ease;
        }

        .messenger-sendCard label:hover span,
        .messenger-sendCard label:hover .fas {
            color: #128c7e !important;
        }

        .messenger-sendCard .m-send {
            border: none;
            padding: 12px 8px;
            font-size: 15px;
            background: transparent;
            min-height: 44px;
            resize: none;
        }

        .messenger-sendCard .m-send::placeholder {
            color: #8696a0;
        }

        .messenger-sendCard button {
            padding: 8px 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .messenger-sendCard button span,
        .messenger-sendCard button .fas,
        .messenger-sendCard button .far {
            color: #ffffff !important;
            background: #00a884 !important;
            padding: 10px !important;
            border-radius: 50%;
            font-size: 16px;
            transition: all 0.2s ease;
        }

        .messenger-sendCard button:hover span,
        .messenger-sendCard button:hover .fas {
            background: #008069 !important;
            transform: scale(1.05);
        }

        /* ================================================
           TYPING INDICATOR - WhatsApp Style
        ================================================ */
        .typing-indicator {
            padding: 8px 0;
        }

        .typing-indicator .message-card p {
            background: #ffffff;
            padding: 12px 18px;
            border-radius: 8px;
            border-top-left-radius: 0;
        }

        .typing-indicator .typing-dots {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .typing-indicator .dot {
            width: 8px;
            height: 8px;
            background: #8696a0;
            border-radius: 50%;
            animation: typingBounce 1.4s infinite ease-in-out;
        }

        .typing-indicator .dot-1 { animation-delay: 0s; }
        .typing-indicator .dot-2 { animation-delay: 0.2s; }
        .typing-indicator .dot-3 { animation-delay: 0.4s; }

        @keyframes typingBounce {
            0%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-6px); }
        }

        /* ================================================
           INFO VIEW - Modern Style
        ================================================ */
        .messenger-infoView {
            background: #ffffff;
            border-left: 1px solid #e9edef;
            height: 100%;
            flex-shrink: 0;
        }

        .messenger-infoView nav a {
            color: #54656f;
        }

        .messenger-infoView nav a:hover {
            color: #111b21;
        }

        /* ================================================
           FAVORITES SECTION
        ================================================ */
        .messenger-favorites div.avatar {
            box-shadow: 0 0 0 3px #00a884 !important;
            border: 2px solid #ffffff;
        }

        .messenger-title {
            color: #008069;
            font-size: 13px;
            font-weight: 600;
            padding: 16px 16px 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ================================================
           SCROLLBAR - Modern Style
        ================================================ */
        .messenger .app-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .messenger .app-scroll::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.2);
            border-radius: 3px;
        }

        .messenger .app-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(0,0,0,0.3);
        }

        /* ================================================
           INTERNET CONNECTION STATUS
        ================================================ */
        .internet-connection {
            background: rgba(0,0,0,0.85);
            border-radius: 4px;
            margin: 8px;
            font-size: 13px;
        }

        .ic-connected {
            background: #00a884;
            border-radius: 4px;
        }

        /* ================================================
           MESSAGE HINT (Empty State)
        ================================================ */
        .message-hint {
            z-index: 1;
            position: relative;
            margin: auto !important;
            margin-top: auto !important;
            margin-bottom: auto !important;
            text-align: center;
            width: 100%;
        }

        .message-hint.center-el,
        .messages > .message-hint {
            margin-top: auto !important;
            margin-bottom: auto !important;
        }

        .message-hint span {
            background: rgba(255,255,255,0.95);
            color: #54656f;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: inline-block;
        }

        /* ================================================
           ICONS OVERRIDE
        ================================================ */
        .messenger i,
        .messenger .fas,
        .messenger .far {
            color: #54656f;
        }

        .m-header-right a i,
        .listView-x i {
            color: #ffffff !important;
        }

        /* Online Status Indicator */
        .activeStatus {
            background: #00a884;
            border: 2px solid #ffffff;
            width: 14px;
            height: 14px;
        }
    </style>
@endpush
@php
    // $profile=\App\Models\Utility::get_file('/'.config('chatify.user_avatar.folder'));
    $profile = \App\Models\Utility::get_file('uploads/avatar/');
    $setting = App\Models\Utility::colorset();
    $color = !empty($setting['theme_color']) ? $setting['theme_color'] : 'theme-3';
    $setting = \App\Models\Utility::colorset();
    if (isset($setting['color_flag']) && $setting['color_flag'] == 'true') {
        $color = 'custom-color';
    } else {
        $color = $color;
    }

    $isEmbedded = request()->get('embed') == '1';

@endphp
@section('page-title')
    {{ __('Messenger') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Messenger') }}</li>
@endsection

@if ($setting['cust_darklayout'] == 'on')
    <style>
        .cards {
            background-color: #272727;
            padding: 25px 25px;
        }
        .cards.chat-embedded {
            background-color: #fff !important;
            padding: 0 !important;
        }
    </style>
@else
    <style>
        .cards {
            background-color: #fff;
            padding: 25px 25px;
        }
        .cards.chat-embedded {
            padding: 0 !important;
        }
    </style>
@endif

@section('content')
    <div class="cards {{ $isEmbedded ? '' : 'rounded-12' }} {{ $isEmbedded ? 'mt-0' : 'mt-4' }} p-0 {{ $isEmbedded ? 'chat-embedded' : '' }}">
        <div class="card-body">
            <div class="messenger rounded min-h-750 overflow-hidden">
                {{-- ----------------------Users/Groups lists side---------------------- --}}
                <div class="messenger-listView">
                    {{-- Header and search bar --}}
                    <div class="m-header">
                        @if ($isEmbedded)
                            <div class="chat-embed-heading">
                                <div class="chat-embed-title-wrap">
                                    <span class="chat-embed-icon"><i class="fas fa-comment-dots"></i></span>
                                    <div>
                                        <div class="chat-embed-title">{{ __('Messages') }}</div>
                                        <div class="chat-embed-subtitle">{{ __('Chat with your team') }}</div>
                                    </div>
                                </div>
                                <a href="#" class="listView-x chat-embed-close"><i class="fas fa-times"></i></a>
                            </div>
                        @endif
                        <nav>
                            <nav class="m-header-right">
                                <a href="#" class="listView-x"><i class="fas fa-times"></i></a>
                            </nav>
                        </nav>
                        {{-- Search input --}}
                        <input type="text" class="messenger-search"
                            placeholder="{{ $isEmbedded ? __('Search users...') : __('Search') }}" />
                        {{-- Tabs --}}
                        @if (\Auth::user()->type == 'super admin')
                        @endif
                        <div class="messenger-listView-tabs">
                            <a href="#" @if ($route == 'user') class="active-tab" @endif data-view="users">
                                <span class="fas fa-clock" title="{{ __('Recent') }}"></span>
                                @if ($isEmbedded)
                                    <span class="chat-tab-label">{{ __('Users') }}</span>
                                @endif
                            </a>
                            <a href="#" @if ($route == 'group') class="active-tab" @endif
                                data-view="groups">
                                <span class="fas fa-users" title="{{ __('Members') }}"></span>
                                @if ($isEmbedded)
                                    <span class="chat-tab-label">{{ __('Conversations') }}</span>
                                @endif
                            </a>
                            <a href="{{ route('chat-groups.index') }}" title="{{ __('Open Group Chat') }}">
                                <span class="fas fa-user-friends"></span>
                                @if ($isEmbedded)
                                    <span class="chat-tab-label">{{ __('Groups') }}</span>
                                @endif
                            </a>
                        </div>
                    </div>
                    {{-- tabs and lists --}}
                    <div class="m-body">
                        {{-- Lists [Users/Group] --}}
                        {{-- ---------------- [ User Tab ] ---------------- --}}
                        <div class="@if ($route == 'user') show @endif messenger-tab app-scroll"
                            data-view="users">

                            <p class="messenger-title">{{ __('All Conversations') }}</p>
                            <div class="combined-conversations app-scroll-thin"></div>

                            {{-- Favorites --}}
                            <p class="messenger-title">Favorites</p>
                            <div class="messenger-favorites app-scroll-thin"></div>

                            <p class="messenger-title">{{ __('Group Messages') }}</p>
                            <div class="group-favorites app-scroll-thin"></div>

                            {{-- Saved Messages --}}
                            {!! view('Chatify::layouts.listItem', ['get' => 'saved', 'id' => $id])->render() !!}

                            {{-- Contact --}}
                            <div class="listOfContacts" style="width: 100%;height: calc(100% - 200px);position: relative;">
                            </div>
                        </div>

                        {{-- ---------------- [ Group Tab ] ---------------- --}}
                        <div class="all_members @if ($route == 'group') show @endif messenger-tab app-scroll"
                            data-view="groups">
                            <div class="group-tab-list"></div>
                        </div>

                        {{-- ---------------- [ Search Tab ] ---------------- --}}
                        <div class="messenger-tab app-scroll" data-view="search">
                            {{-- items --}}
                            <p class="messenger-title">{{ __('Search') }}</p>
                            <div class="search-records">
                                <p class="message-hint center-el"><span>{{ __('Type to search..') }}</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ----------------------Messaging side---------------------- --}}
                <div class="messenger-messagingView">
                    {{-- header title [conversation name] amd buttons --}}
                    <div class="m-header m-header-messaging">
                        <nav class="d-flex a;align-items-center justify-content-between">
                            {{-- header back button, avatar and user name --}}
                            <div style="display: flex;">
                                <a href="#" class="show-listView"><i class="fas fa-arrow-left"></i> </a>
                                @if (!empty($user->avatar))
                                    <div class="avatar av-s header-avatar"
                                        style="margin: 0px 10px; margin-top: -5px; margin-bottom: -5px;background-image: url('{{ asset('/storage/avatars/' . $user->avatar) }}');">
                                    </div>
                                @else
                                    <div class="avatar av-s header-avatar"
                                        style="margin: 0px 10px; margin-top: -5px; margin-bottom: -5px;background-image: url('{{ asset('/storage/avatars/avatar.png') }}');">
                                    </div>
                                @endif
                                <a href="#" class="user-name">{{ config('chatify.name') }}</a>
                            </div>
                            {{-- header buttons --}}
                            <nav class="m-header-right">
                                <a href="#" class="add-to-favorite my-lg-1 my-xl-1 mx-lg-1 mx-xl-1"><i
                                        class="fas fa-star"></i></a>
                                <a href="#" class="show-infoSide my-lg-1 my-xl-1 mx-lg-1 mx-xl-2"><i
                                        class="fas fa-info-circle"></i></a>
                            </nav>
                        </nav>
                    </div>
                    {{-- Internet connection --}}
                    <div class="internet-connection">
                        <span class="ic-connected">{{ __('Connected') }}</span>
                        <span class="ic-connecting">{{ __('Connecting...') }}</span>
                        <span class="ic-noInternet">{{ __('Please add pusher settings for using messenger.') }}</span>
                    </div>
                    {{-- Messaging area --}}
                    <div class="m-body app-scroll">
                        <div class="messages">
                            <p class="message-hint center-el">
                                <span>{{ __('Please select a chat to start messaging') }}</span>
                            </p>
                        </div>
                        {{-- Typing indicator --}}
                        <div class="typing-indicator">
                            <div class="message-card typing">
                                <p>
                                    <span class="typing-dots">
                                        <span class="dot dot-1"></span>
                                        <span class="dot dot-2"></span>
                                        <span class="dot dot-3"></span>
                                    </span>
                                </p>
                            </div>
                        </div>
                        {{-- Send Message Form --}}
                        @include('Chatify::layouts.sendForm')
                    </div>
                </div>
                {{-- ---------------------- Info side ---------------------- --}}
                <div class="messenger-infoView app-scroll text-center">
                    {{-- nav actions --}}
                    <nav class="text-center">
                        <a href="#"><i class="fas fa-times"></i></a>
                    </nav>
                    {!! view('Chatify::layouts.info')->render() !!}
                </div>
            </div>
        </div>
    </div>
@endsection

@if ($isEmbedded)
    @push('css-page')
        <style>
            /* ================================================
               EMBEDDED CHAT - WHATSAPP STYLE (CLEAN)
            ================================================ */
            
            /* Remove ALL extra spacing from document */
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                overflow: hidden !important;
                height: 100% !important;
                background: #ffffff !important;
            }

            /* Reset ALL containers in the layout */
            section.dash-container,
            .dash-container,
            .dash-content,
            .dash-content > *:not(.cards),
            .row,
            .col,
            [class*="col-"] {
                padding: 0 !important;
                margin: 0 !important;
                min-height: 100% !important;
                border: none !important;
            }
            
            .page-header,
            .page-block,
            .breadcrumb {
                display: none !important;
                height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .cards.chat-embedded {
                border-radius: 0 !important;
                overflow: hidden !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                height: 100% !important;
                min-height: 100% !important;
            }

            .cards.chat-embedded .card-body {
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                overflow: hidden !important;
                height: 100% !important;
            }

            .cards.chat-embedded .messenger {
                min-height: 100% !important;
                max-height: 100% !important;
                height: 100% !important;
                border-radius: 0 !important;
                overflow: hidden !important;
                background: #ffffff !important;
                display: flex !important;
                flex-direction: column !important;
            }

            /* REMOVE ALL SCROLLBARS except one */
            .cards.chat-embedded,
            .cards.chat-embedded * {
                scrollbar-width: none !important;
                -ms-overflow-style: none !important;
            }
            
            .cards.chat-embedded::-webkit-scrollbar,
            .cards.chat-embedded *::-webkit-scrollbar {
                display: none !important;
                width: 0 !important;
                height: 0 !important;
            }

            /* ================================================
               SIDEBAR - WhatsApp Style (Full Width)
            ================================================ */
            .cards.chat-embedded .messenger-listView {
                border-right: none;
                background: #ffffff;
                width: 100%;
                min-width: 100%;
                max-width: 100%;
                display: flex;
                flex-direction: column;
                position: relative;
                overflow: hidden !important;
                height: 100vh;
            }

            .cards.chat-embedded .m-header {
                background: transparent;
                padding: 0;
                border-bottom: none;
                position: relative;
                flex-shrink: 0;
            }

            .cards.chat-embedded .m-header>nav {
                display: none !important;
            }
            
            .cards.chat-embedded .messenger-listView .m-body {
                margin-top: 0 !important;
            }

            /* Header - WhatsApp Green Theme */
            .cards.chat-embedded .chat-embed-heading {
                min-height: 60px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 12px 16px;
                background: linear-gradient(135deg, #075e54 0%, #128c7e 100%);
            }

            .cards.chat-embedded .chat-embed-title-wrap {
                display: flex;
                align-items: center;
                gap: 12px;
                color: #fff;
            }

            .cards.chat-embedded .chat-embed-icon {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.2);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 18px;
            }

            .cards.chat-embedded .chat-embed-icon i {
                color: #fff !important;
            }

            .cards.chat-embedded .chat-embed-title {
                font-size: 20px;
                font-weight: 600;
                line-height: 1.2;
            }

            .cards.chat-embedded .chat-embed-subtitle {
                font-size: 13px;
                opacity: 0.9;
                line-height: 1.2;
            }

            /* Search - WhatsApp Style */
            .cards.chat-embedded .m-header .messenger-search {
                margin: 10px 12px;
                width: calc(100% - 24px);
                border-radius: 8px;
                border: none;
                background: #f0f2f5;
                min-height: 40px;
                font-size: 14px;
                padding: 0 16px;
                transition: all 0.2s ease;
            }

            .cards.chat-embedded .m-header .messenger-search:focus {
                background: #ffffff;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }

            /* Tabs - WhatsApp Style */
            .cards.chat-embedded .messenger-listView-tabs {
                padding: 0;
                background: linear-gradient(135deg, #075e54 0%, #128c7e 100%);
                border-bottom: none;
                display: flex;
                align-items: stretch;
                gap: 0;
            }

            .cards.chat-embedded .messenger-listView-tabs a {
                min-height: 48px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                color: rgba(255,255,255,0.7) !important;
                flex: 1;
                font-size: 13px;
                font-weight: 500;
                text-decoration: none;
                line-height: 1;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                border-bottom: 3px solid transparent;
                transition: all 0.2s ease;
            }

            .cards.chat-embedded .messenger-listView-tabs a span {
                color: rgba(255,255,255,0.7) !important;
                background: transparent !important;
                padding: 0 !important;
            }

            .cards.chat-embedded .messenger-listView-tabs a.active-tab {
                color: #ffffff !important;
                border-bottom-color: #ffffff;
            }

            .cards.chat-embedded .messenger-listView-tabs a.active-tab span {
                color: #ffffff !important;
            }

            .cards.chat-embedded .messenger-listView-tabs a:hover span {
                color: #ffffff !important;
            }

            .cards.chat-embedded .chat-tab-label {
                white-space: nowrap;
            }

            /* Close Button */
            .cards.chat-embedded .chat-embed-close {
                width: 36px;
                height: 36px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.15);
                border: none !important;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                text-decoration: none;
                transition: background 0.2s ease;
            }

            .cards.chat-embedded .chat-embed-close:hover {
                background: rgba(255, 255, 255, 0.25);
            }

            .cards.chat-embedded .chat-embed-close i {
                color: #fff !important;
                font-size: 16px;
            }

            /* Content Area - List View Body - SINGLE SCROLLBAR */
            .cards.chat-embedded .messenger-listView .m-body {
                background: #ffffff !important;
                padding-top: 0 !important;
                margin-top: 0 !important;
                flex: 1 !important;
                overflow-y: scroll !important;
                overflow-x: hidden !important;
                scrollbar-width: thin !important;
                scrollbar-color: #c5c5c5 transparent !important;
            }

            /* Custom scrollbar for m-body only - Override global hide */
            .cards.chat-embedded .messenger-listView .m-body::-webkit-scrollbar {
                width: 5px !important;
                display: block !important;
                background: transparent !important;
            }

            .cards.chat-embedded .messenger-listView .m-body::-webkit-scrollbar-track {
                background: transparent !important;
            }

            .cards.chat-embedded .messenger-listView .m-body::-webkit-scrollbar-thumb {
                background: #c5c5c5 !important;
                border-radius: 3px !important;
            }

            .cards.chat-embedded .messenger-listView .m-body::-webkit-scrollbar-thumb:hover {
                background: #a0a0a0 !important;
            }

            /* Messenger tabs inside m-body */
            .cards.chat-embedded .messenger-tab {
                overflow: visible !important;
                height: auto !important;
            }

            .cards.chat-embedded .messenger-title {
                font-size: 12px;
                font-weight: 600;
                color: #008069;
                letter-spacing: 0.3px;
                margin: 16px 16px 10px;
                text-transform: uppercase;
                line-height: 1.2;
            }

            /* Contact List Items - WhatsApp Style */
            .cards.chat-embedded .listOfContacts .messenger-list-item {
                border-bottom: 1px solid #f0f2f5;
                padding: 12px 16px;
                transition: background 0.15s ease;
            }

            .cards.chat-embedded .listOfContacts .messenger-list-item:hover {
                background: #f0f2f5;
            }

            .cards.chat-embedded .messenger-list-item .m-list-details p {
                font-size: 16px;
                font-weight: 500;
                color: #111b21;
            }

            .cards.chat-embedded .messenger-list-item .m-list-details small,
            .cards.chat-embedded .messenger-list-item .m-list-details span {
                font-size: 13px;
                color: #667781;
            }

            /* Combined Conversations & Groups - NO SCROLL */
            .cards.chat-embedded .combined-conversations,
            .cards.chat-embedded .group-favorites,
            .cards.chat-embedded .group-tab-list,
            .cards.chat-embedded .messenger-favorites,
            .cards.chat-embedded .listOfContacts {
                border: none;
                border-radius: 0;
                margin: 0;
                background: #ffffff;
                overflow: visible !important;
                height: auto !important;
            }

            .cards.chat-embedded .combined-conversations .combined-heading {
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.3px;
                color: #008069;
                background: #f0f2f5;
                border-top: none;
                border-bottom: 1px solid #e9edef;
                padding: 10px 16px;
            }

            .cards.chat-embedded .combined-conversations .combined-heading:first-child {
                border-top: 1px solid #e9edef;
            }

            /* Group Chat Items - WhatsApp Style */
            .cards.chat-embedded .group-chat-item {
                display: flex;
                align-items: flex-start;
                gap: 12px;
                padding: 14px 16px;
                border-bottom: 1px solid #f0f2f5;
                text-decoration: none;
                transition: background 0.15s ease;
            }

            .cards.chat-embedded .group-chat-item:hover {
                background: #f0f2f5;
            }

            .cards.chat-embedded .group-chat-item .avatar {
                width: 50px;
                height: 50px;
                min-width: 50px;
                border-radius: 50%;
                background-size: cover;
                background-position: center;
                border: 2px solid #e9edef;
            }

            .cards.chat-embedded .group-chat-item .m-list-details {
                flex: 1;
                min-width: 0;
            }

            .cards.chat-embedded .group-chat-item .m-list-details p {
                margin: 0 0 4px;
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 6px;
                font-size: 16px;
                font-weight: 500;
                color: #111b21;
            }

            .cards.chat-embedded .group-chat-item .m-list-details span {
                display: block;
                margin: 0;
                font-size: 14px;
                line-height: 1.4;
                color: #667781;
                word-break: break-word;
            }

            .cards.chat-embedded .group-chat-item .m-list-action {
                margin-left: auto;
                min-width: 54px;
                text-align: right;
            }

            .cards.chat-embedded .group-chat-item:last-child {
                border-bottom: 0;
            }

            .cards.chat-embedded .group-chat-item .group-chip {
                font-size: 10px;
                font-weight: 600;
                color: #ffffff;
                background: #00a884;
                border-radius: 12px;
                padding: 3px 8px;
                margin-left: 6px;
            }

            .cards.chat-embedded .group-chat-item .m-list-action b {
                min-width: 20px;
                height: 20px;
                line-height: 20px;
                text-align: center;
                border-radius: 50%;
                background: #25d366;
                color: #fff;
                font-size: 11px;
                display: inline-block;
            }

            .cards.chat-embedded .group-chat-item .m-list-action small {
                display: block;
                color: #667781;
                font-size: 12px;
                margin-bottom: 4px;
            }

            .cards.chat-embedded .combined-conversations .messenger-list-item {
                margin: 0;
                border-radius: 0;
                border-bottom: 1px solid #f0f2f5;
                padding: 14px 16px;
            }

            .cards.chat-embedded .combined-conversations .messenger-list-item:last-child {
                border-bottom: 0;
            }

            /* ================================================
               MESSAGING VIEW - WhatsApp Chat Area (HIDDEN BY DEFAULT)
            ================================================ */
            .cards.chat-embedded .messenger-messagingView {
                background: #efeae2;
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 10;
                display: none;
                flex-direction: column;
                overflow: hidden;
            }

            .cards.chat-embedded .messenger-messagingView.show-messages-view {
                display: flex;
            }

            .cards.chat-embedded .messenger-messagingView::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #efeae2;
                background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23d4cfc4' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
                opacity: 0.5;
                z-index: 0;
            }

            .cards.chat-embedded .m-header-messaging {
                background: linear-gradient(135deg, #075e54 0%, #128c7e 100%) !important;
                padding: 12px 16px !important;
                z-index: 2;
                position: relative;
            }

            .cards.chat-embedded .m-header-messaging .show-listView {
                display: inline-flex !important;
                width: 36px;
                height: 36px;
                align-items: center;
                justify-content: center;
                background: rgba(255,255,255,0.15);
                border-radius: 50%;
                margin-right: 10px;
            }

            .cards.chat-embedded .m-header-messaging .show-listView i {
                color: #ffffff !important;
            }

            .cards.chat-embedded .m-header-messaging .user-name {
                color: #ffffff !important;
                font-size: 16px;
                font-weight: 600;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: min(420px, 50vw);
                display: inline-block;
                word-break: normal;
                word-spacing: normal;
                letter-spacing: normal;
            }

            .cards.chat-embedded .m-header-messaging a i {
                color: #ffffff !important;
            }

            .cards.chat-embedded .m-header-messaging .m-header-right {
                display: flex;
                gap: 8px;
            }

            .cards.chat-embedded .m-header-messaging .m-header-right a {
                width: 36px;
                height: 36px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: rgba(255,255,255,0.15);
                border-radius: 50%;
            }

            .cards.chat-embedded .messenger-messagingView .m-body {
                background: transparent;
                z-index: 1;
                position: relative;
                height: auto !important;
                flex: 1;
                min-height: 0;
                display: flex;
                flex-direction: column;
                overflow: hidden;
                padding: 0;
            }

            .cards.chat-embedded .messenger-messagingView .messages {
                flex: 1;
                min-height: 0;
                overflow-y: auto;
                padding: 12px;
            }

            /* Hide Info View by default in embedded */
            .cards.chat-embedded .messenger-infoView {
                display: none !important;
            }

            /* Message Bubbles - WhatsApp Style */
            .cards.chat-embedded .message-card p {
                max-width: 70%;
                padding: 8px 12px;
                padding-bottom: 20px;
                border-radius: 8px;
                font-size: 14px;
                line-height: 1.4;
                position: relative;
                box-shadow: 0 1px 1px rgba(0,0,0,0.08);
            }

            .cards.chat-embedded .message-card:not(.mc-sender) p {
                background: #ffffff;
                color: #111b21;
                border-top-left-radius: 0;
            }

            .cards.chat-embedded .mc-sender p {
                background: #d9fdd3 !important;
                color: #111b21 !important;
                border-top-right-radius: 0;
            }

            /* Message Send Card - WhatsApp Style */
            .cards.chat-embedded .messenger-sendCard {
                background: #f0f2f5;
                padding: 10px 12px;
                z-index: 2;
                position: relative !important;
                bottom: auto !important;
                flex-shrink: 0;
                width: 100%;
            }

            .cards.chat-embedded .messenger-sendCard form {
                background: #ffffff;
                border-radius: 24px;
                padding: 4px 8px;
                box-shadow: 0 1px 2px rgba(0,0,0,0.08);
            }

            .cards.chat-embedded .messenger-sendCard label span,
            .cards.chat-embedded .messenger-sendCard label .fas {
                color: #54656f !important;
                background: transparent !important;
                padding: 0 !important;
                font-size: 18px;
            }

            .cards.chat-embedded .messenger-sendCard button span,
            .cards.chat-embedded .messenger-sendCard button .fas {
                color: #ffffff !important;
                background: #00a884 !important;
                padding: 10px !important;
                border-radius: 50%;
                font-size: 14px;
            }
        </style>
    @endpush
@endif

@push('scripts')
    @include('Chatify::layouts.modals')
    <script>
        (function() {
            const labelUsers = "{{ __('Users') }}";
            const labelGroups = "{{ __('Groups') }}";
            const labelNoConversations = "{{ __('No conversations yet') }}";
            let lastGroupCombinedHtml = '';

            function buildCombinedConversations() {
                const $target = $('.combined-conversations');
                if (!$target.length) {
                    return;
                }

                const userItems = $('.listOfContacts .messenger-list-item').slice(0, 8).map(function() {
                    return this.outerHTML;
                }).get().join('');

                let html = '';
                if (userItems) {
                    html += '<div class="combined-heading">' + labelUsers + '</div>' + userItems;
                }
                if (lastGroupCombinedHtml) {
                    html += '<div class="combined-heading">' + labelGroups + '</div>' + lastGroupCombinedHtml;
                }

                if (!html) {
                    html = '<p class="message-hint center-el"><span>' + labelNoConversations + '</span></p>';
                }

                $target.html(html);
            }

            function loadGroupItems() {
                $.ajax({
                    url: "{{ route('chat-groups.chatbox-favorites') }}",
                    method: 'GET',
                    dataType: 'json',
                    success: function(res) {
                        const groupsHtml = (res && (res.groups_html || res.html)) ? (res.groups_html || res.html) : '<p class="message-hint center-el"><span>No group messages</span></p>';
                        lastGroupCombinedHtml = (res && res.combined_html) ? res.combined_html : '';

                        $('.group-favorites').html(groupsHtml);
                        $('.group-tab-list').html(groupsHtml);
                        buildCombinedConversations();
                    }
                });
            }

            loadGroupItems();
            setInterval(loadGroupItems, 15000);
            setInterval(buildCombinedConversations, 10000);

            const listContainer = document.querySelector('.listOfContacts');
            if (listContainer && typeof MutationObserver !== 'undefined') {
                const observer = new MutationObserver(function() {
                    buildCombinedConversations();
                });
                observer.observe(listContainer, {
                    childList: true,
                    subtree: true
                });
            }

            // Embedded Chat View Handling
            const isEmbedded = document.querySelector('.chat-embedded');
            if (isEmbedded) {
                const msgView = document.querySelector('.messenger-messagingView');
                const listView = document.querySelector('.messenger-listView');
                const backBtn = document.querySelector('.show-listView');
                
                // Show messaging view when conversation is selected
                $(document).on('click', '.messenger-list-item, .group-chat-item', function() {
                    if (msgView) {
                        msgView.classList.add('show-messages-view');
                    }
                });
                
                // Handle back button
                if (backBtn) {
                    backBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        if (msgView) {
                            msgView.classList.remove('show-messages-view');
                        }
                    });
                }
            }
        })();
    </script>
@endpush

@if ($setting['SITE_RTL'] == 'on')
    <style type="text/css">
        body {

            text-align: right !important;
        }
    </style>
@endif

@if ($color == 'theme-1')
    <style type="text/css">
        .m-list-active,
        .m-list-active:hover,
        .m-list-active:focus {
            background: linear-gradient(141.55deg, #0CAF60 3.46%, #0CAF60 99.86%), #0CAF60 !important;
        }

        .mc-sender p {
            background: linear-gradient(141.55deg, #0CAF60 3.46%, #0CAF60 99.86%), #0CAF60 !important;
        }

        .messenger-favorites div.avatar {
            box-shadow: 0px 0px 0px 2px #0CAF60 !important;
        }

        .messenger-listView-tabs a,
        .messenger-listView-tabs a:hover,
        .messenger-listView-tabs a:focus {
            color: linear-gradient(141.55deg, #0CAF60 3.46%, #0CAF60 99.86%), #0CAF60 !important;
        }

        .m-header svg {
            color: #0CAF60 !important;
        }

        .active-tab {
            border-bottom: 2px solid #0CAF60 !important;
        }

        .messenger-infoView nav a {

            color: linear-gradient(141.55deg, #0CAF60 3.46%, #0CAF60 99.86%), #0CAF60 !important;
        }

        .lastMessageIndicator {
            color: #0CAF60 !important;
        }

        .messenger-list-item td span .lastMessageIndicator {

            color: #0CAF60 !important;
            font-weight: bold;
        }

        .messenger-sendCard button svg {
            color: #0CAF60 !important;
        }

        .messenger-list-item.m-list-active td span .lastMessageIndicator {
            color: #fff !important;
        }
    </style>
@endif

@if ($color == 'theme-2')
    <style type="text/css">
        .m-list-active,
        .m-list-active:hover,
        .m-list-active:focus {
            background: linear-gradient(141.55deg, #584ED2 3.46%, #584ED2 99.86%), #584ED2 !important;
        }

        .mc-sender p {
            background: linear-gradient(141.55deg, #584ED2 3.46%, #584ED2 99.86%), #584ED2 !important;
        }

        .messenger-favorites div.avatar {
            box-shadow: 0px 0px 0px 2px #584ED2 !important;
        }

        .messenger-listView-tabs a,
        .messenger-listView-tabs a:hover,
        .messenger-listView-tabs a:focus {
            color: linear-gradient(141.55deg, #584ED2 3.46%, #584ED2 99.86%), #584ED2 !important;
        }

        .m-header svg {
            color: #584ED2 !important;
        }

        .active-tab {
            border-bottom: 2px solid #584ED2 !important;
        }

        .messenger-infoView nav a {

            color: linear-gradient(141.55deg, #584ED2 3.46%, #584ED2 99.86%), #584ED2 !important;
        }

        .lastMessageIndicator {
            color: #584ED2 !important;
        }

        .messenger-list-item td span .lastMessageIndicator {

            color: #584ED2 !important;
            font-weight: bold;
        }

        .messenger-sendCard button svg {
            color: #584ED2 !important;
        }

        .messenger-list-item.m-list-active td span .lastMessageIndicator {
            color: #fff !important;
        }
    </style>
@endif

@if ($color == 'theme-3')
    <style type="text/css">
        .m-list-active,
        .m-list-active:hover,
        .m-list-active:focus {
            background: linear-gradient(141.55deg, #6fd943 3.46%, #6fd943 99.86%), #6fd943 !important;
        }

        .mc-sender p {
            background: linear-gradient(141.55deg, #6fd943 3.46%, #6fd943 99.86%), #6fd943 !important;
        }

        .messenger-favorites div.avatar {
            box-shadow: 0px 0px 0px 2px #6fd943 !important;
        }

        .messenger-listView-tabs a,
        .messenger-listView-tabs a:hover,
        .messenger-listView-tabs a:focus {
            color: linear-gradient(141.55deg, #6fd943 3.46%, #6fd943 99.86%), #6fd943 !important;
        }

        .m-header svg {
            color: #6fd943 !important;
        }

        .active-tab {
            border-bottom: 2px solid #6fd943 !important;
        }

        .messenger-infoView nav a {

            color: linear-gradient(141.55deg, #6fd943 3.46%, #6fd943 99.86%), #6fd943 !important;
        }

        .lastMessageIndicator {
            color: #6fd943 !important;
        }

        .messenger-list-item td span .lastMessageIndicator {

            color: #6fd943 !important;
            font-weight: bold;
        }

        .messenger-sendCard button svg {
            color: #6fd943 !important;
        }

        .messenger-list-item.m-list-active td span .lastMessageIndicator {
            color: #fff !important;
        }
    </style>
@endif

@if ($color == 'theme-4')
    <style type="text/css">
        .m-list-active,
        .m-list-active:hover,
        .m-list-active:focus {
            background: linear-gradient(141.55deg, #145388 3.46%, #145388 99.86%), #145388 !important;
        }

        .mc-sender p {
            background: linear-gradient(141.55deg, #145388 3.46%, #145388 99.86%), #145388 !important;
        }

        .messenger-favorites div.avatar {
            box-shadow: 0px 0px 0px 2px #145388 !important;
        }

        .messenger-listView-tabs a,
        .messenger-listView-tabs a:hover,
        .messenger-listView-tabs a:focus {
            color: linear-gradient(141.55deg, #145388 3.46%, #145388 99.86%), #145388 !important;
        }

        .m-header svg {
            color: #145388 !important;
        }

        .active-tab {
            border-bottom: 2px solid #145388 !important;
        }

        .messenger-infoView nav a {

            color: linear-gradient(141.55deg, #145388 3.46%, #145388 99.86%), #145388 !important;
        }

        .lastMessageIndicator {
            color: #145388 !important;
        }

        .messenger-list-item td span .lastMessageIndicator {

            color: #145388 !important;
            font-weight: bold;
        }

        .messenger-sendCard button svg {
            color: #145388 !important;
        }

        .messenger-list-item.m-list-active td span .lastMessageIndicator {
            color: #fff !important;
        }
    </style>
@endif

@if ($color == 'theme-5')
    <style type="text/css">
        .m-list-active,
        .m-list-active:hover,
        .m-list-active:focus {
            background: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #B9406B 99.86%), #B9406B !important;
        }

        .mc-sender p {
            background: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #B9406B 99.86%), #B9406B !important;
        }

        .messenger-favorites div.avatar {
            box-shadow: 0px 0px 0px 2px #B9406B !important;
        }

        .messenger-listView-tabs a,
        .messenger-listView-tabs a:hover,
        .messenger-listView-tabs a:focus {
            color: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #B9406B 99.86%), #B9406B !important;
        }

        .m-header svg {
            color: #B9406B !important;
        }

        .active-tab {
            border-bottom: 2px solid #B9406B !important;
        }

        .messenger-infoView nav a {

            color: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #B9406B 99.86%), #B9406B !important;
        }

        .lastMessageIndicator {
            color: #B9406B !important;
        }

        .messenger-list-item td span .lastMessageIndicator {

            color: #B9406B !important;
            font-weight: bold;
        }

        .messenger-sendCard button svg {
            color: #B9406B !important;
        }

        .messenger-list-item.m-list-active td span .lastMessageIndicator {
            color: #fff !important;
        }
    </style>
@endif

@if ($color == 'theme-6')
    <style type="text/css">
        .m-list-active,
        .m-list-active:hover,
        .m-list-active:focus {
            background: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #008ECC 99.86%), #008ECC !important;
        }

        .mc-sender p {
            background: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #008ECC 99.86%), #008ECC !important;
        }

        .messenger-favorites div.avatar {
            box-shadow: 0px 0px 0px 2px #008ECC !important;
        }

        .messenger-listView-tabs a,
        .messenger-listView-tabs a:hover,
        .messenger-listView-tabs a:focus {
            color: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #008ECC 99.86%), #008ECC !important;
        }

        .m-header svg {
            color: #008ECC !important;
        }

        .active-tab {
            border-bottom: 2px solid #008ECC !important;
        }

        .messenger-infoView nav a {

            color: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #008ECC 99.86%), #008ECC !important;
        }

        .lastMessageIndicator {
            color: #008ECC !important;
        }

        .messenger-list-item td span .lastMessageIndicator {

            color: #008ECC !important;
            font-weight: bold;
        }

        .messenger-sendCard button svg {
            color: #008ECC !important;
        }

        .messenger-list-item.m-list-active td span .lastMessageIndicator {
            color: #fff !important;
        }
    </style>
@endif

@if ($color == 'theme-7')
    <style type="text/css">
        .m-list-active,
        .m-list-active:hover,
        .m-list-active:focus {
            background: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #922C88 99.86%), #922C88 !important;
        }

        .mc-sender p {
            background: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #922C88 99.86%), #922C88 !important;
        }

        .messenger-favorites div.avatar {
            box-shadow: 0px 0px 0px 2px #922C88 !important;
        }

        .messenger-listView-tabs a,
        .messenger-listView-tabs a:hover,
        .messenger-listView-tabs a:focus {
            color: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #922C88 99.86%), #922C88 !important;
        }

        .m-header svg {
            color: #922C88 !important;
        }

        .active-tab {
            border-bottom: 2px solid #922C88 !important;
        }

        .messenger-infoView nav a {

            color: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #922C88 99.86%), #922C88 !important;
        }

        .lastMessageIndicator {
            color: #922C88 !important;
        }

        .messenger-list-item td span .lastMessageIndicator {

            color: #922C88 !important;
            font-weight: bold;
        }

        .messenger-sendCard button svg {
            color: #922C88 !important;
        }

        .messenger-list-item.m-list-active td span .lastMessageIndicator {
            color: #fff !important;
        }
    </style>
@endif

@if ($color == 'theme-8')
    <style type="text/css">
        .m-list-active,
        .m-list-active:hover,
        .m-list-active:focus {
            background: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #C0A145 99.86%), #C0A145 !important;
        }

        .mc-sender p {
            background: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #C0A145 99.86%), #C0A145 !important;
        }

        .messenger-favorites div.avatar {
            box-shadow: 0px 0px 0px 2px #C0A145 !important;
        }

        .messenger-listView-tabs a,
        .messenger-listView-tabs a:hover,
        .messenger-listView-tabs a:focus {
            color: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #C0A145 99.86%), #C0A145 !important;
        }

        .m-header svg {
            color: #C0A145 !important;
        }

        .active-tab {
            border-bottom: 2px solid #C0A145 !important;
        }

        .messenger-infoView nav a {

            color: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #C0A145 99.86%), #C0A145 !important;
        }

        .lastMessageIndicator {
            color: #C0A145 !important;
        }

        .messenger-list-item td span .lastMessageIndicator {

            color: #C0A145 !important;
            font-weight: bold;
        }

        .messenger-sendCard button svg {
            color: #C0A145 !important;
        }

        .messenger-list-item.m-list-active td span .lastMessageIndicator {
            color: #fff !important;
        }
    </style>
@endif

@if ($color == 'theme-9')
    <style type="text/css">
        .m-list-active,
        .m-list-active:hover,
        .m-list-active:focus {
            background: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #48494B 99.86%), #48494B !important;
        }

        .mc-sender p {
            background: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #48494B 99.86%), #48494B !important;
        }

        .messenger-favorites div.avatar {
            box-shadow: 0px 0px 0px 2px #48494B !important;
        }

        .messenger-listView-tabs a,
        .messenger-listView-tabs a:hover,
        .messenger-listView-tabs a:focus {
            color: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #48494B 99.86%), #48494B !important;
        }

        .m-header svg {
            color: #48494B !important;
        }

        .active-tab {
            border-bottom: 2px solid #48494B !important;
        }

        .messenger-infoView nav a {

            color: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #48494B 99.86%), #48494B !important;
        }

        .lastMessageIndicator {
            color: #48494B !important;
        }

        .messenger-list-item td span .lastMessageIndicator {

            color: #48494B !important;
            font-weight: bold;
        }

        .messenger-sendCard button svg {
            color: #48494B !important;
        }

        .messenger-list-item.m-list-active td span .lastMessageIndicator {
            color: #fff !important;
        }
    </style>
@endif

@if ($color == 'theme-10')
    <style type="text/css">
        .m-list-active,
        .m-list-active:hover,
        .m-list-active:focus {
            background: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #0C7785 99.86%), #0C7785 !important;
        }

        .mc-sender p {
            background: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #0C7785 99.86%), #0C7785 !important;
        }

        .messenger-favorites div.avatar {
            box-shadow: 0px 0px 0px 2px #0C7785 !important;
        }

        .messenger-listView-tabs a,
        .messenger-listView-tabs a:hover,
        .messenger-listView-tabs a:focus {
            color: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #0C7785 99.86%), #0C7785 !important;
        }

        .m-header svg {
            color: #0C7785 !important;
        }

        .active-tab {
            border-bottom: 2px solid #0C7785 !important;
        }

        .messenger-infoView nav a {

            color: linear-gradient(141.55deg, rgba(104, 94, 229, 0) 3.46%, #0C7785 99.86%), #0C7785 !important;
        }

        .lastMessageIndicator {
            color: #0C7785 !important;
        }

        .messenger-list-item td span .lastMessageIndicator {

            color: #0C7785 !important;
            font-weight: bold;
        }

        .messenger-sendCard button svg {
            color: #0C7785 !important;
        }

        .messenger-list-item.m-list-active td span .lastMessageIndicator {
            color: #fff !important;
        }
    </style>
@endif

@if ($color == 'custom-color')
    <style type="text/css">
        .m-list-active,
        .m-list-active:hover,
        .m-list-active:focus {
            background: linear-gradient(141.55deg, var(--color-customColor) 3.46%, var(--color-customColor) 99.86%), var(--color-customColor) !important;
        }

        .mc-sender p {
            background: linear-gradient(141.55deg, var(--color-customColor) 3.46%, var(--color-customColor) 99.86%), var(--color-customColor) !important;
        }

        .messenger-favorites div.avatar {
            box-shadow: 0px 0px 0px 2px var(--color-customColor) !important;
        }

        .messenger-listView-tabs a,
        .messenger-listView-tabs a:hover,
        .messenger-listView-tabs a:focus {
            color: linear-gradient(141.55deg, var(--color-customColor) 3.46%, var(--color-customColor) 99.86%), var(--color-customColor) !important;
        }

        .m-header svg {
            color: var(--color-customColor) !important;
        }

        .active-tab {
            border-bottom: 2px solid var(--color-customColor) !important;
        }

        .messenger-infoView nav a {

            color: linear-gradient(141.55deg, var(--color-customColor) 3.46%, var(--color-customColor) 99.86%), var(--color-customColor) !important;
        }

        .lastMessageIndicator {
            color: var(--color-customColor) !important;
        }

        .messenger-list-item td span .lastMessageIndicator {

            color: var(--color-customColor) !important;
            font-weight: bold;
        }

        .messenger-sendCard button svg {
            color: var(--color-customColor) !important;
        }

        .messenger-list-item.m-list-active td span .lastMessageIndicator {
            color: #fff !important;
        }
    </style>
@endif
