@extends('layouts.public')

@section('title', 'Pencarian - TINTAPENA')
@section('canonical', route('search'))

@section('content')
<div class="bg-white md:bg-transparent min-h-screen">
    <div class="max-w-[1240px] mx-auto px-4 md:px-8 py-8 md:py-12">
        <!-- Breadcrumb -->
        <div class="text-[#8A9099] text-xs font-medium mb-6 flex items-center space-x-2">
            <a href="{{ route('home') }}" class="hover:text-[#1A2BC4] transition">Beranda</a>
            <span>/</span>
            <span class="text-[#17191D]">Pencarian</span>
        </div>

        <!-- Page Heading -->
        <div class="mb-6">
            <h1 class="text-3xl md:text-5xl font-bold text-[#17191D] tracking-tight">
                CARI BERITA
            </h1>
        </div>

        <!-- Search Form -->
        <form action="{{ route('search') }}" method="GET" class="mb-6">
            @if($filter !== 'semua')
                <input type="hidden" name="filter" value="{{ $filter }}">
            @endif
            <div class="flex items-center w-full max-w-2xl border border-[#E1E4E8] rounded bg-white overflow-hidden focus-within:border-[#1A2BC4] focus-within:ring-1 focus-within:ring-[#1A2BC4] transition">
                <input type="text"
                       name="q"
                       value="{{ $q }}"
                       class="w-full px-4 py-3 outline-none text-[#17191D] placeholder-gray-400"
                       placeholder="Cari berita..."
                       required>
                <button type="submit" class="bg-[#1A2BC4] hover:bg-blue-800 text-white px-6 py-3 font-semibold transition">
                    Cari
                </button>
            </div>
        </form>

        @if($hasRunSearch)
            <!-- Search Stats & Filters -->
            <div class="mb-8">
                <p class="text-[#5D6470] mb-4 text-lg">
                    {{ $articles->total() }} hasil untuk "{{ $q }}"
                </p>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('search', ['q' => $q, 'filter' => 'semua']) }}"
                       class="px-5 py-2 rounded-full border text-sm font-medium transition {{ $filter === 'semua' ? 'bg-[#1A2BC4] text-white border-[#1A2BC4]' : 'bg-white text-[#17191D] border-[#E1E4E8] hover:border-[#1A2BC4] hover:text-[#1A2BC4]' }}">
                        Semua
                    </a>
                    <a href="{{ route('search', ['q' => $q, 'filter' => 'berita']) }}"
                       class="px-5 py-2 rounded-full border text-sm font-medium transition {{ $filter === 'berita' ? 'bg-[#1A2BC4] text-white border-[#1A2BC4]' : 'bg-white text-[#17191D] border-[#E1E4E8] hover:border-[#1A2BC4] hover:text-[#1A2BC4]' }}">
                        Berita
                    </a>
                    <a href="{{ route('search', ['q' => $q, 'filter' => 'opini']) }}"
                       class="px-5 py-2 rounded-full border text-sm font-medium transition {{ $filter === 'opini' ? 'bg-[#1A2BC4] text-white border-[#1A2BC4]' : 'bg-white text-[#17191D] border-[#E1E4E8] hover:border-[#1A2BC4] hover:text-[#1A2BC4]' }}">
                        Opini
                    </a>
                </div>
            </div>

            <!-- Content Area (Left main, no sidebar implemented yet as per rules) -->
            <div class="w-full lg:w-2/3">
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-[#17191D] border-l-4 border-[#1A2BC4] pl-3 uppercase">HASIL PENCARIAN</h2>
                </div>

                @if($articles->isEmpty())
                    <!-- Empty State -->
                    <div class="py-16 px-6 text-center bg-gray-50 rounded-lg border border-gray-100 mt-6">
                        <h3 class="text-2xl font-bold text-[#17191D] mb-3">Berita tidak ditemukan</h3>
                        <p class="text-[#5D6470] text-base">Coba gunakan kata kunci yang lebih umum atau periksa kembali ejaan pencarian.</p>
                    </div>
                @else
                    <!-- Results List -->
                    <div class="flex flex-col space-y-6">
                        @foreach($articles as $article)
                            <div class="border-b border-[#E1E4E8] pb-6 last:border-0 last:pb-0">
                                <a href="{{ route('articles.show', ['article' => $article->slug]) }}" class="group flex flex-col sm:flex-row gap-6">
                                    <!-- Image -->
                                    <div class="sm:w-1/3 aspect-[3/2] sm:aspect-square md:aspect-[4/3] bg-gray-200 relative overflow-hidden shrink-0 rounded-lg">
                                        @if($article->featuredMedia)
                                            <img src="{{ Storage::disk($article->featuredMedia->disk)->url($article->featuredMedia->path) }}"
                                                 alt="{{ $article->featuredMedia->alt_text ?: $article->title }}"
                                                 loading="lazy"
                                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-gray-400 bg-gray-100">
                                                <svg class="w-10 h-10 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Content -->
                                    <div class="sm:w-2/3 flex flex-col justify-center">
                                        <div class="flex items-center space-x-2 mb-2 text-[10px] font-bold uppercase tracking-wider text-[#1A2BC4]">
                                            @if($article->category)
                                                <span>{{ $article->category->name }}</span>
                                            @endif
                                            @if($article->region)
                                                <span>&bull;</span>
                                                <span class="text-[#8A9099]">{{ $article->region->name }}</span>
                                            @endif
                                            <span>&bull;</span>
                                            <span class="text-[#8A9099] font-normal tracking-normal">{{ $article->published_at->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMM YYYY, HH:mm') }} WIB</span>
                                        </div>

                                        <h3 class="text-xl md:text-2xl font-bold text-[#17191D] leading-tight mb-2 group-hover:text-[#1A2BC4] transition">
                                            {{ $article->title }}
                                        </h3>

                                        @if($article->excerpt)
                                            <p class="text-[15px] text-[#5D6470] leading-relaxed line-clamp-2">
                                                {{ $article->excerpt }}
                                            </p>
                                        @endif
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-10">
                        {{ $articles->links() }}
                    </div>
                @endif
            </div>
        @else
            <!-- Initial State -->
            <div class="w-full lg:w-2/3 mt-8">
                <div class="py-20 text-center bg-gray-50 rounded-lg border border-gray-100">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <h3 class="text-xl font-bold text-[#17191D] mb-2">Pencarian Berita</h3>
                    <p class="text-[#5D6470] text-sm">Masukkan kata kunci untuk mencari berita.</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
