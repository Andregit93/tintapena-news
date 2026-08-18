@extends('layouts.public')

@php
    $sanitizedContent = (string) str($page->content)->sanitizeHtml();

    $seoTitle = blank($page->seo_title) ? $page->title : $page->seo_title;

    $metaDescription = $page->meta_description;
    if (blank($metaDescription)) {
        $stripped = strip_tags($sanitizedContent);
        $normalized = preg_replace('/\s+/', ' ', $stripped);
        $metaDescription = \Illuminate\Support\Str::limit(trim($normalized), 160);
    }

    $canonicalUrl = route('pages.show', ['slug' => $page->slug]);
@endphp

@section('title', $seoTitle)
@section('meta_description', $metaDescription)
@section('canonical', $canonicalUrl)

@section('content')
<div class="bg-white md:bg-transparent">
    <div class="max-w-[800px] mx-auto pt-4 md:pt-0 px-4 md:px-0 mb-12">
        <!-- Title -->
        <h1 class="text-2xl md:text-[40px] leading-tight md:leading-[1.2] font-bold text-[#17191D] mb-8 border-b border-[#E1E4E8] pb-4">
            {{ $page->title }}
        </h1>

        <!-- Page Body -->
        <div class="article-content">
            {!! $sanitizedContent !!}
        </div>
    </div>
</div>
@endsection
