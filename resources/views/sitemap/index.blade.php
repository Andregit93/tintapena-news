{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('home') }}</loc>
    </url>
    <url>
        <loc>{{ route('articles.latest') }}</loc>
    </url>
    <url>
        <loc>{{ route('articles.popular') }}</loc>
    </url>
    <url>
        <loc>{{ route('contact.show') }}</loc>
    </url>
@foreach($articles as $article)
    <url>
        <loc>{{ route('articles.show', $article->slug) }}</loc>
@if($article->published_at)
        <lastmod>{{ $article->published_at->tz('UTC')->toAtomString() }}</lastmod>
@endif
    </url>
@endforeach
@foreach($pages as $page)
    <url>
        <loc>{{ route('pages.show', $page->slug) }}</loc>
@if($page->updated_at)
        <lastmod>{{ $page->updated_at->tz('UTC')->toAtomString() }}</lastmod>
@endif
    </url>
@endforeach
</urlset>
