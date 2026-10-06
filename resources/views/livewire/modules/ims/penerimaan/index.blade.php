<div>
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title">Penerimaan Barang (Goods In)</h2>
            <p class="text-sm text-base-content/70">Tambahkan stok baru ke gudang.</p>

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

                <div class="form-control w-full">
                    <label class="label"><span class="label-text">Pilih Barang <span class="text-error">*</span></span></label>
                    <select wire:model="itemId" class="select select-bordered w-full">
                        <option value="">-- Pilih Barang --</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}">{{ $item->part_number }} - {{ $item->name }}</option>
                        @endforeach
                    </select>
                    @error('itemId') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="form-control w-full">
                    <label class="label"><span class="label-text">Lokasi / Gudang <span class="text-error">*</span></span></label>
                    <select wire:model="locationId" class="select select-bordered w-full">
                        <option value="">-- Pilih Lokasi --</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </select>
                    @error('locationId') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="form-control w-full">
                    <label class="label"><span class="label-text">Jumlah <span class="text-error">*</span></span></label>
                    <input type="number" wire:model="qty" min="1" placeholder="Masukkan jumlah barang masuk" class="input input-bordered w-full" />
                    @error('qty') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="form-control w-full">
                    <label class="label"><span class="label-text">Catatan (Opsional)</span></label>
                    <textarea wire:model="notes" class="textarea textarea-bordered h-24" placeholder="Keterangan tambahan (misal: PO-123)"></textarea>
                    @error('notes') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="card-actions justify-end mt-6">
                    <button type="submit" class="btn btn-primary">
                        <span wire:loading.remove wire:target="submit">Simpan Stok</span>
                        <span wire:loading wire:target="submit" class="loading loading-spinner"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
