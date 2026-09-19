<div class="w-full max-w-6xl mx-auto p-2 sm:p-4 md:p-6 sm:bg-transparent rounded-3xl min-h-screen sm:min-h-[90vh] flex items-center">
    <div class="flex flex-col md:flex-row w-full bg-white rounded-[2rem] shadow-2xl shadow-red-900/5 overflow-hidden min-h-[700px]">
        
        <!-- Left Side: Gradient Banner -->
        <div class="w-full md:w-[55%] relative p-10 text-white rounded-[2rem] overflow-hidden flex flex-col justify-between m-2"
             style="background: linear-gradient(135deg, #E53E3E 0%, #F56565 50%, #C53030 100%);">
            
            <!-- Logo area -->
            <div class="flex items-center gap-2 mb-8 md:mb-0">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                </svg>
                <span class="font-bold text-xl tracking-tight">Cabin Core</span>
            </div>

            <!-- Content Area -->
            <div class="mt-8 md:mt-24 mb-16">
                <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-sm px-4 py-1.5 rounded-full text-sm font-medium mb-6 border border-white/20">
                    Wajib Ganti Password <span class="text-yellow-400">⚠️</span>
                </div>
                <h1 class="text-4xl md:text-5xl font-bold mb-4 tracking-tight leading-tight">Keamanan Pertama</h1>
                <p class="text-red-100/90 text-lg max-w-sm">Demi keamanan data Anda, silakan ubah password default dari administrator sebelum melanjutkan.</p>
            </div>

            <!-- Warning Notice -->
            <div class="mt-auto">
                <div class="bg-black/20 backdrop-blur-md rounded-2xl p-5 text-white border border-white/10">
                    <p class="font-medium text-sm leading-relaxed text-white/90">
                        Pastikan password baru Anda sulit ditebak dan mengandung kombinasi huruf serta angka (minimal 8 karakter).
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Side: Reset Form -->
        <div class="w-full md:w-[45%] p-10 lg:px-16 flex flex-col justify-center">
            
            <div class="max-w-sm w-full mx-auto">
                <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Ubah Password</h2>

                <form wire:submit="updatePassword" class="space-y-6">
                    
                    <!-- Password Input -->
                    <div class="space-y-1.5">
                        <label for="password" class="block text-sm font-semibold text-gray-700">Password Baru</label>
                        <div class="relative">
                            <input wire:model="password" type="password" id="password" class="w-full bg-[#F5F7FA] border-none rounded-xl px-4 py-3.5 text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-red-600 focus:bg-white transition-colors" placeholder="Min. 8 karakter">
                        </div>
                        @error('password') <span class="text-red-500 text-xs font-medium">{{ $message }}</span> @enderror
                    </div>

                    <!-- Password Confirmation Input -->
                    <div class="space-y-1.5">
                        <label for="password_confirmation" class="block text-sm font-semibold text-gray-700">Konfirmasi Password Baru</label>
                        <div class="relative">
                            <input wire:model="password_confirmation" type="password" id="password_confirmation" class="w-full bg-[#F5F7FA] border-none rounded-xl px-4 py-3.5 text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-red-600 focus:bg-white transition-colors" placeholder="Ulangi password baru">
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full bg-[#E53E3E] hover:bg-red-700 text-white font-semibold rounded-xl py-3.5 mt-4 transition-colors flex justify-center items-center gap-2">
                        <span wire:loading.remove wire:target="updatePassword">Simpan Password</span>
                        <span wire:loading wire:target="updatePassword">Menyimpan...</span>
                    </button>
                </form>
                
            </div>
        </div>
        
    </div>
</div>
