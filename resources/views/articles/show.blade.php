@extends('layouts.public')

@php
    $metaDescription = $article->meta_description;
    if (!$metaDescription && $article->excerpt) {
        $metaDescription = $article->excerpt;
    }
    if (!$metaDescription) {
        $stripped = strip_tags($article->content);
        $normalized = preg_replace('/\s+/', ' ', $stripped);
        $metaDescription = \Illuminate\Support\Str::limit(trim($normalized), 160);
    }
@endphp

@section('title', $article->seo_title ?? $article->title)
@section('meta_description', $metaDescription)
@section('canonical', route('articles.show', ['article' => $article->slug]))

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
                <span>Oleh Redaksi TINTAPENA</span>
                <span class="mx-1">&bull;</span>
                <span>{{ $article->published_at->locale('id')->isoFormat('D MMMM YYYY, HH:mm') }} WIB</span>
                @if($article->updated_at && $article->updated_at->gt($article->published_at))
                    <span class="mx-1">&bull;</span>
                    <span>Diperbarui {{ $article->updated_at->locale('id')->isoFormat('HH:mm') }} WIB</span>
                @endif
            </div>
            
            <div class="flex space-x-2">
                <span class="px-3 py-1.5 border border-[#E1E4E8] rounded bg-white font-medium text-[#17191D]">WhatsApp</span>
                <span class="px-3 py-1.5 border border-[#E1E4E8] rounded bg-white font-medium text-[#17191D]">Facebook</span>
                <span class="px-3 py-1.5 border border-[#E1E4E8] rounded bg-white font-medium text-[#17191D]">X</span>
                <span class="px-3 py-1.5 border border-[#E1E4E8] rounded bg-white font-medium text-[#17191D]">Salin Tautan</span>
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
        <div class="prose prose-lg max-w-none prose-a:text-[#1A2BC4] prose-headings:font-bold prose-headings:text-[#17191D] text-[#17191D] prose-p:leading-[1.8] prose-p:mb-6 mb-12">
            {!! $article->content !!}
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
    </div>
</div>
@endsection
