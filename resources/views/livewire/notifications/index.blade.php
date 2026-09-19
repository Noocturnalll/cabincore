<div>
    <div class="cbm-page-header">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 3.5rem; height: 3.5rem; border-radius: 1rem; background: linear-gradient(135deg, var(--cbm-yellow), var(--cbm-red)); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; box-shadow: 0 8px 16px rgba(245,158,11,0.3);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.75rem; height: 1.75rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                </div>
                <div>
                    <h1 class="cbm-greeting" style="font-size: 1.5rem;">Pusat Notifikasi</h1>
                    <p class="cbm-greeting-sub">Pembaruan real-time untuk aktivitas dan status laporan Anda.</p>
                </div>
            </div>
            
            <button class="cbm-btn-outline">Tandai Semua Dibaca</button>
        </div>
    </div>

    <style>
        .cbm-btn-outline {
            background: transparent;
            color: var(--cbm-text-muted);
            border: 1px solid var(--cbm-input-border);
            border-radius: 0.75rem;
            padding: 0.5rem 1rem;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--cbm-transition);
        }
        .cbm-btn-outline:hover {
            border-color: var(--cbm-blue);
            color: var(--cbm-blue);
            background: var(--cbm-nav-hover);
        }
        
        .notif-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .notif-card {
            background: var(--cbm-card-bg);
            border: 1px solid var(--cbm-card-border);
            border-radius: 1rem;
            padding: 1.25rem;
            display: flex;
            gap: 1.25rem;
            transition: var(--cbm-transition), transform 0.2s;
            position: relative;
            overflow: hidden;
        }
        .notif-card:hover {
            transform: translateX(4px);
            border-color: var(--cbm-input-border);
        }
        .notif-card.unread {
            background: var(--cbm-nav-hover);
            border-left: 4px solid var(--cbm-blue);
        }
        
        .notif-icon {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .icon-success { background: rgba(16,185,129,0.15); color: #10b981; }
        .icon-warning { background: rgba(245,158,11,0.15); color: #f59e0b; }
        .icon-error   { background: rgba(244,63,94,0.15); color: #f43f5e; }
        .icon-info    { background: rgba(59,130,246,0.15); color: #3b82f6; }
        
        .notif-content {
            flex: 1;
        }
        .notif-title {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--cbm-text);
            margin-bottom: 0.25rem;
        }
        .notif-message {
            font-size: 0.875rem;
            color: var(--cbm-text-muted);
            line-height: 1.5;
            margin-bottom: 0.5rem;
        }
        .notif-time {
            font-size: 0.75rem;
            color: var(--cbm-text-sub);
            font-weight: 500;
        }
    </style>

    <div class="notif-list">
        @forelse($notifications as $notif)
        <div class="notif-card {{ !$notif['is_read'] ? 'unread' : '' }}">
            <div class="notif-icon icon-{{ $notif['type'] }}">
                @if($notif['type'] === 'success')
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.25rem; height: 1.25rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                @elseif($notif['type'] === 'warning')
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.25rem; height: 1.25rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                @elseif($notif['type'] === 'error')
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.25rem; height: 1.25rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.25rem; height: 1.25rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                @endif
            </div>
            <div class="notif-content">
                <div class="notif-title">{{ $notif['title'] }}</div>
                <div class="notif-message">{{ $notif['message'] }}</div>
                <div class="notif-time">{{ $notif['time'] }}</div>
            </div>
            
            @if(!$notif['is_read'])
            <div style="position: absolute; right: 1.5rem; top: 1.5rem; width: 0.5rem; height: 0.5rem; background: var(--cbm-blue); border-radius: 50%;"></div>
            @endif
        </div>
        @empty
        <div class="cbm-card" style="text-align: center; padding: 4rem 2rem;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" style="width: 4rem; height: 4rem; margin: 0 auto 1rem; color: var(--cbm-text-muted); opacity: 0.5;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
            </svg>
            <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--cbm-text); margin-bottom: 0.5rem;">Tidak Ada Notifikasi Baru</h3>
            <p style="color: var(--cbm-text-sub); font-size: 0.875rem;">Anda sudah membaca semua pemberitahuan.</p>
        </div>
        @endforelse
    </div>
</div>
