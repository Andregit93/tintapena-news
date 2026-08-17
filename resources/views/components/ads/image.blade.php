@php
    $imageUrl = Storage::disk($ad->media->disk)->url($ad->media->path);
    $altText = $ad->media->alt_text ?: $ad->name;
    $validTarget = null;

    if ($ad->target_url) {
        $parsedUrl = parse_url($ad->target_url);
        if (isset($parsedUrl['scheme']) && in_array(strtolower($parsedUrl['scheme']), ['http', 'https'])) {
            $validTarget = $ad->target_url;
        }
    }
@endphp

@if($validTarget)
    <a href="{{ $validTarget }}" target="_blank" rel="noopener noreferrer sponsored nofollow">
@endif

    <img src="{{ $imageUrl }}" alt="{{ $altText }}" loading="lazy" decoding="async" class="max-w-full h-auto">

@if($validTarget)
    </a>
@endif
