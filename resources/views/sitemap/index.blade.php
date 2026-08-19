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
    </url>
@endforeach
@foreach($pages as $page)
    <url>
        <loc>{{ route('pages.show', $page->slug) }}</loc>
    </url>
@endforeach
</urlset>
