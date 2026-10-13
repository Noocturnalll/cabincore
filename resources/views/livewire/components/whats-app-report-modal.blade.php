<div>
    @if($isOpen)
        <div class="wa-modal-backdrop" style="position:fixed;inset:0;background:rgba(0,0,0,0.78);backdrop-filter:blur(6px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;">
            <div class="wa-modal-card" style="background:#0d1117;border:1px solid #30363d;border-radius:14px;width:100%;max-width:1050px;max-height:92vh;display:flex;flex-direction:column;box-shadow:0 25px 60px rgba(0,0,0,0.85);overflow:hidden;animation:waFadeIn .18s ease-out;">
                
                {{-- Header --}}
                <div style="padding:1rem 1.4rem;border-bottom:1px solid #21262d;display:flex;justify-content:space-between;align-items:center;background:#161b22;">
                    <div style="display:flex;align-items:center;gap:.75rem;">
                        <div style="width:38px;height:38px;border-radius:10px;background:rgba(37,211,102,.15);border:1px solid rgba(37,211,102,.3);display:flex;align-items:center;justify-content:center;color:#25d366;flex-shrink:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:22px;height:22px;">
                                <path fill-rule="evenodd" d="M1.5 4.5a3 3 0 013-3h1.372c.86 0 1.61.586 1.819 1.42l1.105 4.423a1.875 1.875 0 01-.694 1.955l-1.293.97c-.135.101-.164.249-.126.352a11.285 11.285 0 006.697 6.697c.103.038.251.009.352-.126l.97-1.293a1.875 1.875 0 011.955-.694l4.423 1.105c.834.209 1.42.959 1.42 1.82V19.5a3 3 0 01-3 3h-2.25C8.552 22.5 1.5 15.448 1.5 6.75V4.5z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <div style="display:flex;align-items:center;gap:.5rem;">
                                <h3 style="font-size:1.05rem;font-weight:800;color:#f0f6fc;margin:0;">{{ $reportTitle }}</h3>
                                <span style="font-size:.7rem;padding:.15rem .5rem;border-radius:999px;background:#238636;color:#ffffff;font-weight:700;text-transform:uppercase;">
                                    {{ $module }}
                                </span>
                            </div>
                            <div style="font-size:.78rem;color:#8b949e;margin-top:.15rem;">
                                Periode: <strong style="color:#c9d1d9;">{{ $startDate }} @if($endDate && $endDate !== $startDate) s/d {{ $endDate }} @endif</strong>
                                @if($station) &bull; Stasiun: <strong style="color:#58a6ff;">{{ $station }}</strong> @else &bull; <strong style="color:#58a6ff;">Nasional</strong> @endif
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" wire:click="close" style="background:transparent;border:none;color:#8b949e;cursor:pointer;padding:.4rem;border-radius:6px;transition:all .15s ease;" onmouseover="this.style.color='#f0f6fc'" onmouseout="this.style.color='#8b949e'">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:20px;height:20px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Status & QR Pair Bar --}}
                <div style="background:#131820;border-bottom:1px solid #21262d;padding:.65rem 1.4rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.6rem;">
                    <div style="display:flex;align-items:center;gap:.6rem;font-size:.8rem;">
                        @if($botReady)
                            <span style="display:inline-flex;align-items:center;gap:.35rem;padding:.2rem .6rem;background:rgba(35,134,54,.15);border:1px solid rgba(35,134,54,.3);color:#3fb950;border-radius:999px;font-weight:700;">
                                <span style="width:8px;height:8px;border-radius:50%;background:#3fb950;display:inline-block;box-shadow:0 0 8px #3fb950;"></span>
                                WhatsApp Bot Terhubung &amp; Siap
                            </span>
                        @else
                            <span style="display:inline-flex;align-items:center;gap:.35rem;padding:.2rem .6rem;background:rgba(210,153,34,.15);border:1px solid rgba(210,153,34,.3);color:#e3b341;border-radius:999px;font-weight:700;">
                                <span style="width:8px;height:8px;border-radius:50%;background:#e3b341;display:inline-block;"></span>
                                Bot Belum Terhubung
                            </span>
                        @endif
                        <span style="color:#8b949e;">{{ $botMessage }}</span>
                    </div>

                    <div style="display:flex;align-items:center;gap:.4rem;">
                        <button type="button" wire:click="toggleQrSection" style="font-size:.75rem;font-weight:600;padding:.3rem .65rem;border-radius:6px;background:{{ $showQrSection ? '#30363d' : '#21262d' }};color:#c9d1d9;border:1px solid #30363d;cursor:pointer;display:inline-flex;align-items:center;gap:.35rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                            </svg>
                            {{ $showQrSection ? 'Tutup Scan QR' : '📲 Scan QR Code di Sini' }}
                        </button>

                        <button type="button" wire:click="refreshBotStatus" style="font-size:.75rem;padding:.3rem .55rem;border-radius:6px;background:#21262d;color:#58a6ff;border:1px solid #30363d;cursor:pointer;" title="Periksa Status Bot">
                            🔄 Periksa
                        </button>
                    </div>
                </div>

                {{-- QR Code Section (Scan in UI without going to terminal!) --}}
                @if($showQrSection)
                    <div @if(!$botReady) wire:poll.3000ms="refreshBotStatus" @endif style="background:#090d13;border-bottom:1px solid #21262d;padding:1.2rem;display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:1.5rem;">
                        <div style="background:#ffffff;padding:.85rem;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;">
                            @if($botQr)
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($botQr) }}" alt="WhatsApp QR Code" style="width:200px;height:200px;display:block;" />
                            @else
                                <div style="width:200px;height:200px;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#333;text-align:center;padding:1rem;">
                                    <div style="font-size:.85rem;font-weight:700;margin-bottom:.4rem;">
                                        @if($botReady)
                                            ✅ Sudah Terhubung!
                                        @else
                                            Menunggu QR Code...
                                        @endif
                                    </div>
                                    <div style="font-size:.72rem;color:#666;">
                                        @if($botReady)
                                            Bot siap digunakan, tidak perlu scan lagi.
                                        @else
                                            Pastikan service node whatsapp aktif.
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div style="max-width:440px;">
                            <h4 style="font-size:.92rem;font-weight:700;color:#f0f6fc;margin:0 0 .4rem;">
                                📱 Scan QR Code Langsung di Layar Ini
                            </h4>
                            <p style="font-size:.78rem;color:#8b949e;line-height:1.45;margin:0 0 .75rem;">
                                Anda tidak perlu lagi buka terminal / server backend. Cukup scan QR code di atas menggunakan WhatsApp di ponsel Anda:
                            </p>
                            <ol style="font-size:.76rem;color:#c9d1d9;margin:0 0 1rem;padding-left:1.15rem;line-height:1.55;">
                                <li>Buka aplikasi WhatsApp di HP Anda.</li>
                                <li>Buka <strong>Menu / Titik Tiga</strong> (Android) atau <strong>Pengaturan</strong> (iPhone).</li>
                                <li>Pilih <strong>Perangkat Tertaut (Linked Devices)</strong> &gt; ketuk <strong>Tautkan Perangkat</strong>.</li>
                                <li>Arahkan kamera ke QR Code di samping. Sistem akan otomatis terhubung!</li>
                            </ol>

                            <div style="display:flex;gap:.5rem;">
                                <button type="button" wire:click="restartBotClient" style="font-size:.75rem;font-weight:600;padding:.35rem .75rem;background:#21262d;border:1px solid #30363d;color:#f85149;border-radius:6px;cursor:pointer;">
                                    🔄 Putuskan &amp; Minta QR Baru
                                </button>
                                <button type="button" wire:click="refreshBotStatus" style="font-size:.75rem;font-weight:600;padding:.35rem .75rem;background:#238636;border:1px solid #2ea043;color:#ffffff;border-radius:6px;cursor:pointer;">
                                    ✓ Saya Sudah Scan
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Modal Body: 2 Columns (Preview & Dispatch) --}}
                <div style="padding:1.2rem 1.4rem;overflow-y:auto;display:grid;grid-template-columns:1.15fr 0.85fr;gap:1.25rem;flex:1;">
                    
                    {{-- Column 1: WhatsApp Message Preview Bubble --}}
                    <div style="display:flex;flex-direction:column;gap:.6rem;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:.82rem;font-weight:700;color:#c9d1d9;">Preview Format Pesan WhatsApp:</span>
                            <div style="display:flex;gap:.4rem;">
                                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('wa-report-text-area').value); alert('Teks laporan berhasil disalin ke clipboard! ✓');" style="font-size:.74rem;font-weight:600;padding:.25rem .6rem;background:#21262d;border:1px solid #30363d;color:#58a6ff;border-radius:5px;cursor:pointer;">
                                    📋 Salin Teks
                                </button>
                                <a href="https://wa.me/?text={{ rawurlencode($reportText) }}" target="_blank" style="font-size:.74rem;font-weight:600;padding:.25rem .6rem;background:rgba(37,211,102,.15);border:1px solid rgba(37,211,102,.3);color:#25d366;border-radius:5px;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;">
                                    ↗ Buka WA Web
                                </a>
                            </div>
                        </div>

                        {{-- Text Area with WhatsApp Chat Bubble styling --}}
                        <div style="position:relative;background:#0b141a;border:1px solid #21262d;border-radius:10px;padding:.85rem;display:flex;flex-direction:column;flex:1;min-height:360px;">
                            <textarea id="wa-report-text-area" wire:model.live="reportText" style="width:100%;height:100%;min-height:340px;background:transparent;border:none;color:#e9edef;font-family:'Courier New',Courier,monospace;font-size:.82rem;line-height:1.5;resize:vertical;outline:none;white-space:pre-wrap;" spellcheck="false"></textarea>
                        </div>

                        @if(!empty($evidenceSamples))
                            <div style="font-size:.75rem;color:#8b949e;padding:.4rem .6rem;background:#161b22;border-radius:6px;border:1px solid #21262d;">
                                📸 Ditemukan <strong>{{ $evidenceCount }} foto eviden</strong> tersimpan pada periode ini.
                            </div>
                        @endif
                    </div>

                    {{-- Column 2: WhatsApp Dispatch & Target Selection --}}
                    <div style="display:flex;flex-direction:column;gap:.85rem;">
                        <div>
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.4rem;">
                                <label style="font-size:.82rem;font-weight:700;color:#c9d1d9;">Pilih Grup WhatsApp Tujuan:</label>
                                <button type="button" wire:click="loadWhatsAppGroups" style="font-size:.72rem;color:#58a6ff;background:transparent;border:none;cursor:pointer;text-decoration:underline;">
                                    🔄 Sync Daftar Grup
                                </button>
                            </div>

                            <div style="background:#161b22;border:1px solid #21262d;border-radius:8px;padding:.65rem;max-height:220px;overflow-y:auto;display:flex;flex-direction:column;gap:.4rem;">
                                @if(count($waGroups) > 0)
                                    <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:.4rem;border-bottom:1px solid #21262d;margin-bottom:.2rem;">
                                        <button type="button" wire:click="toggleSelectAllGroups" style="font-size:.72rem;background:transparent;border:none;color:#58a6ff;cursor:pointer;">
                                            {{ count($selectedWaGroups) === count($waGroups) ? 'Batal Pilih Semua' : 'Centang Semua (' . count($waGroups) . ')' }}
                                        </button>
                                        <span style="font-size:.72rem;color:#8b949e;">{{ count($selectedWaGroups) }} dipilih</span>
                                    </div>
                                    @foreach($waGroups as $g)
                                        <label style="display:flex;align-items:center;gap:.5rem;font-size:.78rem;color:#c9d1d9;cursor:pointer;padding:.25rem .35rem;border-radius:4px;transition:background .15s ease;" onmouseover="this.style.background='#21262d'" onmouseout="this.style.background='transparent'">
                                            <input type="checkbox" wire:model.live="selectedWaGroups" value="{{ $g['id'] }}" style="border-radius:3px;accent-color:#25d366;" />
                                            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $g['name'] }}</span>
                                        </label>
                                    @endforeach
                                @else
                                    <div style="padding:1rem .5rem;text-align:center;color:#8b949e;font-size:.78rem;">
                                        Belum ada grup terdeteksi.<br />
                                        @if($botReady)
                                            Klik tombol <strong>Sync Daftar Grup</strong> di atas.
                                        @else
                                            Hubungkan bot terlebih dahulu via <strong>Scan QR</strong>.
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Manual Target Number --}}
                        <div>
                            <label style="font-size:.78rem;color:#8b949e;display:block;margin-bottom:.25rem;">Tambah Nomor / JID Manual:</label>
                            <div style="display:flex;gap:.4rem;">
                                <input type="text" wire:model="manualWaTarget" placeholder="Contoh: 081234567890 atau JID grup" style="flex:1;font-size:.78rem;padding:.35rem .6rem;background:#0d1117;border:1px solid #30363d;border-radius:6px;color:#f0f6fc;" />
                                <button type="button" wire:click="addManualWaTarget" style="font-size:.75rem;padding:.35rem .65rem;background:#21262d;border:1px solid #30363d;color:#c9d1d9;border-radius:6px;cursor:pointer;">
                                    + Tambah
                                </button>
                            </div>
                        </div>

                        {{-- Alerts --}}
                        @if($sendSuccessMessage)
                            <div style="padding:.6rem .8rem;background:rgba(35,134,54,.15);border:1px solid rgba(35,134,54,.3);border-radius:6px;color:#3fb950;font-size:.78rem;display:flex;align-items:center;gap:.4rem;">
                                <span>✓</span> {{ $sendSuccessMessage }}
                            </div>
                        @endif

                        @if($sendErrorMessage)
                            <div style="padding:.6rem .8rem;background:rgba(248,81,73,.15);border:1px solid rgba(248,81,73,.3);border-radius:6px;color:#f85149;font-size:.78rem;display:flex;align-items:center;gap:.4rem;">
                                <span>⚠️</span> {{ $sendErrorMessage }}
                            </div>
                        @endif

                        {{-- Submit Button --}}
                        <div style="margin-top:auto;padding-top:1rem;">
                            <button type="button" wire:click="sendReportToSelectedGroups" wire:loading.attr="disabled" style="width:100%;padding:.7rem 1.2rem;background:#238636;border:1px solid #2ea043;border-radius:8px;color:#ffffff;font-weight:700;font-size:.88rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;box-shadow:0 4px 14px rgba(35,134,54,.35);" onmouseover="this.style.background='#2ea043'" onmouseout="this.style.background='#238636'">
                                <span wire:loading.remove wire:target="sendReportToSelectedGroups" style="display:inline-flex;align-items:center;gap:.4rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:18px;height:18px;">
                                        <path d="M3.105 2.289a.75.75 0 00-.826.95l1.414 4.925A1.5 1.5 0 004.75 9.25h6.5a.75.75 0 010 1.5h-6.5a1.5 1.5 0 00-1.057 1.086l-1.414 4.925a.75.75 0 00.826.95 28.896 28.896 0 0015.293-7.154.75.75 0 000-1.114A28.897 28.897 0 003.105 2.289z" />
                                    </svg>
                                    Kirim Laporan ke {{ count($selectedWaGroups) }} Grup WhatsApp
                                </span>
                                <span wire:loading wire:target="sendReportToSelectedGroups">
                                    Mengirim Laporan ke WhatsApp...
                                </span>
                            </button>
                            <p style="font-size:.7rem;color:#8b949e;text-align:center;margin:.4rem 0 0;">
                                Opsi Utama: Pengiriman otomatis via Bot WhatsApp Gateway
                            </p>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    @endif
</div>
