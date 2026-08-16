@extends('layouts.public')

@section('title', 'Berita Terpopuler - TINTAPENA')
@section('canonical', route('articles.popular'))

@section('content')
<div class="bg-white md:bg-transparent min-h-screen">
    <div class="max-w-[1240px] mx-auto px-4 md:px-8 py-8 md:py-12">
        <!-- Breadcrumb -->
        <div class="text-[#8A9099] text-xs font-medium mb-6 flex items-center space-x-2">
            <a href="{{ route('home') }}" class="hover:text-[#1A2BC4] transition">Beranda</a>
            <span>/</span>
            <span class="text-[#17191D]">Berita Terpopuler</span>
        </div>

        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 border-b-2 border-[#E1E4E8] pb-4 gap-4">
            <!-- Page Heading -->
            <h1 class="text-3xl md:text-4xl font-bold text-[#17191D] border-l-4 border-[#1A2BC4] pl-4">
                BERITA TERPOPULER
            </h1>
            
            <!-- Period Controls -->
            <div class="flex items-center space-x-2">
                <a href="{{ route('articles.popular', ['periode' => '24jam']) }}" 
                   class="px-4 py-2 text-sm font-bold rounded-full transition {{ $currentPeriod === '24jam' ? 'bg-[#1A2BC4] text-white' : 'bg-gray-100 text-[#5D6470] hover:bg-gray-200' }}">
                    24 JAM
                </a>
                <a href="{{ route('articles.popular', ['periode' => '7hari']) }}" 
                   class="px-4 py-2 text-sm font-bold rounded-full transition {{ $currentPeriod === '7hari' ? 'bg-[#1A2BC4] text-white' : 'bg-gray-100 text-[#5D6470] hover:bg-gray-200' }}">
                    7 HARI
                </a>
            </div>
        </div>

        @if($articles->isEmpty())
            <!-- Empty State -->
            <div class="py-20 text-center bg-gray-50 rounded-lg border border-gray-100">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                <h3 class="text-xl font-bold text-[#17191D] mb-2">Belum ada berita terpopuler untuk periode ini.</h3>
                <p class="text-[#5D6470] text-sm">Kembali beberapa saat lagi untuk melihat artikel yang paling banyak dibaca.</p>
            </div>
        @else
            <!-- Articles List -->
            <div class="flex flex-col gap-6 mb-12">
                @php
                    // Determine the starting rank for this page
                    $startRank = $articles->firstItem() ?? 1;
                @endphp
                
                @foreach($articles as $index => $article)
                    @php
                        $rank = $startRank + $index;
                    @endphp
                    
                    <a href="{{ route('articles.show', ['article' => $article->slug]) }}" class="group flex flex-col sm:flex-row bg-white rounded-lg overflow-hidden border border-[#E1E4E8] hover:shadow-lg transition duration-300">
                        
                        <!-- Rank Number & Image -->
                        <div class="relative shrink-0 sm:w-1/3 md:w-1/4 aspect-[3/2] sm:aspect-auto bg-gray-200 overflow-hidden">
                            <!-- Rank Badge -->
                            <div class="absolute top-0 left-0 z-10 w-12 h-12 bg-[#1A2BC4] text-white flex items-center justify-center text-2xl font-black rounded-br-lg shadow-md">
                                {{ $rank }}
                            </div>
                            
                            @if($article->featuredMedia)
                                <img src="{{ Storage::disk($article->featuredMedia->disk)->url($article->featuredMedia->path) }}" 
                                     alt="{{ $article->featuredMedia->alt_text ?: $article->title }}" 
                                     loading="lazy"
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-500 min-h-full">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400 bg-gray-100 min-h-[200px]">
                                    <svg class="w-10 h-10 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Content -->
                        <div class="p-5 flex flex-col grow justify-center">
                            <!-- Category & Region -->
                            <div class="flex items-center space-x-2 mb-3 text-[10px] font-bold uppercase tracking-wider text-[#1A2BC4]">
                                @if($article->category)
                                    <span>{{ $article->category->name }}</span>
                                @endif
                                @if($article->region)
                                    <span>&bull;</span>
                                    <span>{{ $article->region->name }}</span>
                                @endif
                            </div>
                            
                            <!-- Title -->
                            <h2 class="text-xl md:text-2xl font-bold text-[#17191D] leading-tight mb-4 group-hover:text-[#1A2BC4] transition">
                                {{ $article->title }}
                            </h2>
                            
                            <!-- Excerpt -->
                            @if($article->excerpt)
                                <p class="text-sm text-[#5D6470] mb-4 line-clamp-2">
                                    {{ $article->excerpt }}
                                </p>
                            @endif
                            
                            <!-- Date & Views -->
                            <div class="text-xs text-[#8A9099] font-medium flex items-center justify-between mt-auto pt-4 border-t border-gray-100">
                                <span>{{ $article->published_at->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMM YYYY, HH:mm') }} WIB</span>
                                @if(isset($article->period_views))
                                    <span class="flex items-center gap-1 text-[#1A2BC4]">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        {{ number_format($article->period_views, 0, ',', '.') }}
                                    </span>
                                @endif
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
