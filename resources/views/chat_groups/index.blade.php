@extends('layouts.admin')

@push('css-page')
<style>
/* ─── Hide standard chrome ─────────────────────────────────────────── */
.dash-sidebar, .dash-header, .page-header, .dash-footer {
    display: none !important;
}
.dash-container { margin-left: 0 !important; padding-top: 0 !important; }
.dash-content   { padding: 0 !important; }

/* ─── Design tokens ─────────────────────────────────────────────────── */
:root {
    --cg-primary:       #4361ee;
    --cg-primary-dark:  #3a0ca3;
    --cg-primary-light: #eef2ff;
    --cg-accent:        #4cc9f0;
    --cg-sent-bg:       #dbe4ff;
    --cg-sent-time:     #4361ee;
    --cg-received-bg:   #ffffff;
    --cg-header-bg:     #f8f9fc;
    --cg-input-bg:      #f8f9fc;
    --cg-sidebar-bg:    #ffffff;
    --cg-chat-bg:       #f0f2f8;
    --cg-border:        #e8ecf0;
    --cg-text-meta:     #7b8fa6;
    --cg-unread:        #e63946;
    --cg-online:        #2dc653;
    --cg-radius:        12px;
}

/* ─── Layout ────────────────────────────────────────────────────────── */
.cg-wrap {
    display: flex;
    height: 100vh;
    overflow: hidden;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--cg-chat-bg);
}

/* ─── Sidebar ────────────────────────────────────────────────────────── */
.cg-sidebar {
    width: 320px;
    min-width: 260px;
    background: var(--cg-sidebar-bg);
    border-right: 1px solid var(--cg-border);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    box-shadow: 2px 0 12px rgba(67,97,238,.06);
}
.cg-sidebar-topbar {
    background: linear-gradient(135deg, var(--cg-primary-dark) 0%, var(--cg-primary) 100%);
    color: #fff;
    padding: 14px 16px 13px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.cg-sidebar-topbar h6 {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    letter-spacing: .4px;
}
.cg-topbar-actions { display: flex; gap: 4px; }
.cg-topbar-actions a,
.cg-topbar-actions button {
    background: rgba(255,255,255,.12);
    border: none;
    color: #fff;
    font-size: 17px;
    cursor: pointer;
    border-radius: 50%;
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background .15s;
    text-decoration: none;
}
.cg-topbar-actions a:hover,
.cg-topbar-actions button:hover { background: rgba(255,255,255,.25); color: #fff; }

.cg-search-bar {
    padding: 10px 12px;
    background: var(--cg-sidebar-bg);
    border-bottom: 1px solid var(--cg-border);
}
.cg-search-bar input {
    width: 100%;
    background: var(--cg-primary-light);
    border: 1.5px solid transparent;
    border-radius: 20px;
    padding: 7px 14px 7px 38px;
    font-size: 13px;
    outline: none;
    color: #1a1a2e;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%234361ee' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: 13px center;
    background-size: 14px;
    transition: border-color .2s, box-shadow .2s;
}
.cg-search-bar input:focus { border-color: var(--cg-primary); box-shadow: 0 0 0 3px rgba(67,97,238,.12); background: #fff; }

.cg-group-list {
    flex: 1;
    overflow-y: auto;
    overscroll-behavior: contain;
}
.cg-group-item {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 11px 14px;
    cursor: pointer;
    border-bottom: 1px solid #f4f5f7;
    transition: background .12s;
    text-decoration: none;
    color: inherit;
}
.cg-group-item:hover  { background: var(--cg-primary-light); color: inherit; }
.cg-group-item.active { background: var(--cg-primary-light); border-left: 3px solid var(--cg-primary); }
.cg-group-avatar {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--cg-primary) 0%, var(--cg-primary-dark) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: #fff;
    font-weight: 700;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(67,97,238,.3);
}
.cg-group-info { flex: 1; min-width: 0; }
.cg-group-name { font-size: 14px; font-weight: 600; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #1a1a2e; }
.cg-group-preview { font-size: 12px; color: var(--cg-text-meta); margin: 3px 0 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cg-group-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0; }
.cg-group-time { font-size: 11px; color: var(--cg-text-meta); }
.cg-unread-badge {
    background: var(--cg-unread);
    color: #fff;
    border-radius: 999px;
    min-width: 18px;
    height: 18px;
    font-size: 10px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 5px;
    box-shadow: 0 1px 4px rgba(230,57,70,.3);
}
.cg-empty-list {
    padding: 40px 16px;
    text-align: center;
    color: var(--cg-text-meta);
    font-size: 14px;
}
.cg-empty-list i { font-size: 44px; display: block; margin-bottom: 10px; color: #c5cae9; }

/* ─── Create-Group Drawer ──────────────────────────────────────────── */
.cg-drawer-overlay {
    position: absolute;
    inset: 0;
    z-index: 200;
    overflow: hidden;
    pointer-events: none;
}
.cg-drawer-overlay.open { pointer-events: all; background: rgba(10,10,30,.25); }
.cg-drawer {
    position: absolute;
    top: 0; left: 0; bottom: 0;
    width: 320px;
    background: #fff;
    box-shadow: 6px 0 32px rgba(67,97,238,.18);
    transform: translateX(-100%);
    transition: transform .28s cubic-bezier(.4,0,.2,1);
    display: flex;
    flex-direction: column;
    z-index: 201;
}
.cg-drawer-overlay.open .cg-drawer { transform: translateX(0); }
.cg-drawer-header {
    background: linear-gradient(135deg, var(--cg-primary-dark) 0%, var(--cg-primary) 100%);
    color: #fff;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.cg-drawer-header h6 { margin: 0; font-size: 15px; font-weight: 700; }
.cg-drawer-body { flex: 1; overflow-y: auto; padding: 16px; }
.cg-drawer-body .form-label { font-size: 11px; font-weight: 700; color: var(--cg-text-meta); text-transform: uppercase; letter-spacing: .6px; }
.cg-drawer-body .form-control {
    border-radius: 10px;
    font-size: 14px;
    border: 1.5px solid var(--cg-border);
    transition: border-color .2s, box-shadow .2s;
}
.cg-drawer-body .form-control:focus {
    border-color: var(--cg-primary);
    box-shadow: 0 0 0 3px rgba(67,97,238,.12);
}
.cg-member-check-list {
    max-height: 220px;
    overflow-y: auto;
    border: 1.5px solid var(--cg-border);
    border-radius: 10px;
    padding: 4px 8px;
    background: #fafbff;
}
.cg-member-check-list .form-check {
    padding: 7px 0 7px 28px;
    border-bottom: 1px solid #f0f0f0;
}
.cg-member-check-list .form-check:last-child { border-bottom: none; }
.cg-member-check-list .form-check-input:checked { background-color: var(--cg-primary); border-color: var(--cg-primary); }
.cg-drawer-footer { padding: 14px 16px; border-top: 1px solid var(--cg-border); background: #fafbff; }

/* ─── Main Chat Area ─────────────────────────────────────────────────── */
.cg-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
    background-color: var(--cg-chat-bg);
    background-image:
        radial-gradient(circle at 20% 50%, rgba(67,97,238,.03) 0%, transparent 60%),
        radial-gradient(circle at 80% 20%, rgba(76,201,240,.04) 0%, transparent 50%);
}

/* Chat header */
.cg-main-header {
    background: var(--cg-header-bg);
    padding: 10px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid var(--cg-border);
    flex-shrink: 0;
    min-height: 58px;
    box-shadow: 0 1px 6px rgba(67,97,238,.06);
}
.cg-main-avatar {
    width: 42px; height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--cg-primary) 0%, var(--cg-primary-dark) 100%);
    color: #fff;
    font-size: 16px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(67,97,238,.25);
}
.cg-main-info { flex: 1; min-width: 0; }
.cg-main-info h6 { margin: 0; font-size: 15px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #1a1a2e; }
.cg-main-info small { color: var(--cg-text-meta); font-size: 12px; }
.cg-main-actions { display: flex; gap: 2px; }
.cg-main-actions button,
.cg-main-actions a {
    background: transparent;
    border: none;
    color: var(--cg-text-meta);
    font-size: 17px;
    width: 38px; height: 38px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background .15s, color .15s;
    text-decoration: none;
}
.cg-main-actions button:hover,
.cg-main-actions a:hover { background: var(--cg-primary-light); color: var(--cg-primary); }

/* Add members panel */
.cg-add-members-panel {
    background: #fff;
    border-bottom: 1px solid var(--cg-border);
    padding: 0 16px;
    max-height: 0;
    overflow: hidden;
    transition: max-height .3s ease, padding .3s ease;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(67,97,238,.05) inset;
}
.cg-add-members-panel.show { max-height: 260px; padding: 14px 16px; }
.cg-add-members-panel strong { font-size: 12px; font-weight: 700; color: var(--cg-primary); text-transform: uppercase; letter-spacing: .5px; }

/* Messages pane */
.cg-messages-pane {
    flex: 1;
    overflow-y: auto;
    overscroll-behavior: contain;
    padding: 16px 10%;
    display: flex;
    flex-direction: column;
    gap: 3px;
}
@media (max-width: 768px) {
    .cg-messages-pane { padding: 10px 8px; }
}

/* Bubbles */
.cg-msg-row { display: flex; flex-direction: column; max-width: 70%; }
.cg-msg-row.mine   { align-self: flex-end;   align-items: flex-end; }
.cg-msg-row.theirs { align-self: flex-start; align-items: flex-start; }
.cg-msg-sender {
    font-size: 11px;
    font-weight: 700;
    color: var(--cg-primary);
    margin-bottom: 3px;
    padding-left: 4px;
    letter-spacing: .2px;
}
.cg-msg-row.mine .cg-msg-sender { display: none; }
.cg-bubble {
    padding: 8px 14px 20px;
    border-radius: 12px;
    font-size: 13.5px;
    line-height: 1.55;
    position: relative;
    word-break: break-word;
    box-shadow: 0 1px 4px rgba(0,0,0,.07);
}
.cg-msg-row.mine  .cg-bubble {
    background: var(--cg-sent-bg);
    border-top-right-radius: 3px;
    border-bottom-right-radius: 12px;
}
.cg-msg-row.theirs .cg-bubble {
    background: var(--cg-received-bg);
    border-top-left-radius: 3px;
    border-bottom-left-radius: 12px;
}
.cg-msg-time {
    position: absolute;
    bottom: 5px;
    right: 10px;
    font-size: 10px;
    color: var(--cg-text-meta);
    white-space: nowrap;
}
.cg-msg-row.mine .cg-msg-time { color: var(--cg-primary); opacity: .75; }
.cg-voice-player { max-width: 220px; height: 36px; display: block; }
.cg-date-sep {
    align-self: center;
    background: rgba(255,255,255,.9);
    backdrop-filter: blur(4px);
    border-radius: 20px;
    padding: 4px 16px;
    font-size: 11px;
    font-weight: 600;
    color: var(--cg-text-meta);
    margin: 10px 0 6px;
    box-shadow: 0 1px 4px rgba(0,0,0,.07);
    letter-spacing: .3px;
}
.cg-empty-msg {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--cg-text-meta);
    gap: 10px;
}
.cg-empty-msg i { font-size: 64px; color: #c5cae9; }
.cg-empty-state-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--cg-text-meta);
    text-align: center;
    padding: 24px;
}
.cg-empty-state-main i { font-size: 80px; margin-bottom: 16px; color: #c5cae9; }
.cg-empty-state-main p { font-size: 16px; font-weight: 600; margin: 0; color: #3a4060; }
.cg-empty-state-main small { color: var(--cg-text-meta); margin-top: 6px; display: block; }

/* Input bar */
.cg-input-bar {
    padding: 10px 14px;
    background: var(--cg-header-bg);
    border-top: 1px solid var(--cg-border);
    display: flex;
    align-items: flex-end;
    gap: 8px;
    flex-shrink: 0;
    box-shadow: 0 -1px 6px rgba(67,97,238,.05);
}
.cg-input-wrap {
    flex: 1;
    background: #fff;
    border-radius: 24px;
    border: 1.5px solid var(--cg-border);
    display: flex;
    align-items: center;
    padding: 0 14px;
    min-height: 44px;
    gap: 8px;
    transition: border-color .2s, box-shadow .2s;
}
.cg-input-wrap:focus-within {
    border-color: var(--cg-primary);
    box-shadow: 0 0 0 3px rgba(67,97,238,.10);
}
.cg-input-wrap textarea {
    flex: 1;
    border: none;
    outline: none;
    resize: none;
    font-size: 14px;
    line-height: 1.5;
    padding: 10px 0;
    max-height: 120px;
    background: transparent;
    overflow-y: auto;
    color: #1a1a2e;
}
.cg-send-btn,
.cg-voice-btn {
    width: 46px; height: 46px;
    border-radius: 50%;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
    cursor: pointer;
    flex-shrink: 0;
    transition: background .15s, transform .12s, box-shadow .15s;
}
.cg-send-btn {
    background: linear-gradient(135deg, var(--cg-primary) 0%, var(--cg-primary-dark) 100%);
    color: #fff;
    box-shadow: 0 3px 10px rgba(67,97,238,.35);
}
.cg-send-btn:hover { transform: scale(1.07); box-shadow: 0 5px 16px rgba(67,97,238,.45); }
.cg-voice-btn {
    background: #fff;
    color: var(--cg-primary);
    border: 1.5px solid var(--cg-border);
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.cg-voice-btn.recording {
    background: #e63946;
    color: #fff;
    border-color: #e63946;
    box-shadow: 0 3px 10px rgba(230,57,70,.4);
    animation: pulse 1.2s infinite;
}
.cg-voice-btn:hover:not(.recording) { background: var(--cg-primary-light); border-color: var(--cg-primary); }

/* Recording indicator */
.cg-recording-bar {
    height: 0;
    overflow: hidden;
    background: linear-gradient(135deg, #fff3e0, #fff8e1);
    border-top: 1px solid #ffe082;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-size: 13px;
    font-weight: 500;
    color: #e65100;
    transition: height .2s;
    flex-shrink: 0;
}
.cg-recording-bar.show { height: 38px; }
.cg-rec-dot { width: 9px; height: 9px; border-radius: 50%; background: #e63946; animation: pulse 1.2s infinite; }

@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: .4; transform: scale(.85); }
}

/* Scrollbar */
.cg-messages-pane::-webkit-scrollbar,
.cg-group-list::-webkit-scrollbar { width: 3px; }
.cg-messages-pane::-webkit-scrollbar-track,
.cg-group-list::-webkit-scrollbar-track { background: transparent; }
.cg-messages-pane::-webkit-scrollbar-thumb,
.cg-group-list::-webkit-scrollbar-thumb { background: rgba(67,97,238,.2); border-radius: 3px; }

/* Mobile back button in main header */
.cg-mobile-back {
    display: none;
    background: none;
    border: none;
    color: var(--cg-primary);
    font-size: 21px;
    cursor: pointer;
    padding: 0;
    width: 38px; height: 38px;
    border-radius: 50%;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-right: 2px;
    transition: background .15s;
}
.cg-mobile-back:hover { background: var(--cg-primary-light); }

/* Responsive */
@media (max-width: 768px) {
    .cg-sidebar { width: 100%; display: none; }
    .cg-sidebar.mobile-visible { display: flex; position: absolute; z-index: 100; height: 100%; top: 0; left: 0; }
    .cg-main { width: 100%; }
    .cg-mobile-back { display: inline-flex; }
    /* Make the main chat header stand out on mobile */
    .cg-main-header {
        background: linear-gradient(135deg, var(--cg-primary-dark) 0%, var(--cg-primary) 100%);
        box-shadow: 0 2px 10px rgba(67,97,238,.25);
    }
    .cg-main-info h6 { color: #fff; }
    .cg-main-info small { color: rgba(255,255,255,.75); }
    .cg-main-avatar { box-shadow: 0 2px 8px rgba(0,0,0,.2); }
    .cg-main-actions button,
    .cg-main-actions a { color: rgba(255,255,255,.85); }
    .cg-main-actions button:hover,
    .cg-main-actions a:hover { background: rgba(255,255,255,.18); color: #fff; }
}
</style>
@endpush

@section('page-title'){{ __('Group Chat') }}@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Group Chat') }}</li>
@endsection

@section('content')
@php
    $currentUserId = Auth::id();
    $selectedId = !empty($selectedGroup) ? $selectedGroup->id : null;
@endphp

<div class="cg-wrap" id="cgWrap">

    {{-- ══════════════════ SIDEBAR ══════════════════ --}}
    <aside class="cg-sidebar" id="cgSidebar">

        {{-- Top bar --}}
        <div class="cg-sidebar-topbar">
            <a href="javascript:void(0);" onclick="if(document.referrer && document.referrer !== window.location.href){history.back();}else{window.location.href='{{ url('/chats') }}';}return false;" class="text-white" title="{{ __('Back') }}" style="font-size:20px;line-height:1;text-decoration:none;">
                <i class="ti ti-arrow-left"></i>
            </a>
            <span style="width:1px;height:22px;background:rgba(255,255,255,.2);display:inline-block;margin:0 6px;"></span>
            <h6 class="flex-grow-1"><i class="ti ti-users-group me-1" style="font-size:15px;"></i>{{ __('Group Chat') }}</h6>
            <div class="cg-topbar-actions">
                <button id="cgNewGroupBtn" title="{{ __('New Group') }}">
                    <i class="ti ti-plus"></i>
                </button>
            </div>
        </div>

        {{-- Search --}}
        <div class="cg-search-bar">
            <input type="text" id="cgSearchInput" placeholder="{{ __('Search groups...') }}" autocomplete="off">
        </div>

        {{-- Group list --}}
        <div class="cg-group-list" id="cgGroupList">
            @forelse ($groups as $group)
                @php
                    $lastMsg  = $group->messages->first();
                    $preview  = $lastMsg
                        ? ($lastMsg->message_type === 'voice' ? '�️ Voice message' : \Illuminate\Support\Str::limit($lastMsg->message, 40))
                        : __('No messages yet');
                    $initial  = strtoupper(mb_substr($group->name, 0, 1));
                    $isActive = $selectedId == $group->id;
                @endphp
                <a href="{{ route('chat-groups.index', ['groupId' => $group->id]) }}"
                   class="cg-group-item {{ $isActive ? 'active' : '' }}"
                   data-group-id="{{ $group->id }}"
                   data-name="{{ $group->name }}">
                    <div class="cg-group-avatar">{{ $initial }}</div>
                    <div class="cg-group-info">
                        <p class="cg-group-name">{{ $group->name }}</p>
                        <p class="cg-group-preview">{{ $preview }}</p>
                    </div>
                    <div class="cg-group-meta">
                        <span class="cg-group-time">{{ $group->updated_at ? $group->updated_at->format('h:i A') : '' }}</span>
                    </div>
                </a>
            @empty
                <div class="cg-empty-list">
                    <i class="ti ti-message-off"></i>
                    <div>{{ __('No groups yet.') }}</div>
                    <small>{{ __('Tap + to create one') }}</small>
                </div>
            @endforelse
        </div>

        {{-- Create-group overlay drawer --}}
        <div class="cg-drawer-overlay" id="cgDrawerOverlay">
            <div class="cg-drawer" id="cgDrawer">
                <div class="cg-drawer-header">
                    <button id="cgDrawerClose" style="background:none;border:none;color:#fff;font-size:20px;cursor:pointer;padding:0;">
                        <i class="ti ti-x"></i>
                    </button>
                    <h6>{{ __('New Group') }}</h6>
                </div>
                <div class="cg-drawer-body">
                    {{ Form::open(['route' => 'chat-groups.store', 'method' => 'post', 'id' => 'cgCreateForm']) }}
                    <div class="mb-3">
                        <label class="form-label">{{ __('Group Name') }}</label>
                        <input type="text" name="name" class="form-control" required placeholder="{{ __('e.g. Design Team') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Add Members') }}</label>
                        <div class="cg-member-check-list">
                            @foreach ($teamMembers as $member)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="members[]"
                                           value="{{ $member->id }}" id="nm_{{ $member->id }}">
                                    <label class="form-check-label" for="nm_{{ $member->id }}" style="font-size:13px;">
                                        {{ $member->name }}
                                        <span class="badge bg-light text-secondary" style="font-size:10px;">{{ ucfirst($member->type) }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="cg-drawer-footer px-0">
                        <button type="submit" class="btn w-100" style="background:linear-gradient(135deg,#4361ee,#3a0ca3);color:#fff;font-weight:600;border-radius:10px;box-shadow:0 3px 10px rgba(67,97,238,.3);">
                            <i class="ti ti-users-plus me-1"></i>{{ __('Create Group') }}
                        </button>
                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </aside>

    {{-- ══════════════════ MAIN CHAT ══════════════════ --}}
    <main class="cg-main" id="cgMain">
        @if (!empty($selectedGroup))
        @php $memberCount = $selectedGroup->members->count(); @endphp

        {{-- Header --}}
        <div class="cg-main-header">
            <button type="button" class="cg-mobile-back" id="cgMobileBack" title="{{ __('Back to groups') }}">
                <i class="ti ti-arrow-left"></i>
            </button>
            <div class="cg-main-avatar">{{ strtoupper(mb_substr($selectedGroup->name, 0, 1)) }}</div>
            <div class="cg-main-info">
                <h6>{{ $selectedGroup->name }}</h6>
                <small><i class="ti ti-users" style="font-size:11px;vertical-align:middle;"></i> {{ $memberCount }} {{ __('members') }}</small>
            </div>
            <div class="cg-main-actions">
                <button id="cgAddMembersToggle" title="{{ __('Add Members') }}">
                    <i class="ti ti-user-plus"></i>
                </button>
                <button id="cgMemberListToggle" title="{{ __('View Members') }}">
                    <i class="ti ti-users-group"></i>
                </button>
            </div>
        </div>

        {{-- Add-Members panel --}}
        <div class="cg-add-members-panel" id="cgAddMembersPanel">
            {{ Form::open(['route' => ['chat-groups.members', $selectedGroup->id], 'method' => 'post']) }}
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong style="font-size:13px;">{{ __('Add Members') }}</strong>
                <button type="button" class="btn btn-sm" style="background:#4361ee;color:#fff;border-radius:10px;font-size:12px;font-weight:600;" onclick="this.closest('form').submit()">{{ __('Add Selected') }}</button>
            </div>
            @php $existingMemberIds = $selectedGroup->members->pluck('user_id')->toArray(); @endphp
            <div style="display:flex;flex-wrap:wrap;gap:6px;max-height:130px;overflow-y:auto;">
                @foreach ($teamMembers as $member)
                    @if (!in_array($member->id, $existingMemberIds))
                        <label style="display:flex;align-items:center;gap:4px;font-size:13px;background:#f8f9fa;border:1px solid #dee2e6;border-radius:20px;padding:3px 10px;cursor:pointer;">
                            <input type="checkbox" name="members[]" value="{{ $member->id }}" class="form-check-input m-0">
                            {{ $member->name }}
                        </label>
                    @endif
                @endforeach
            </div>
            {{ Form::close() }}
        </div>

        {{-- Member-List panel --}}
        <div class="cg-add-members-panel" id="cgMemberListPanel">
            <strong style="font-size:13px;">{{ __('Members') }}</strong>
            <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;max-height:130px;overflow-y:auto;">
                @foreach ($selectedGroup->members as $mem)
                    @if ($mem->user)
                        <span style="display:flex;align-items:center;gap:5px;background:#eef2ff;border-radius:20px;padding:3px 10px;font-size:13px;border:1px solid #c5cae9;">
                            <span style="width:22px;height:22px;border-radius:50%;background:#4361ee;color:#fff;font-size:11px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;">
                                {{ strtoupper(mb_substr($mem->user->name, 0, 1)) }}
                            </span>
                            {{ $mem->user->name }}
                            @if ($mem->user_id === Auth::id())
                                <small class="text-muted">({{ __('you') }})</small>
                            @endif
                        </span>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Recording indicator --}}
        <div class="cg-recording-bar" id="cgRecBar">
            <span class="cg-rec-dot"></span>
            <span id="cgRecTimer">00:00</span>
            <span>{{ __('Recording... tap mic to stop') }}</span>
        </div>

        {{-- Messages --}}
        <div class="cg-messages-pane" id="cgMessages">
            @php $prevDate = null; @endphp
            @forelse ($messages as $msg)
                @php
                    $msgDate = $msg->created_at->format('d M Y');
                    $isMine  = $msg->user_id === $currentUserId;
                @endphp
                @if ($msgDate !== $prevDate)
                    <div class="cg-date-sep">{{ $msgDate }}</div>
                    @php $prevDate = $msgDate; @endphp
                @endif
                <div class="cg-msg-row {{ $isMine ? 'mine' : 'theirs' }}" data-id="{{ $msg->id }}">
                    @if (!$isMine)
                        <div class="cg-msg-sender">{{ $msg->user ? $msg->user->name : 'User' }}</div>
                    @endif
                    <div class="cg-bubble">
                        @if (($msg->message_type ?? 'text') === 'voice' && !empty($msg->voice_path))
                            <audio controls preload="none" class="cg-voice-player">
                                <source src="{{ asset($msg->voice_path) }}">
                            </audio>
                        @elseif (!empty($msg->file_path))
                            @php
                                $ext = strtolower(pathinfo($msg->file_path, PATHINFO_EXTENSION));
                                $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                                $isVideo = in_array($ext, ['mp4','webm','ogg','avi','mov','mkv']);
                            @endphp
                            @if ($isImage)
                                <a href="{{ asset($msg->file_path) }}" target="_blank"><img src="{{ asset($msg->file_path) }}" alt="file" style="max-width:120px;max-height:120px;border-radius:8px;box-shadow:0 2px 8px #0001;"></a>
                            @elseif ($isVideo)
                                <video controls style="max-width:180px;max-height:120px;border-radius:8px;box-shadow:0 2px 8px #0001;">
                                    <source src="{{ asset($msg->file_path) }}">
                                </video>
                            @else
                                <a href="{{ asset($msg->file_path) }}" target="_blank" style="display:inline-block;max-width:180px;overflow-wrap:break-word;">
                                    <i class="ti ti-file"></i> {{ basename($msg->file_path) }}
                                </a>
                            @endif
                            @if (!empty($msg->message))<div>{!! nl2br(e($msg->message)) !!}</div>@endif
                        @else
                            {!! nl2br(e($msg->message)) !!}
                        @endif
                        <span class="cg-msg-time">{{ $msg->created_at->format('h:i A') }}</span>
                    </div>
                </div>
            @empty
                <div class="cg-empty-msg">
                    <i class="ti ti-message-off"></i>
                    <p style="font-size:14px;margin:0;">{{ __('No messages yet. Say hello!') }}</p>
                </div>
            @endforelse
        </div>

        {{-- Input bar --}}
            <form id="cgFileForm" enctype="multipart/form-data" style="display:flex;align-items:center;gap:6px;width:100%;">
                <div class="cg-input-wrap" style="flex:1;">
                    <textarea id="cgMsgInput" rows="1"
                        placeholder="{{ __('Write a message...') }}"
                        maxlength="5000" style="width:100%;"></textarea>
                </div>
                <label class="cg-file-btn" style="margin:0 4px;cursor:pointer;">
                    <input type="file" id="cgFileInput" name="file" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar,.mp3,.wav,.mp4,.avi,.mov,.mkv" style="display:none;">
                    <i class="ti ti-paperclip" style="font-size:1.2rem;"></i>
                </label>
                <button type="button" class="cg-voice-btn" id="cgVoiceBtn" title="{{ __('Voice message') }}">
                    <i class="ti ti-microphone-2" id="cgVoiceIcon"></i>
                </button>
                <button type="button" class="cg-send-btn" id="cgSendBtn" title="{{ __('Send') }}">
                    <i class="ti ti-send-2"></i>
                </button>
            </form>
                <button type="button" class="cg-voice-btn" id="cgVoiceBtn" title="{{ __('Voice message') }}">
                    <i class="ti ti-microphone-2" id="cgVoiceIcon"></i>
                </button>
                <button type="button" class="cg-send-btn" id="cgSendBtn" title="{{ __('Send') }}">
                    <i class="ti ti-send-2"></i>
                </button>
            </form>
        </div>

        {{-- Hidden forms for CSRF --}}
        <form id="cgMsgForm" method="POST" action="{{ route('chat-groups.messages', $selectedGroup->id) }}" style="display:none;">
            @csrf
            <input type="hidden" name="message" id="cgMsgHidden">
        </form>
        <form id="cgVoiceForm" method="POST" action="{{ route('chat-groups.voice', $selectedGroup->id) }}"
              enctype="multipart/form-data" style="display:none;">
            @csrf
            <input type="file" name="voice" id="cgVoiceFileInput">
        </form>

        @else
        {{-- Empty state --}}
        <div class="cg-empty-state-main">
            <i class="ti ti-messages"></i>
            <p>{{ __('Select a group to start chatting') }}</p>
            <small>{{ __('Or tap + in the sidebar to create a new group') }}</small>
            <a href="{{ url('/chats') }}" class="btn btn-sm mt-4" style="background:var(--cg-primary-light);color:var(--cg-primary);font-weight:600;border-radius:20px;padding:7px 20px;">
                <i class="ti ti-arrow-left me-1"></i>{{ __('Back to Chats') }}
            </a>
        </div>
        @endif
    </main>
</div>
@endsection

@push('scripts')
<script>
(function () {
    /* ──────────────────────── CSRF ──────────────────────── */
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    /* ──────────────────────── Mobile: sidebar vs chat view ─── */
    const cgSidebar = document.getElementById('cgSidebar');
    const isMobile  = () => window.innerWidth <= 768;

    // On mobile with no group selected → show the group list
    @if(empty($selectedGroup))
    if (isMobile() && cgSidebar) cgSidebar.classList.add('mobile-visible');
    @endif

    // Back button → navigate to group list
    const mobileBack = document.getElementById('cgMobileBack');
    if (mobileBack) {
        mobileBack.addEventListener('click', function () {
            window.location.href = '{{ route("chat-groups.index") }}';
        });
    }

    /* ──────────────────────── Sidebar drawer ─────────────── */
    const newGroupBtn   = document.getElementById('cgNewGroupBtn');
    const drawerOverlay = document.getElementById('cgDrawerOverlay');
    const drawerClose   = document.getElementById('cgDrawerClose');

    if (newGroupBtn && drawerOverlay) {
        newGroupBtn.addEventListener('click', () => drawerOverlay.classList.add('open'));
        drawerClose?.addEventListener('click', () => drawerOverlay.classList.remove('open'));
        drawerOverlay.addEventListener('click', (e) => {
            if (e.target === drawerOverlay) drawerOverlay.classList.remove('open');
        });
    }

    /* ──────────────────────── Group search filter ─────────── */
    const searchInput = document.getElementById('cgSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('.cg-group-item').forEach(item => {
                const name = (item.dataset.name || '').toLowerCase();
                item.style.display = (!q || name.includes(q)) ? '' : 'none';
            });
        });
    }

    /* ──────────────────────── Panels toggle ──────────────── */
    function setupToggle(btnId, panelId) {
        const btn   = document.getElementById(btnId);
        const panel = document.getElementById(panelId);
        if (!btn || !panel) return;
        btn.addEventListener('click', () => {
            const isOpen = panel.classList.contains('show');
            // Close all panels first
            document.querySelectorAll('.cg-add-members-panel').forEach(p => p.classList.remove('show'));
            if (!isOpen) panel.classList.add('show');
        });
    }
    setupToggle('cgAddMembersToggle', 'cgAddMembersPanel');
    setupToggle('cgMemberListToggle', 'cgMemberListPanel');

    /* ──────────────────────── Auto-resize textarea ────────── */
    const msgInput = document.getElementById('cgMsgInput');
    if (msgInput) {
        msgInput.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });
        msgInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendTextMessage();
            }
        });
    }

    /* ──────────────────────── Send text/file via AJAX ──────────── */
    const sendBtn   = document.getElementById('cgSendBtn');
    const fileForm  = document.getElementById('cgFileForm');
    const fileInput = document.getElementById('cgFileInput');
    const msgForm   = document.getElementById('cgMsgForm');
    const msgHidden = document.getElementById('cgMsgHidden');

    if (sendBtn) sendBtn.addEventListener('click', sendMessageWithFile);
    if (fileInput) fileInput.addEventListener('change', previewFile);

    function sendMessageWithFile() {
        if (!msgInput || !fileForm) return;
        const text = msgInput.value.trim();
        const file = fileInput && fileInput.files.length ? fileInput.files[0] : null;
        if (!text && !file) return;

        const url = msgForm.action;
        const formData = new FormData();
        if (text) formData.append('message', text);
        if (file) formData.append('file', file);

        // Optimistic preview
        if (file) {
            const ext = file.name.split('.').pop().toLowerCase();
            const isImage = ['jpg','jpeg','png','gif','webp'].includes(ext);
            const isVideo = ['mp4','webm','ogg','avi','mov','mkv'].includes(ext);
            let content = '';
            if (isImage) {
                const url = URL.createObjectURL(file);
                content = `<img src="${url}" alt="file" style="max-width:120px;max-height:120px;border-radius:8px;box-shadow:0 2px 8px #0001;">`;
            } else if (isVideo) {
                const url = URL.createObjectURL(file);
                content = `<video controls style="max-width:180px;max-height:120px;border-radius:8px;box-shadow:0 2px 8px #0001;"><source src="${url}"></video>`;
            } else {
                content = `<i class='ti ti-file'></i> ${file.name}`;
            }
            appendOptimisticBubble(text ? text + '<br>' + content : content, 'file', null);
        } else {
            appendOptimisticBubble(text, 'text', null);
        }

        msgInput.value = '';
        msgInput.style.height = 'auto';
        if (fileInput) fileInput.value = '';

        fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken }, body: formData })
            .catch(() => {});
    }

    function previewFile() {
        // Optionally, show a preview before sending (not required for MVP)
    }

    /* ──────────────────────── Voice recording ─────────────── */
    const voiceBtn      = document.getElementById('cgVoiceBtn');
    const voiceIcon     = document.getElementById('cgVoiceIcon');
    const recBar        = document.getElementById('cgRecBar');
    const recTimerEl    = document.getElementById('cgRecTimer');
    const voiceForm     = document.getElementById('cgVoiceForm');
    const voiceFileInp  = document.getElementById('cgVoiceFileInput');

    let mediaRecorder = null;
    let recChunks     = [];
    let recInterval   = null;
    let recSeconds    = 0;

    if (voiceBtn && voiceForm) {
        voiceBtn.addEventListener('click', async () => {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
                return;
            }
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                recChunks    = [];
                recSeconds   = 0;
                mediaRecorder = new MediaRecorder(stream);

                mediaRecorder.ondataavailable = e => { if (e.data.size) recChunks.push(e.data); };
                mediaRecorder.onstart = () => {
                    voiceBtn.classList.add('recording');
                    voiceIcon.className = 'ti ti-player-stop';
                    recBar?.classList.add('show');
                    recInterval = setInterval(() => {
                        recSeconds++;
                        const m = String(Math.floor(recSeconds / 60)).padStart(2, '0');
                        const s = String(recSeconds % 60).padStart(2, '0');
                        if (recTimerEl) recTimerEl.textContent = m + ':' + s;
                    }, 1000);
                };
                mediaRecorder.onstop = () => {
                    clearInterval(recInterval);
                    voiceBtn.classList.remove('recording');
                    voiceIcon.className = 'ti ti-microphone';
                    recBar?.classList.remove('show');
                    stream.getTracks().forEach(t => t.stop());

                    const blob = new Blob(recChunks, { type: 'audio/webm' });
                    const file = new File([blob], 'voice_' + Date.now() + '.webm', { type: 'audio/webm' });
                    const dt   = new DataTransfer();
                    dt.items.add(file);
                    voiceFileInp.files = dt.files;

                    appendOptimisticBubble(null, 'voice', URL.createObjectURL(blob));

                    const formData = new FormData(voiceForm);
                    formData.set('voice', file, file.name);
                    fetch(voiceForm.action, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
                        body: formData
                    }).catch(() => {});
                };
                mediaRecorder.start();
            } catch (err) {
                alert('{{ __("Microphone access denied.") }}');
            }
        });
    }

    /* ──────────────────────── Optimistic bubble ───────────── */
    const messagesPane = document.getElementById('cgMessages');
    let lastKnownId = {{ $messages->count() ? $messages->last()->id : 0 }};

    function appendOptimisticBubble(text, type, blobUrl) {
        if (!messagesPane) return;
        // Remove empty-state placeholder if present
        const emptyMsg = messagesPane.querySelector('.cg-empty-msg');
        if (emptyMsg) emptyMsg.remove();

        const row  = document.createElement('div');
        row.className = 'cg-msg-row mine';
        const now  = new Date();
        const time = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        let content = '';
        if (type === 'voice' && blobUrl) {
            content = `<audio controls preload="none" class="cg-voice-player"><source src="${blobUrl}"></audio>`;
        } else {
            content = escHtml(text ?? '').replace(/\n/g, '<br>');
        }
        row.innerHTML = `<div class="cg-bubble">${content}<span class="cg-msg-time">${time}</span></div>`;
        messagesPane.appendChild(row);
        scrollBottom();
    }

    function escHtml(s) {
        return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ──────────────────────── Poll for new messages ────────── */
    @if (!empty($selectedGroup))
    const pollUrl = '{{ route("chat-groups.messages-json", $selectedGroup->id) }}';

    function pollMessages() {
        fetch(pollUrl + '?since=' + lastKnownId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.messages || !data.messages.length) return;
            data.messages.forEach(msg => {
                if (msg.is_mine) { lastKnownId = Math.max(lastKnownId, msg.id); return; } // already shown optimistically
                appendIncomingBubble(msg);
                lastKnownId = Math.max(lastKnownId, msg.id);
            });
        })
        .catch(() => {});
    }

    function appendIncomingBubble(msg) {
        if (!messagesPane) return;
        const emptyMsg = messagesPane.querySelector('.cg-empty-msg');
        if (emptyMsg) emptyMsg.remove();

        const row = document.createElement('div');
        row.className = 'cg-msg-row theirs';
        row.dataset.id = msg.id;

        let content = '';
        if (msg.message_type === 'voice' && msg.voice_path) {
            content = `<audio controls preload="none" class="cg-voice-player"><source src="${msg.voice_path}"></audio>`;
        } else {
            content = escHtml(msg.message).replace(/\n/g, '<br>');
        }
        row.innerHTML = `<div class="cg-msg-sender">${escHtml(msg.user_name)}</div>
            <div class="cg-bubble">${content}<span class="cg-msg-time">${msg.time}</span></div>`;
        messagesPane.appendChild(row);
        scrollBottom();
    }

    setInterval(function () {
        if (!document.hidden) pollMessages();
    }, 20000);
    @endif

    /* ──────────────────────── Auto-scroll ─────────────────── */
    function scrollBottom() {
        if (messagesPane) messagesPane.scrollTop = messagesPane.scrollHeight;
    }
    scrollBottom();
})();
</script>
@endpush
