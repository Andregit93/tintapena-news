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
    <header class="bg-[#FFFFFF] border-b border-[#E1E4E8] sticky top-0 z-50">
        <!-- Top bar -->
        <div class="bg-[#111833] text-white text-xs py-2 hidden md:block">
            <div class="max-w-[1240px] mx-auto px-4 md:px-8 flex justify-between items-center">
                <div class="flex space-x-6">
                    <span>{{ now()->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</span>
                    <span>&bull;</span>
                    <span>Bangka Belitung</span>
                </div>
                <div class="flex space-x-4">
                    <span>Tentang Kami</span>
                    <span>Redaksi</span>
                    <span>Kontak</span>
                </div>
            </div>
        </div>
        
        <!-- Main Logo Area -->
        <div class="max-w-[1240px] mx-auto px-4 md:px-8 py-4 md:py-6 flex justify-between items-center">
            <!-- Mobile Menu Toggle -->
            <button class="md:hidden text-[#17191D] w-11 h-11 flex items-center justify-center -ml-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            
            <div class="flex flex-col md:items-start items-center flex-1 md:flex-none">
                <a href="{{ route('home') }}" class="text-[#1A2BC4] font-bold text-3xl tracking-tight">TINTAPENA</a>
                <span class="text-[#5D6470] text-[10px] uppercase font-bold tracking-widest mt-1 hidden md:block">Menulis Berdasarkan Fakta</span>
            </div>
            
            <div class="hidden md:flex items-center space-x-4">
                <div class="w-[728px] h-[90px] bg-[#F6F7F9] border border-[#E1E4E8] flex items-center justify-center text-[#8A9099] text-sm">
                    IKLAN 728 x 90
                </div>
            </div>
            
            <button class="text-[#17191D] w-11 h-11 flex items-center justify-center -mr-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </button>
        </div>
        
        <!-- Navigation -->
        <nav class="hidden md:block max-w-[1240px] mx-auto px-4 md:px-8 pb-4 overflow-x-auto whitespace-nowrap">
            <ul class="flex space-x-6 text-sm font-semibold text-[#17191D]">
                <li class="text-[#1A2BC4]">Beranda</li>
                <li>Babel</li>
                <li>Politik</li>
                <li>Pemerintahan</li>
                <li>Ekonomi</li>
                <li>Hukum & Kriminal</li>
                <li>Pendidikan</li>
                <li>Kesehatan</li>
                <li>Pariwisata</li>
                <li>Olahraga</li>
                <li>Opini</li>
            </ul>
        </nav>
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
                        <li>Kontak</li>
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
