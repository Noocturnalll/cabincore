<div>
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title">Transfer Stok (Mutasi Antar Gudang)</h2>
            <p class="text-sm text-base-content/70">Pindahkan stok barang dari satu lokasi ke lokasi lainnya.</p>

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
                    <select wire:model.live="itemId" class="select select-bordered w-full">
                        <option value="">-- Pilih Barang --</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}">{{ $item->part_number }} - {{ $item->name }}</option>
                        @endforeach
                    </select>
                    @error('itemId') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control w-full">
                        <label class="label"><span class="label-text">Lokasi Asal <span class="text-error">*</span></span></label>
                        <select wire:model.live="fromLocationId" class="select select-bordered w-full">
                            <option value="">-- Pilih Lokasi Asal --</option>
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                            @endforeach
                        </select>
                        @error('fromLocationId') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control w-full">
                        <label class="label"><span class="label-text">Lokasi Tujuan <span class="text-error">*</span></span></label>
                        <select wire:model="toLocationId" class="select select-bordered w-full">
                            <option value="">-- Pilih Lokasi Tujuan --</option>
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                            @endforeach
                        </select>
                        @error('toLocationId') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control w-full">
                        <label class="label"><span class="label-text">Stok Tersedia (Lokasi Asal)</span></label>
                        <input type="text" value="{{ $availableQty }}" class="input input-bordered w-full bg-base-200" readonly />
                    </div>

                    <div class="form-control w-full">
                        <label class="label"><span class="label-text">Jumlah Ditransfer <span class="text-error">*</span></span></label>
                        <input type="number" wire:model="qty" min="1" max="{{ $availableQty > 0 ? $availableQty : 1 }}" placeholder="Masukkan jumlah transfer" class="input input-bordered w-full" />
                        @error('qty') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-control w-full">
                    <label class="label"><span class="label-text">Catatan (Opsional)</span></label>
                    <textarea wire:model="notes" class="textarea textarea-bordered h-24" placeholder="Keterangan tambahan"></textarea>
                    @error('notes') <span class="text-error text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="card-actions justify-end mt-6">
                    <button type="submit" class="btn btn-primary" @if($availableQty <= 0) disabled @endif>
                        <span wire:loading.remove wire:target="submit">Transfer Stok</span>
                        <span wire:loading wire:target="submit" class="loading loading-spinner"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
