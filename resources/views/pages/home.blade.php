@extends('layouts.public')

@section('title', 'Beranda - ' . \App\Support\SiteSettings::siteName())

@section('content')
<div class="bg-white md:bg-transparent min-h-screen">
    <div class="max-w-[1240px] mx-auto px-4 md:px-8 py-8 md:py-12">
        
        <!-- BREAKING NEWS TICKER -->
        <x-news.breaking-ticker :breakingNews="$breakingNews" />

        <!-- HOMEPAGE TOP ADS -->
        <x-ads.slot position="homepage_top" />

        <!-- HEADLINE AREA -->
        @if($headlineMain || $supportingHeadlines->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <!-- Main Headline -->
            @if($headlineMain)
            <div class="md:col-span-2">
                <a href="{{ route('articles.show', $headlineMain) }}" class="group block relative rounded-xl overflow-hidden aspect-[4/3] md:aspect-[16/9] shadow-md hover:shadow-xl transition">
                    @if($headlineMain->featuredMedia)
                        <img src="{{ Storage::disk($headlineMain->featuredMedia->disk)->url($headlineMain->featuredMedia->path) }}" alt="{{ $headlineMain->featuredMedia->alt_text ?: $headlineMain->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-700">
                    @else
                        <div class="w-full h-full bg-gray-200"></div>
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>
                    <div class="absolute bottom-0 left-0 p-6 w-full">
                        <div class="flex items-center space-x-2 mb-3 text-xs font-bold uppercase tracking-wider text-white">
                            @if($headlineMain->category)
                                <span class="bg-[#1A2BC4] px-2 py-1 rounded">{{ $headlineMain->category->name }}</span>
                            @endif
                            @if($headlineMain->region)
                                <span class="bg-[#E53935] px-2 py-1 rounded">{{ $headlineMain->region->name }}</span>
                            @endif
                        </div>
                        <h1 class="text-2xl md:text-4xl font-bold text-white leading-tight mb-2 group-hover:text-gray-200 transition">
                            {{ $headlineMain->title }}
                        </h1>
                        <div class="text-sm text-gray-300">
                            {{ $headlineMain->published_at->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMM YYYY, HH:mm') }} WIB
                        </div>
                    </div>
                </a>
            </div>
            @endif

            <!-- Supporting Headlines -->
            @if($supportingHeadlines->isNotEmpty())
            <div class="md:col-span-1 flex flex-col gap-6">
                @foreach($supportingHeadlines as $article)
                <a href="{{ route('articles.show', $article) }}" class="group block relative rounded-xl overflow-hidden aspect-[4/3] md:aspect-auto md:flex-1 shadow-md hover:shadow-xl transition">
                    @if($article->featuredMedia)
                        <img src="{{ Storage::disk($article->featuredMedia->disk)->url($article->featuredMedia->path) }}" alt="{{ $article->featuredMedia->alt_text ?: $article->title }}" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-700">
                    @else
                        <div class="absolute inset-0 w-full h-full bg-gray-200"></div>
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                    <div class="absolute bottom-0 left-0 p-4 w-full">
                        <div class="flex items-center space-x-2 mb-2 text-[10px] font-bold uppercase tracking-wider text-white">
                            @if($article->category)
                                <span class="bg-[#1A2BC4] px-2 py-1 rounded">{{ $article->category->name }}</span>
                            @endif
                        </div>
                        <h2 class="text-lg font-bold text-white leading-tight mb-1 group-hover:text-gray-200 transition line-clamp-3">
                            {{ $article->title }}
                        </h2>
                        <div class="text-xs text-gray-300">
                            {{ $article->published_at->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMM YYYY, HH:mm') }} WIB
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
            @endif
        </div>
        @endif

        <!-- TERBARU & TERPOPULER -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
            
            <!-- Berita Terbaru -->
            <div class="lg:col-span-2">
                <div class="flex items-center justify-between border-b-2 border-[#1A2BC4] mb-6 pb-2">
                    <h2 class="text-2xl font-bold text-[#17191D]">BERITA TERBARU</h2>
                    <a href="{{ route('articles.latest') }}" class="text-sm font-medium text-[#1A2BC4] hover:underline">Lihat Semua &rarr;</a>
                </div>
                
                @if($latestArticles->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($latestArticles as $article)
                    <a href="{{ route('articles.show', $article) }}" class="group flex space-x-4">
                        <div class="w-1/3 aspect-[4/3] rounded-lg overflow-hidden shrink-0">
                            @if($article->featuredMedia)
                                <img src="{{ Storage::disk($article->featuredMedia->disk)->url($article->featuredMedia->path) }}" alt="{{ $article->featuredMedia->alt_text ?: $article->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            @else
                                <div class="w-full h-full bg-gray-200"></div>
                            @endif
                        </div>
                        <div class="w-2/3 flex flex-col justify-center">
                            <div class="text-[#1A2BC4] text-[10px] font-bold uppercase mb-1">
                                {{ $article->category?->name }}
                            </div>
                            <h3 class="text-base font-bold text-[#17191D] leading-tight mb-2 group-hover:text-[#1A2BC4] transition line-clamp-2">
                                {{ $article->title }}
                            </h3>
                            <div class="text-xs text-[#8A9099]">
                                {{ $article->published_at->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMM YYYY, HH:mm') }} WIB
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
                @else
                <p class="text-[#8A9099] italic">Belum ada berita terbaru.</p>
                @endif
            </div>

            <!-- Terpopuler -->
            <div class="lg:col-span-1">
                <div class="flex items-center justify-between border-b-2 border-[#E53935] mb-6 pb-2">
                    <h2 class="text-2xl font-bold text-[#17191D]">TERPOPULER</h2>
                    <a href="{{ route('articles.popular') }}" class="text-sm font-medium text-[#E53935] hover:underline">Lihat &rarr;</a>
                </div>

                @if($popularArticles->isNotEmpty())
                <div class="flex flex-col space-y-4">
                    @foreach($popularArticles as $index => $article)
                    <a href="{{ route('articles.show', $article) }}" class="group flex space-x-4 items-center">
                        <div class="text-4xl font-black text-gray-200 group-hover:text-[#E53935] transition shrink-0 w-8 text-center">
                            {{ $index + 1 }}
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-bold text-[#17191D] leading-tight mb-1 group-hover:text-[#1A2BC4] transition line-clamp-2">
                                {{ $article->title }}
                            </h3>
                            <div class="text-xs text-[#8A9099]">
                                {{ $article->published_at->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMM YYYY, HH:mm') }} WIB
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
                @else
                <p class="text-[#8A9099] italic">Belum ada berita terpopuler saat ini.</p>
                @endif
            </div>
        </div>

        <!-- HOMEPAGE MIDDLE ADS -->
        <x-ads.slot position="homepage_middle" />

        <!-- BANGKA BELITUNG -->
        @if($regionalArticles->isNotEmpty())
        <div class="mb-12">
            <div class="border-b-2 border-[#1A2BC4] mb-6 pb-2">
                <h2 class="text-2xl font-bold text-[#17191D]">BANGKA BELITUNG</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach($regionalArticles as $article)
                <a href="{{ route('articles.show', $article) }}" class="group flex flex-col h-full">
                    <div class="aspect-[4/3] rounded-lg overflow-hidden mb-3">
                        @if($article->featuredMedia)
                            <img src="{{ Storage::disk($article->featuredMedia->disk)->url($article->featuredMedia->path) }}" alt="{{ $article->featuredMedia->alt_text ?: $article->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                        @else
                            <div class="w-full h-full bg-gray-200"></div>
                        @endif
                    </div>
                    <div class="text-[#E53935] text-[10px] font-bold uppercase mb-1">
                        {{ $article->region?->name }}
                    </div>
                    <h3 class="text-sm font-bold text-[#17191D] leading-snug group-hover:text-[#1A2BC4] transition line-clamp-3">
                        {{ $article->title }}
                    </h3>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        <!-- POLITIK & EKONOMI -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
            @if($politikArticles->isNotEmpty())
            <div>
                <div class="border-b-2 border-[#1A2BC4] mb-6 pb-2">
                    <h2 class="text-2xl font-bold text-[#17191D]">POLITIK & PEMERINTAHAN</h2>
                </div>
                <div class="flex flex-col gap-4">
                    @foreach($politikArticles as $article)
                    <a href="{{ route('articles.show', $article) }}" class="group flex space-x-4">
                        <div class="w-1/3 aspect-[4/3] rounded-lg overflow-hidden shrink-0">
                            @if($article->featuredMedia)
                                <img src="{{ Storage::disk($article->featuredMedia->disk)->url($article->featuredMedia->path) }}" alt="{{ $article->featuredMedia->alt_text ?: $article->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            @else
                                <div class="w-full h-full bg-gray-200"></div>
                            @endif
                        </div>
                        <div class="w-2/3">
                            <h3 class="text-sm md:text-base font-bold text-[#17191D] leading-tight mb-2 group-hover:text-[#1A2BC4] transition line-clamp-3">
                                {{ $article->title }}
                            </h3>
                            <div class="text-xs text-[#8A9099]">
                                {{ $article->published_at->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMM, YYYY') }}
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            @if($ekonomiArticles->isNotEmpty())
            <div>
                <div class="border-b-2 border-[#1A2BC4] mb-6 pb-2">
                    <h2 class="text-2xl font-bold text-[#17191D]">EKONOMI</h2>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    @foreach($ekonomiArticles as $article)
                    <a href="{{ route('articles.show', $article) }}" class="group flex flex-col h-full">
                        <div class="aspect-[4/3] rounded-lg overflow-hidden mb-3">
                            @if($article->featuredMedia)
                                <img src="{{ Storage::disk($article->featuredMedia->disk)->url($article->featuredMedia->path) }}" alt="{{ $article->featuredMedia->alt_text ?: $article->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            @else
                                <div class="w-full h-full bg-gray-200"></div>
                            @endif
                        </div>
                        <h3 class="text-sm font-bold text-[#17191D] leading-snug group-hover:text-[#1A2BC4] transition line-clamp-3">
                            {{ $article->title }}
                        </h3>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- HUKUM & PARIWISATA -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
            @if($hukumArticles->isNotEmpty())
            <div>
                <div class="border-b-2 border-[#1A2BC4] mb-6 pb-2">
                    <h2 class="text-2xl font-bold text-[#17191D]">HUKUM & KRIMINAL</h2>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    @foreach($hukumArticles as $article)
                    <a href="{{ route('articles.show', $article) }}" class="group flex flex-col h-full">
                        <div class="aspect-[4/3] rounded-lg overflow-hidden mb-3">
                            @if($article->featuredMedia)
                                <img src="{{ Storage::disk($article->featuredMedia->disk)->url($article->featuredMedia->path) }}" alt="{{ $article->featuredMedia->alt_text ?: $article->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            @else
                                <div class="w-full h-full bg-gray-200"></div>
                            @endif
                        </div>
                        <h3 class="text-sm font-bold text-[#17191D] leading-snug group-hover:text-[#1A2BC4] transition line-clamp-3">
                            {{ $article->title }}
                        </h3>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            @if($pariwisataArticles->isNotEmpty())
            <div>
                <div class="border-b-2 border-[#1A2BC4] mb-6 pb-2">
                    <h2 class="text-2xl font-bold text-[#17191D]">PARIWISATA</h2>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    @foreach($pariwisataArticles as $article)
                    <a href="{{ route('articles.show', $article) }}" class="group flex flex-col h-full">
                        <div class="aspect-[4/3] rounded-lg overflow-hidden mb-3">
                            @if($article->featuredMedia)
                                <img src="{{ Storage::disk($article->featuredMedia->disk)->url($article->featuredMedia->path) }}" alt="{{ $article->featuredMedia->alt_text ?: $article->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            @else
                                <div class="w-full h-full bg-gray-200"></div>
                            @endif
                        </div>
                        <h3 class="text-sm font-bold text-[#17191D] leading-snug group-hover:text-[#1A2BC4] transition line-clamp-3">
                            {{ $article->title }}
                        </h3>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- PILIHAN REDAKSI -->
        @if($editorPicks->isNotEmpty())
        <div class="mb-12 bg-gray-50 p-6 rounded-xl border border-gray-100">
            <div class="border-b-2 border-[#E53935] mb-6 pb-2">
                <h2 class="text-2xl font-bold text-[#17191D]">PILIHAN REDAKSI</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                @foreach($editorPicks as $article)
                <a href="{{ route('articles.show', $article) }}" class="group flex flex-col bg-white h-full rounded-lg shadow-sm hover:shadow-md transition overflow-hidden">
                    <div class="aspect-[4/3] overflow-hidden">
                        @if($article->featuredMedia)
                            <img src="{{ Storage::disk($article->featuredMedia->disk)->url($article->featuredMedia->path) }}" alt="{{ $article->featuredMedia->alt_text ?: $article->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                        @else
                            <div class="w-full h-full bg-gray-200"></div>
                        @endif
                    </div>
                    <div class="p-4 flex flex-col flex-grow">
                        <div class="text-[#1A2BC4] text-[10px] font-bold uppercase mb-2">
                            {{ $article->category?->name }}
                        </div>
                        <h3 class="text-sm font-bold text-[#17191D] leading-snug group-hover:text-[#E53935] transition line-clamp-3">
                            {{ $article->title }}
                        </h3>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
