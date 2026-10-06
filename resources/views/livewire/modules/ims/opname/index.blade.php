<div>
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title">Stock Opname / Penyesuaian Stok</h2>
            <p class="text-sm text-base-content/70">Sesuaikan jumlah stok fisik dengan sistem.</p>

            @if (session()->has('success'))
                <div class="alert alert-success mt-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session()->has('error'))
                <div class="alert alert-error mt-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <form wire:submit.prevent="submit" class="mt-4 space-y-4">
                <div class="form-control w-full">
                    <label class="label"><span class="label-text">Cari Barang</span></label>
                    <input type="text" wire:model.live.debounce.300ms="searchItem" placeholder="Ketik nama atau part number..." class="input input-bordered w-full" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control w-full">
                        <label class="label"><span class="label-text">Pilih Barang <span class="text-error">*</span></span></label>
                        <select wire:model.live="itemId" class="select select-bordered w-full">
                            <option value="">-- Pilih Barang --</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}">{{ $item->part_number }} - {{ $item->name }}</option>
                            @endforeach
                        </select>
                        @error('itemId') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control w-full">
                        <label class="label"><span class="label-text">Lokasi / Gudang <span class="text-error">*</span></span></label>
                        <select wire:model.live="locationId" class="select select-bordered w-full">
                            <option value="">-- Pilih Lokasi --</option>
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                            @endforeach
                        </select>
                        @error('locationId') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control w-full">
                        <label class="label"><span class="label-text">Stok Saat Ini (Sistem)</span></label>
                        <input type="text" value="{{ $currentQty }}" class="input input-bordered w-full bg-base-200" readonly />
                    </div>

                    <div class="form-control w-full">
                        <label class="label"><span class="label-text">Stok Fisik (Baru) <span class="text-error">*</span></span></label>
                        <input type="number" wire:model="newQty" min="0" placeholder="Masukkan jumlah aktual" class="input input-bordered w-full" />
                        @error('newQty') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-control w-full">
                    <label class="label"><span class="label-text">Alasan Penyesuaian <span class="text-error">*</span></span></label>
                    <textarea wire:model="reason" class="textarea textarea-bordered h-24" placeholder="Misal: Stok hilang, selisih perhitungan, barang rusak"></textarea>
                    @error('reason') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="card-actions justify-end mt-6">
                    <button type="submit" class="btn btn-warning">
                        <span wire:loading.remove wire:target="submit">Sesuaikan Stok</span>
                        <span wire:loading wire:target="submit" class="loading loading-spinner"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
