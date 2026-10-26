<x-guest-layout>
    <!-- Card Utama Login -->
    <div class="bg-white rounded-[2rem] shadow-2xl shadow-blue-100/50 overflow-hidden border border-gray-100">
        
        <!-- Header Card: Gradient & Branding -->
        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 p-8 text-white text-center relative overflow-hidden">
            <!-- Dekorasi Lingkaran -->
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
            <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-blue-400/20 rounded-full blur-2xl"></div>
            
            <div class="relative z-10">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg border border-white/30">
                    <x-auth-icon name="qrcode" class="text-3xl" />
                </div>
                <h2 class="text-2xl font-extrabold tracking-tight">E-ABSENSI</h2>
                <p class="text-blue-100 text-sm mt-1 font-medium italic opacity-90">Sistem Absensi Berbasis QR Code</p>
            </div>
        </div>

        <div class="p-8 sm:p-10">
            <div class="mb-8">
                <h3 class="text-xl font-bold text-gray-800">Selamat Datang! </h3>
                <p class="text-gray-500 text-sm mt-1 leading-relaxed">Silakan masuk menggunakan akun Anda untuk mengakses sistem absensi.</p>
            </div>

            <!-- Session Status -->
            <x-auth-session-status class="mb-6" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <!-- Email Address -->
                <div class="space-y-1.5">
                    <label for="email" class="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Email / NISN</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
                            <x-auth-icon name="envelope" class="text-sm" />
                        </div>
                        <input id="email" type="text" name="email" value="{{ old('email') }}" required autofocus
                            placeholder="Masukkan email atau NISN Anda"
                            class="block w-full pl-11 pr-4 py-3.5 bg-gray-50 border-gray-200 border rounded-2xl text-sm transition-all focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:bg-white placeholder:text-gray-400">
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Password -->
                <div class="space-y-1.5">
                    <div class="flex justify-between items-center ml-1">
                        <label for="password" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Kata Sandi</label>
                        @if (Route::has('password.request'))
                            <a class="text-xs font-bold text-blue-600 hover:text-blue-700 transition-colors" href="{{ route('password.request') }}">
                                Lupa Sandi?
                            </a>
                        @endif
                    </div>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
                            <x-auth-icon name="lock" class="text-sm" />
                        </div>
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                            placeholder="Masukkan kata sandi Anda"
                            class="block w-full pl-11 pr-12 py-3.5 bg-gray-50 border-gray-200 border rounded-2xl text-sm transition-all focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:bg-white placeholder:text-gray-400">
                        <button type="button" data-password-toggle aria-controls="password" aria-pressed="false" aria-label="Tampilkan atau sembunyikan sandi" class="absolute inset-y-0 right-0 px-4 flex items-center text-gray-400 hover:text-blue-600 focus-visible:ring-2 focus-visible:ring-blue-500 rounded-r-2xl transition-colors">
                            <span data-password-hidden><x-auth-icon name="eye" class="text-sm" /></span>
                            <span data-password-visible hidden><x-auth-icon name="eye-slash" class="text-sm" /></span>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Remember Me -->
                <div class="flex items-center ml-1">
                    <label for="remember_me" class="inline-flex items-center cursor-pointer group">
                        <input id="remember_me" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 transition-all cursor-pointer" name="remember">
                        <span class="ms-3 text-sm text-gray-600 font-medium group-hover:text-gray-900 transition-colors">{{ __('Ingat saya di perangkat ini') }}</span>
                    </label>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white font-bold py-4 px-4 rounded-2xl transition-all shadow-lg shadow-blue-200 hover:shadow-blue-300 transform active:scale-[0.98] flex items-center justify-center gap-2">
                        <span>MASUK KE SISTEM</span>
                        <x-auth-icon name="arrow-right-to-bracket" />
                    </button>
                </div>
            </form>
            
            <div class="mt-10 pt-8 border-t border-gray-100">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3 text-gray-400 italic">
                        <x-auth-icon name="shield-halved" class="text-green-500" />
                        <span class="text-xs font-medium">Sistem Terenkripsi & Aman</span>
                    </div>
                    <div class="text-xs font-bold text-gray-300 uppercase tracking-widest">v1.0.0</div>
                </div>
            </div>
        </div>
    </div>

</x-guest-layout>
