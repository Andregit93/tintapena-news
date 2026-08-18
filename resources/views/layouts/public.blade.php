<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'TINTAPENA - Menulis Berdasarkan Fakta')</title>
    <meta name="description" content="@yield('meta_description', 'TINTAPENA adalah portal berita independen yang menyajikan informasi terkini dan terpercaya.')">
    <link rel="canonical" href="@yield('canonical', url()->current())">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', Inter, sans-serif;
            color: #17191D;
        }
    </style>
</head>
<body class="bg-[#F6F7F9] min-h-screen flex flex-col">

    <!-- Header -->
    <header class="bg-[#FFFFFF] border-b border-[#E1E4E8] sticky top-0 z-50" x-data="{ mobileMenuOpen: false }">
        <!-- Top bar -->
        <div class="bg-[#111833] text-white text-xs py-2 hidden md:block">
            <div class="max-w-[1240px] mx-auto px-4 md:px-8 flex justify-between items-center">
                <div class="flex space-x-6">
                    <span>{{ now()->timezone('Asia/Jakarta')->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</span>
                    <span>&bull;</span>
                    <span>Bangka Belitung</span>
                </div>
                <div class="flex space-x-4">
                    <span>Tentang Kami</span>
                    <span>Redaksi</span>
                    <a href="{{ route('contact.show') }}" class="hover:text-gray-300 transition-colors">Kontak</a>
                </div>
            </div>
        </div>

        <!-- Main Logo Area -->
        <div class="max-w-[1240px] mx-auto px-4 md:px-8 py-4 md:py-6 flex justify-between items-center relative">

            <div class="flex flex-col items-start flex-1 md:flex-none">
                <a href="{{ route('home') }}" class="text-[#1A2BC4] font-bold text-3xl tracking-tight">TINTAPENA</a>
                <span class="text-[#5D6470] text-[10px] uppercase font-bold tracking-widest mt-1 hidden md:block">Menulis Berdasarkan Fakta</span>
            </div>

            <!-- Hamburger Button (Mobile) -->
            <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-[#111833] focus:outline-none p-2 -mr-2 w-11 h-11 flex items-center justify-center">
                <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                <svg x-show="mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>

        </div>

        <!-- Navigation (Desktop) -->
        <nav class="hidden md:block max-w-[1240px] mx-auto px-4 md:px-8 pb-4">
            <ul class="flex flex-wrap space-x-6 text-sm font-semibold text-[#17191D] items-center gap-y-2">
                <li><a href="{{ route('home') }}" class="text-[#1A2BC4]">Beranda</a></li>
                <li x-data="{ open: false }" class="relative" @mouseenter="open = true" @mouseleave="open = false" @click.outside="open = false" @keydown.escape.window="open = false">
                    <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="true" class="flex items-center gap-1 hover:text-[#1A2BC4]">
                        Babel
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <ul x-show="open"
                        x-transition
                        style="display: none;"
                        class="absolute top-full left-0 mt-2 bg-white border border-gray-100 shadow-lg rounded py-2 min-w-[180px] flex flex-col z-50">
                        <li><a href="{{ route('regions.show', 'pangkalpinang') }}" class="block px-4 py-2 hover:text-[#1A2BC4]">Pangkalpinang</a></li>
                        <li><a href="{{ route('regions.show', 'bangka') }}" class="block px-4 py-2 hover:text-[#1A2BC4]">Bangka</a></li>
                        <li><a href="{{ route('regions.show', 'bangka-barat') }}" class="block px-4 py-2 hover:text-[#1A2BC4]">Bangka Barat</a></li>
                        <li><a href="{{ route('regions.show', 'bangka-tengah') }}" class="block px-4 py-2 hover:text-[#1A2BC4]">Bangka Tengah</a></li>
                        <li><a href="{{ route('regions.show', 'bangka-selatan') }}" class="block px-4 py-2 hover:text-[#1A2BC4]">Bangka Selatan</a></li>
                        <li><a href="{{ route('regions.show', 'belitung') }}" class="block px-4 py-2 hover:text-[#1A2BC4]">Belitung</a></li>
                        <li><a href="{{ route('regions.show', 'belitung-timur') }}" class="block px-4 py-2 hover:text-[#1A2BC4]">Belitung Timur</a></li>
                    </ul>
                </li>
                <li><a href="{{ route('categories.show', 'politik') }}" class="hover:text-[#1A2BC4]">Politik</a></li>
                <li><a href="{{ route('categories.show', 'pemerintahan') }}" class="hover:text-[#1A2BC4]">Pemerintahan</a></li>
                <li><a href="{{ route('categories.show', 'ekonomi') }}" class="hover:text-[#1A2BC4]">Ekonomi</a></li>
                <li><a href="{{ route('categories.show', 'hukum-kriminal') }}" class="hover:text-[#1A2BC4]">Hukum & Kriminal</a></li>
                <li><a href="{{ route('categories.show', 'pendidikan') }}" class="hover:text-[#1A2BC4]">Pendidikan</a></li>
                <li><a href="{{ route('categories.show', 'kesehatan') }}" class="hover:text-[#1A2BC4]">Kesehatan</a></li>
                <li><a href="{{ route('categories.show', 'pariwisata') }}" class="hover:text-[#1A2BC4]">Pariwisata</a></li>
                <li><a href="{{ route('categories.show', 'olahraga') }}" class="hover:text-[#1A2BC4]">Olahraga</a></li>
                <li><a href="{{ route('categories.show', 'opini') }}" class="hover:text-[#1A2BC4]">Opini</a></li>
            </ul>
        </nav>

        <!-- Navigation (Mobile) -->
        <div x-show="mobileMenuOpen"
             x-transition
             style="display: none;"
             class="md:hidden absolute top-full left-0 w-full bg-white border-b border-[#E1E4E8] shadow-lg max-h-[calc(100vh-80px)] overflow-y-auto">
            <div class="px-4 py-4 space-y-4">
                <!-- Main Links -->
                <ul class="flex flex-col text-base font-bold text-[#17191D]">
                    <li><a href="{{ route('home') }}" class="text-[#1A2BC4] flex min-h-11 items-center">Beranda</a></li>
                    <li><a href="{{ route('categories.show', 'politik') }}" class="flex min-h-11 items-center">Politik</a></li>
                    <li><a href="{{ route('categories.show', 'pemerintahan') }}" class="flex min-h-11 items-center">Pemerintahan</a></li>
                    <li><a href="{{ route('categories.show', 'ekonomi') }}" class="flex min-h-11 items-center">Ekonomi</a></li>
                    <li><a href="{{ route('categories.show', 'hukum-kriminal') }}" class="flex min-h-11 items-center">Hukum & Kriminal</a></li>
                    <li><a href="{{ route('categories.show', 'pendidikan') }}" class="flex min-h-11 items-center">Pendidikan</a></li>
                    <li><a href="{{ route('categories.show', 'kesehatan') }}" class="flex min-h-11 items-center">Kesehatan</a></li>
                    <li><a href="{{ route('categories.show', 'pariwisata') }}" class="flex min-h-11 items-center">Pariwisata</a></li>
                    <li><a href="{{ route('categories.show', 'olahraga') }}" class="flex min-h-11 items-center">Olahraga</a></li>
                    <li><a href="{{ route('categories.show', 'opini') }}" class="flex min-h-11 items-center">Opini</a></li>
                </ul>

                <!-- Regions -->
                <div class="pt-4 border-t border-gray-100">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Wilayah / Babel</h3>
                    <ul class="flex flex-col text-sm font-semibold text-[#5D6470]">
                        <li><a href="{{ route('regions.show', 'pangkalpinang') }}" class="flex min-h-11 items-center">Pangkalpinang</a></li>
                        <li><a href="{{ route('regions.show', 'bangka') }}" class="flex min-h-11 items-center">Bangka</a></li>
                        <li><a href="{{ route('regions.show', 'bangka-barat') }}" class="flex min-h-11 items-center">Bangka Barat</a></li>
                        <li><a href="{{ route('regions.show', 'bangka-tengah') }}" class="flex min-h-11 items-center">Bangka Tengah</a></li>
                        <li><a href="{{ route('regions.show', 'bangka-selatan') }}" class="flex min-h-11 items-center">Bangka Selatan</a></li>
                        <li><a href="{{ route('regions.show', 'belitung') }}" class="flex min-h-11 items-center">Belitung</a></li>
                        <li><a href="{{ route('regions.show', 'belitung-timur') }}" class="flex min-h-11 items-center">Belitung Timur</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow w-full max-w-[1240px] mx-auto px-0 md:px-8 py-0 md:py-8 bg-white md:bg-transparent">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-[#111833] text-white pt-12 pb-6 mt-12">
        <div class="max-w-[1240px] mx-auto px-4 md:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
                <div class="col-span-1 md:col-span-1">
                    <h2 class="text-2xl font-bold mb-2">TINTAPENA</h2>
                    <p class="text-sm text-gray-400 mb-6 font-semibold uppercase tracking-wider">Menulis Berdasarkan Fakta</p>
                    <p class="text-xs text-gray-400 leading-relaxed">Portal berita independen yang menyajikan informasi terkini dan terpercaya dari Bangka Belitung dan sekitarnya.</p>
                </div>

                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider mb-4 border-b border-gray-700 pb-2 flex justify-between items-center">Kategori <span class="md:hidden text-gray-500">+</span></h3>
                    <ul class="space-y-2 text-sm text-gray-400 hidden md:block">
                        <li>Politik</li>
                        <li>Pemerintahan</li>
                        <li>Ekonomi</li>
                        <li>Hukum & Kriminal</li>
                        <li>Pendidikan</li>
                        <li>Pariwisata</li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider mb-4 border-b border-gray-700 pb-2 flex justify-between items-center">Wilayah <span class="md:hidden text-gray-500">+</span></h3>
                    <ul class="space-y-2 text-sm text-gray-400 hidden md:block">
                        <li>Pangkalpinang</li>
                        <li>Bangka</li>
                        <li>Bangka Barat</li>
                        <li>Bangka Tengah</li>
                        <li>Bangka Selatan</li>
                        <li>Belitung</li>
                        <li>Belitung Timur</li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider mb-4 border-b border-gray-700 pb-2 flex justify-between items-center">Informasi <span class="md:hidden text-gray-500">+</span></h3>
                    <ul class="space-y-2 text-sm text-gray-400 hidden md:block">
                        <li>Tentang Kami</li>
                        <li>Redaksi</li>
                        <li><a href="{{ route('contact.show') }}" class="hover:text-white transition-colors">Kontak</a></li>
                        <li>Pedoman Media Siber</li>
                        <li>Privacy Policy</li>
                        <li>Disclaimer</li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-6 flex flex-col md:flex-row justify-between items-center text-xs text-gray-500 text-center md:text-left">
                <p>&copy; {{ date('Y') }} TINTAPENA. Semua Hak Cipta Dilindungi.</p>
                <div class="flex space-x-4 mt-4 md:mt-0">
                    <span>Syarat & Ketentuan</span>
                    <span>Kebijakan Privasi</span>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
