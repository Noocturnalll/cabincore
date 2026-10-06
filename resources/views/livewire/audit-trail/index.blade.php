<div>
    <div class="cbm-page-header" style="margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="width: 3.5rem; height: 3.5rem; border-radius: 1rem; background: linear-gradient(135deg, var(--cbm-blue), var(--cbm-purple)); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; box-shadow: 0 8px 1rem rgba(59,130,246,0.3);">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.75rem; height: 1.75rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                </svg>
            </div>
            <div>
                <h1 class="cbm-greeting" style="font-size: 1.5rem;">Audit Trail</h1>
                <p class="cbm-greeting-sub">Riwayat aktivitas dan perubahan data sistem</p>
            </div>
        </div>
    </div>

    <div class="cbm-card" style="padding: 0; overflow: hidden; border: 1px solid var(--cbm-card-border);">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 50rem;">
                <thead>
                    <tr style="background: var(--cbm-nav-hover); border-bottom: 1px solid var(--cbm-card-border); text-align: left;">
                        <th style="padding: 1rem 1.5rem; font-size: 0.75rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: 0.05em; width: 11.25rem;">Waktu</th>
                        <th style="padding: 1rem 1.5rem; font-size: 0.75rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: 0.05em; width: 15.625rem;">Pengguna</th>
                        <th style="padding: 1rem 1.5rem; font-size: 0.75rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: 0.05em; width: 9.375rem;">Aktivitas</th>
                        <th style="padding: 1rem 1.5rem; font-size: 0.75rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Detail</th>
                    </tr>
                </thead>
                <tbody style="background: var(--cbm-card-bg);">
                    @forelse($logs as $log)
                    <tr style="border-bottom: 1px solid var(--cbm-card-border);">
                        <td style="padding: 1rem 1.5rem; font-size: 0.875rem; color: var(--cbm-text-muted);">
                            <span style="font-weight: 600; color: var(--cbm-text);">{{ $log->created_at->format('d M Y') }}</span><br>
                            {{ $log->created_at->format('H:i:s') }}
                        </td>
                        <td style="padding: 1rem 1.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div style="width: 2rem; height: 2rem; border-radius: 62.4375rem; background: linear-gradient(135deg, var(--cbm-blue), var(--cbm-purple)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.75rem;">
                                    {{ $log->user ? substr($log->user->name, 0, 1) : 'S' }}
                                </div>
                                <div>
                                    <div style="font-size: 0.875rem; font-weight: 600; color: var(--cbm-text);">{{ $log->user ? $log->user->name : 'Sistem' }}</div>
                                    <div style="font-size: 0.75rem; color: var(--cbm-text-muted);">{{ $log->user ? $log->user->email : 'Otomatis' }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 1rem 1.5rem;">
                            <span style="font-size: 0.6875rem; font-weight: 700; padding: 0.25rem 0.5rem; border-radius: 6.1875rem; background: rgba(59,130,246,0.1); color: #3b82f6; text-transform: uppercase;">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td style="padding: 1rem 1.5rem; font-size: 0.875rem; color: var(--cbm-text-muted);">
                            {{ $log->description ?: '-' }}
                            @if($log->ip_address)
                            <div style="font-size: 0.6875rem; margin-top: 0.25rem; opacity: 0.7;">IP: {{ $log->ip_address }}</div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="padding: 3rem 1.5rem; text-align: center; color: var(--cbm-text-muted); font-size: 0.875rem;">
                            Belum ada riwayat aktivitas di sistem.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($logs->hasPages())
        <div style="padding: 1rem 1.5rem; border-top: 1px solid var(--cbm-card-border); background: var(--cbm-card-bg);">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>
