@extends('layouts.public')

@php
    $seoTitle = blank($article->seo_title) ? $article->title : $article->seo_title;
    
    $metaDescription = $article->meta_description;
    if (blank($metaDescription) && filled($article->excerpt)) {
        $metaDescription = $article->excerpt;
    }
    if (blank($metaDescription)) {
        $stripped = strip_tags($article->content);
        $normalized = preg_replace('/\s+/', ' ', $stripped);
        $metaDescription = \Illuminate\Support\Str::limit(trim($normalized), 160);
    }
    
    $canonicalUrl = route('articles.show', ['article' => $article->slug]);
@endphp

@section('title', $seoTitle)
@section('meta_description', $metaDescription)
@section('canonical', $canonicalUrl)

@section('content')
<div class="bg-white md:bg-transparent">
    <div class="max-w-[800px] mx-auto pt-4 md:pt-0 px-4 md:px-0">
        <!-- Breadcrumb -->
        <div class="text-[#8A9099] text-xs font-medium mb-4 flex items-center space-x-2">
            <span>Beranda</span>
            <span>/</span>
            @if($article->category)
                <span>{{ $article->category->name }}</span>
            @endif
            @if($article->region)
                <span>/</span>
                <span>{{ $article->region->name }}</span>
            @endif
        </div>

        <!-- Category/Region tags -->
        <div class="flex items-center space-x-2 mb-4 text-[10px] md:text-xs font-bold uppercase tracking-wider text-[#1A2BC4]">
            @if($article->category)
                <span>{{ $article->category->name }}</span>
            @endif
            @if($article->region)
                <span>&bull;</span>
                <span>{{ mb_strtoupper($article->region->name) }}</span>
            @endif
        </div>

        <!-- Title -->
        <h1 class="text-2xl md:text-[40px] leading-tight md:leading-[1.2] font-bold text-[#17191D] mb-4">
            {{ $article->title }}
        </h1>

        <!-- Subtitle -->
        @if($article->subtitle)
            <h2 class="text-[#5D6470] text-lg md:text-xl font-medium mb-6 leading-relaxed">
                {{ $article->subtitle }}
            </h2>
        @endif

        <!-- Author & Time -->
        <div class="flex flex-col md:flex-row md:items-center justify-between text-xs text-[#5D6470] mb-8 border-b border-[#E1E4E8] pb-4">
            <div class="mb-4 md:mb-0">
                <span>Oleh {{ $article->author->name ?? 'Redaksi TINTAPENA' }}</span>
                <span class="mx-1">&bull;</span>
                <span>{{ $article->published_at->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMMM YYYY, HH:mm') }} WIB</span>
                @if($article->updated_at && $article->updated_at->gt($article->published_at))
                    <span class="mx-1">&bull;</span>
                    <span>Diperbarui {{ $article->updated_at->timezone('Asia/Jakarta')->locale('id')->isoFormat('HH:mm') }} WIB</span>
                @endif
            </div>
        </div>
        
        <!-- Featured Image -->
        @if($article->featuredMedia)
            <div class="mb-8 relative">
                <img src="{{ Storage::disk($article->featuredMedia->disk)->url($article->featuredMedia->path) }}" 
                     alt="{{ $article->featuredMedia->alt_text ?? $article->title }}" 
                     class="w-full h-auto aspect-[3/2] object-cover bg-gray-100">
                     
                @if($article->featuredMedia->caption || $article->featuredMedia->photo_credit)
                    <div class="mt-2 text-[10px] md:text-xs text-[#8A9099] flex flex-col md:flex-row justify-between">
                        <span>{{ $article->featuredMedia->caption }}</span>
                        @if($article->featuredMedia->photo_credit)
                            <span class="mt-1 md:mt-0">Foto: {{ $article->featuredMedia->photo_credit }}</span>
                        @endif
                    </div>
                @endif
            </div>
        @endif
        
        <!-- Article Body -->
        <div class="article-content">
            {!! str($article->content)->sanitizeHtml() !!}
        </div>
        
        <!-- Tags -->
        @if($article->tags->isNotEmpty())
            <div class="mb-8">
                <div class="text-xs font-bold uppercase tracking-wider mb-4 text-[#17191D]">Tag</div>
                <div class="flex flex-wrap gap-2">
                    @foreach($article->tags as $tag)
                        <span class="px-4 py-2 border border-[#E1E4E8] rounded-full text-sm text-[#5D6470] bg-white">
                            #{{ $tag->name }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Social Share -->
        <div class="mb-12 border-t border-b border-[#E1E4E8] py-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="text-sm font-bold text-[#17191D]">Bagikan Artikel:</div>
            <div class="flex items-center gap-3">
                <a href="https://api.whatsapp.com/send?text={{ rawurlencode($article->title . ' ' . $canonicalUrl) }}" 
                   target="_blank" rel="noopener noreferrer"
                   class="px-4 py-2 bg-[#25D366] text-white rounded font-medium text-sm hover:bg-[#20bd5a] transition">
                    WhatsApp
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($canonicalUrl) }}" 
                   target="_blank" rel="noopener noreferrer"
                   class="px-4 py-2 bg-[#1877F2] text-white rounded font-medium text-sm hover:bg-[#166fe5] transition">
                    Facebook
                </a>
                <a href="https://twitter.com/intent/tweet?text={{ rawurlencode($article->title) }}&url={{ urlencode($canonicalUrl) }}" 
                   target="_blank" rel="noopener noreferrer"
                   class="px-4 py-2 bg-[#1DA1F2] text-white rounded font-medium text-sm hover:bg-[#1a91da] transition">
                    X
                </a>
                <button 
                    x-data="{ copied: false, url: '{{ $canonicalUrl }}' }"
                    @click="
                        if (navigator.clipboard && window.isSecureContext) {
                            navigator.clipboard.writeText(url).then(() => {
                                copied = true;
                                setTimeout(() => copied = false, 2000);
                            });
                        } else {
                            const textArea = document.createElement('textarea');
                            textArea.value = url;
                            textArea.style.position = 'absolute';
                            textArea.style.left = '-999999px';
                            document.body.prepend(textArea);
                            textArea.select();
                            try {
                                document.execCommand('copy');
                                copied = true;
                                setTimeout(() => copied = false, 2000);
                            } catch (error) {
                                console.error(error);
                            } finally {
                                textArea.remove();
                            }
                        }
                    "
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded font-medium text-sm hover:bg-gray-200 transition">
                    <span x-text="copied ? 'Tersalin' : 'Salin Tautan'"></span>
                </button>
            </div>
        </div>

        <!-- Related News -->
        @if(isset($relatedArticles) && $relatedArticles->isNotEmpty())
            <div class="mb-12">
                <h3 class="text-xl font-bold text-[#17191D] mb-6 border-l-4 border-[#1A2BC4] pl-3">
                    BERITA TERKAIT
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    @foreach($relatedArticles as $related)
                        <a href="{{ route('articles.show', ['article' => $related->slug]) }}" class="group block bg-white rounded-lg overflow-hidden border border-[#E1E4E8] hover:shadow-lg transition">
                            <!-- Image -->
                            <div class="aspect-[3/2] w-full bg-gray-200 relative overflow-hidden">
                                @if($related->featuredMedia)
                                    <img src="{{ Storage::disk($related->featuredMedia->disk)->url($related->featuredMedia->path) }}" 
                                         alt="{{ $related->title }}" 
                                         class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Content -->
                            <div class="p-4">
                                <!-- Category & Region -->
                                <div class="flex items-center space-x-2 mb-2 text-[10px] font-bold uppercase tracking-wider text-[#1A2BC4]">
                                    @if($related->category)
                                        <span>{{ $related->category->name }}</span>
                                    @endif
                                    @if($related->region)
                                        <span>&bull;</span>
                                        <span>{{ $related->region->name }}</span>
                                    @endif
                                </div>
                                
                                <!-- Title -->
                                <h4 class="text-base font-bold text-[#17191D] leading-tight mb-3 group-hover:text-[#1A2BC4] transition">
                                    {{ $related->title }}
                                </h4>
                                
                                <!-- Date -->
                                <div class="text-xs text-[#8A9099]">
                                    {{ $related->published_at->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMM YYYY, HH:mm') }} WIB
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
