<div>
    <div class="cbm-page-header mod-header">
        <div class="mod-title-block">
            <span class="mod-title-accent mod-title-accent-blue">IMS Tracker</span>
            <h1 class="mod-title">Data Master</h1>
            <p class="mod-subtitle">Kelola database inti untuk inventaris (Barang, Kategori, Lokasi, dsb).</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-4">
        
        <!-- Barang / Items -->
        <div class="mod-card flex flex-col justify-between" style="padding: 1.5rem;">
            <div>
                <div style="display: inline-flex; padding: 0.75rem; background: #e0f2fe; color: #0284c7; border-radius: 0.5rem; margin-bottom: 1rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 2rem; height: 2rem;"><path d="M12.378 1.602a.75.75 0 00-.756 0L3 6.632l9 5.25 9-5.25-8.622-5.03zM21.75 7.93l-9 5.25v9l8.628-5.032a.75.75 0 00.372-.648V7.93zM11.25 22.18v-9l-9-5.25v8.57a.75.75 0 00.372.648l8.628 5.033z" /></svg>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">Data Barang</h3>
                <p style="color: var(--cbm-text-muted); font-size: 0.875rem; margin-bottom: 1.5rem;">Kelola master data Spare parts, Consumables, dan Tools.</p>
            </div>
            <a href="{{ route('ims.master.items') }}" wire:navigate class="mod-btn-primary text-center" style="width: 100%; display: block;">Kelola Barang</a>
        </div>

        <!-- Kategori -->
        <div class="mod-card flex flex-col justify-between" style="padding: 1.5rem;">
            <div>
                <div style="display: inline-flex; padding: 0.75rem; background: #fef3c7; color: #d97706; border-radius: 0.5rem; margin-bottom: 1rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 2rem; height: 2rem;"><path fill-rule="evenodd" d="M3 6a3 3 0 013-3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6zm4.5 7.5a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0v-2.25a.75.75 0 01.75-.75zm3.75-1.5a.75.75 0 00-1.5 0v4.5a.75.75 0 001.5 0V12zm3.75-1.5a.75.75 0 01.75.75v6a.75.75 0 01-1.5 0v-6a.75.75 0 01.75-.75z" clip-rule="evenodd" /></svg>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">Kategori</h3>
                <p style="color: var(--cbm-text-muted); font-size: 0.875rem; margin-bottom: 1.5rem;">Kelompokkan barang ke dalam kategori & ATA Chapter.</p>
            </div>
            <a href="{{ route('ims.master.categories') }}" wire:navigate class="mod-btn-primary text-center" style="width: 100%; display: block; background: #fff; color: var(--cbm-text); border: 1px solid var(--cbm-border);">Kelola Kategori</a>
        </div>

        <!-- Lokasi -->
        <div class="mod-card flex flex-col justify-between" style="padding: 1.5rem;">
            <div>
                <div style="display: inline-flex; padding: 0.75rem; background: #dcfce7; color: #15803d; border-radius: 0.5rem; margin-bottom: 1rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 2rem; height: 2rem;"><path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" /></svg>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">Lokasi & Rak</h3>
                <p style="color: var(--cbm-text-muted); font-size: 0.875rem; margin-bottom: 1.5rem;">Manajemen gudang, rak, dan lokasi penyimpanan barang.</p>
            </div>
            <a href="{{ route('ims.master.locations') }}" wire:navigate class="mod-btn-primary text-center" style="width: 100%; display: block; background: #fff; color: var(--cbm-text); border: 1px solid var(--cbm-border);">Kelola Lokasi</a>
        </div>

        <!-- Satuan (Units) -->
        <div class="mod-card flex flex-col justify-between" style="padding: 1.5rem;">
            <div>
                <div style="display: inline-flex; padding: 0.75rem; background: #f3e8ff; color: #7e22ce; border-radius: 0.5rem; margin-bottom: 1rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 2rem; height: 2rem;"><path fill-rule="evenodd" d="M10.5 3.75a6 6 0 00-5.98 6.496A5.25 5.25 0 006.75 20.25H18a4.5 4.5 0 002.206-8.423 3.75 3.75 0 00-4.133-4.303A6.001 6.001 0 0010.5 3.75zm2.25 6a.75.75 0 00-1.5 0v4.94l-1.72-1.72a.75.75 0 00-1.06 1.06l3 3a.75.75 0 001.06 0l3-3a.75.75 0 10-1.06-1.06l-1.72 1.72V9.75z" clip-rule="evenodd" /></svg>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">Satuan (UoM)</h3>
                <p style="color: var(--cbm-text-muted); font-size: 0.875rem; margin-bottom: 1.5rem;">Unit of Measurement seperti PCS, EA, Liter, dll.</p>
            </div>
            <a href="{{ route('ims.master.units') }}" wire:navigate class="mod-btn-primary text-center" style="width: 100%; display: block; background: #fff; color: var(--cbm-text); border: 1px solid var(--cbm-border);">Kelola Satuan</a>
        </div>

        <!-- Supplier -->
        <div class="mod-card flex flex-col justify-between" style="padding: 1.5rem;">
            <div>
                <div style="display: inline-flex; padding: 0.75rem; background: #fee2e2; color: #b91c1c; border-radius: 0.5rem; margin-bottom: 1rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 2rem; height: 2rem;"><path d="M4.5 3.75a3 3 0 00-3 3v.75h21v-.75a3 3 0 00-3-3h-15z" /><path fill-rule="evenodd" d="M22.5 9.75h-21v7.5a3 3 0 003 3h15a3 3 0 003-3v-7.5zm-18 3.75a.75.75 0 01.75-.75h6a.75.75 0 010 1.5h-6a.75.75 0 01-.75-.75zm.75 2.25a.75.75 0 000 1.5h3a.75.75 0 000-1.5h-3z" clip-rule="evenodd" /></svg>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">Supplier</h3>
                <p style="color: var(--cbm-text-muted); font-size: 0.875rem; margin-bottom: 1.5rem;">Database vendor dan penyedia spare part.</p>
            </div>
            <a href="{{ route('ims.master.suppliers') }}" wire:navigate class="mod-btn-primary text-center" style="width: 100%; display: block; background: #fff; color: var(--cbm-text); border: 1px solid var(--cbm-border);">Kelola Supplier</a>
        </div>

        <!-- Aircraft Type -->
        <div class="mod-card flex flex-col justify-between" style="padding: 1.5rem;">
            <div>
                <div style="display: inline-flex; padding: 0.75rem; background: #e0e7ff; color: #4338ca; border-radius: 0.5rem; margin-bottom: 1rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 2rem; height: 2rem;"><path d="M3.375 4.5C2.339 4.5 1.5 5.34 1.5 6.375V13.5h12V6.375c0-1.036-.84-1.875-1.875-1.875h-8.25zM13.5 15h-12v2.625c0 1.035.84 1.875 1.875 1.875h.375v-1.5a3 3 0 016 0v1.5h3.75v-1.5a3 3 0 016 0v1.5h1.65a.75.75 0 00.75-.75v-1.795a2.25 2.25 0 00-.547-1.467l-2.062-2.474a1.5 1.5 0 00-1.15-.564H13.5v4.125z" /><path d="M20.25 18v-1.5a1.5 1.5 0 00-3 0V18h3zM7.5 18v-1.5a1.5 1.5 0 00-3 0V18h3z" /></svg>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">Tipe Pesawat</h3>
                <p style="color: var(--cbm-text-muted); font-size: 0.875rem; margin-bottom: 1.5rem;">Referensi pesawat (C208B, PC-6, dll).</p>
            </div>
            <a href="{{ route('ims.master.aircraft-types') }}" wire:navigate class="mod-btn-primary text-center" style="width: 100%; display: block; background: #fff; color: var(--cbm-text); border: 1px solid var(--cbm-border);">Kelola Tipe Pesawat</a>
        </div>

    </div>
</div>
