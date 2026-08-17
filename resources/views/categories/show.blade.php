@extends('layouts.public')

@section('title', 'Kategori ' . $category->name . ' - TINTAPENA')
@section('canonical', route('categories.show', ['category' => $category->slug]))

@if($category->description)
    @section('meta_description', Str::limit($category->description, 150))
@endif

@section('content')
<div class="bg-white md:bg-transparent min-h-screen">
    <div class="max-w-[1240px] mx-auto px-4 md:px-8 py-8 md:py-12">
        <!-- Breadcrumb -->
        <div class="text-[#8A9099] text-xs font-medium mb-6 flex items-center space-x-2">
            <a href="{{ route('home') }}" class="hover:text-[#1A2BC4] transition">Beranda</a>
            <span>/</span>
            <span class="text-[#17191D]">{{ mb_strtoupper($category->name) }}</span>
        </div>

        <!-- Page Heading -->
        <div class="mb-8 border-b-2 border-[#E1E4E8] pb-4">
            <h1 class="text-3xl md:text-4xl font-bold text-[#17191D] border-l-4 border-[#1A2BC4] pl-4">
                {{ mb_strtoupper($category->name) }}
            </h1>
            @if($category->description)
                <p class="mt-4 text-[#5D6470] text-sm md:text-base leading-relaxed max-w-3xl">
                    {{ $category->description }}
                </p>
            @endif
        </div>

        <!-- CATEGORY ADS -->
        <x-ads.slot position="category_sidebar" />

        @if($articles->isEmpty())
            <!-- Empty State -->
            <div class="py-20 text-center bg-gray-50 rounded-lg border border-gray-100">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l6 6v10a2 2 0 01-2 2z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 2v7h7"></path></svg>
                <h3 class="text-xl font-bold text-[#17191D] mb-2">Belum ada berita pada kategori ini.</h3>
                <p class="text-[#5D6470] text-sm">Nantikan update informasi selanjutnya di kanal {{ $category->name }}.</p>
            </div>
        @else
            <!-- Articles Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                @foreach($articles as $article)
                    <a href="{{ route('articles.show', ['article' => $article->slug]) }}" class="group flex flex-col bg-white rounded-lg overflow-hidden border border-[#E1E4E8] hover:shadow-lg transition duration-300 h-full">
                        <!-- Image -->
                        <div class="aspect-[3/2] w-full bg-gray-200 relative overflow-hidden shrink-0">
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
                        <div class="p-5 flex flex-col grow">
                            <!-- Region -->
                            <div class="flex items-center space-x-2 mb-3 text-[10px] font-bold uppercase tracking-wider text-[#1A2BC4]">
                                @if($article->region)
                                    <span>{{ $article->region->name }}</span>
                                @endif
                            </div>
                            
                            <!-- Title -->
                            <h2 class="text-lg md:text-xl font-bold text-[#17191D] leading-tight mb-3 group-hover:text-[#1A2BC4] transition line-clamp-3">
                                {{ $article->title }}
                            </h2>
                            
                            <!-- Excerpt -->
                            @if($article->excerpt)
                                <p class="text-sm text-[#5D6470] mb-4 line-clamp-2">
                                    {{ $article->excerpt }}
                                </p>
                            @endif
                            
                            <!-- Spacer to push date to bottom -->
                            <div class="mt-auto"></div>
                            
                            <!-- Date -->
                            <div class="text-xs text-[#8A9099] font-medium pt-4 border-t border-gray-100">
                                {{ $article->published_at->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMM YYYY, HH:mm') }} WIB
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $articles->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
