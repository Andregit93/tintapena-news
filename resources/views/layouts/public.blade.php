<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', \App\Support\SiteSettings::siteName() . ' - ' . \App\Support\SiteSettings::tagline())</title>
    <meta name="description" content="@yield('meta_description', \App\Support\SiteSettings::siteName() . ' adalah portal berita independen yang menyajikan informasi terkini dan terpercaya.')">
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
                <a href="{{ route('home') }}" class="text-[#1A2BC4] font-bold text-3xl tracking-tight">{{ \App\Support\SiteSettings::siteName() }}</a>
                <span class="text-[#5D6470] text-[10px] uppercase font-bold tracking-widest mt-1 hidden md:block">{{ \App\Support\SiteSettings::tagline() }}</span>
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
                    <h2 class="text-2xl font-bold mb-2">{{ \App\Support\SiteSettings::siteName() }}</h2>
                    <p class="text-sm text-gray-400 mb-6 font-semibold uppercase tracking-wider">{{ \App\Support\SiteSettings::tagline() }}</p>
                    <p class="text-xs text-gray-400 leading-relaxed">Portal berita independen yang menyajikan informasi terkini dan terpercaya dari Bangka Belitung dan sekitarnya.</p>

                    @if (\App\Support\SiteSettings::instagram() || \App\Support\SiteSettings::facebook())
                        <div class="flex space-x-4 mt-6">
                            @if ($instagram = \App\Support\SiteSettings::instagram())
                                <a href="{{ $instagram }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-white transition-colors" aria-label="Instagram">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            @endif
                            @if ($facebook = \App\Support\SiteSettings::facebook())
                                <a href="{{ $facebook }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-white transition-colors" aria-label="Facebook">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            @endif
                        </div>
                    @endif
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
                <p>&copy; {{ date('Y') }} {{ \App\Support\SiteSettings::siteName() }}. Semua Hak Cipta Dilindungi.</p>
                <div class="flex space-x-4 mt-4 md:mt-0">
                    <span>Syarat & Ketentuan</span>
                    <span>Kebijakan Privasi</span>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
