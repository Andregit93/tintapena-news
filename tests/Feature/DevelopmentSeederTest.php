<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleViewStat;
use App\Models\Category;
use App\Models\Region;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DevelopmentSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed required taxonomies
    $this->seed(CategorySeeder::class);
    $this->seed(RegionSeeder::class);
});

it('1. Seeder rejects production and staging environments', function () {
    App::detectEnvironment(fn() => 'production');
    
    $seeder = new DevelopmentSeeder();
    
    expect(fn() => $seeder->run())
        ->toThrow(RuntimeException::class, 'DevelopmentSeeder may only be run in local or testing environments.');
        
    App::detectEnvironment(fn() => 'staging');
    expect(fn() => $seeder->run())
        ->toThrow(RuntimeException::class, 'DevelopmentSeeder may only be run in local or testing environments.');
        
    // Reset back to testing
    App::detectEnvironment(fn() => 'testing');
});

it('2. Seeder requires an existing User', function () {
    expect(User::count())->toBe(0);
    
    expect(fn() => (new DevelopmentSeeder())->run())
        ->toThrow(RuntimeException::class, 'No user found');
});

it('3. Protects user-created articles from overwrite', function () {
    User::factory()->create();
    
    // Create an unrelated article that happens to share a slug with what would be generated
    // (though our new generator uses dev-* prefix, we simulate a collision)
    $collisionSlug = 'dev-simulasi-berita-politik-di-pangkalpinang-seri-0';
    $userArticle = Article::factory()->create([
        'slug' => $collisionSlug,
        'title' => 'Real User Article',
        'subtitle' => 'Real User Subtitle', // No dev marker
    ]);
    
    $seeder = new DevelopmentSeeder();
    
    expect(fn() => $seeder->run())
        ->toThrow(RuntimeException::class, "Slug collision with non-development article: {$collisionSlug}");
        
    // Ensure the original article wasn't modified
    $userArticle->refresh();
    expect($userArticle->title)->toBe('Real User Article');
});

it('4. Creates exactly 50 dev articles with all categories, regions and markers, and is idempotent', function () {
    User::factory()->create();
    
    $seeder = new DevelopmentSeeder();
    $seeder->run();
    
    $articles = Article::where('slug', 'like', 'dev-%')->get();
    expect($articles->count())->toBe(50);
    
    // Verify marker
    foreach ($articles as $article) {
        expect(str_contains($article->subtitle, 'Konten simulasi'))->toBeTrue();
    }
    
    // Verify categories
    $categoryIds = $articles->pluck('category_id')->unique();
    expect($categoryIds->count())->toBe(9); // All 9 categories
    
    // Verify regions
    $regionIds = $articles->pluck('region_id')->unique();
    expect($regionIds->count())->toBe(7); // All 7 regions
    
    // Verify specific categories have published content
    $opiniId = Category::where('slug', 'opini')->first()->id;
    $hukumId = Category::where('slug', 'hukum-kriminal')->first()->id;
    
    $hasPublishedOpini = $articles->where('category_id', $opiniId)->where('status', ArticleStatus::Published)->count() > 0;
    $hasPublishedHukum = $articles->where('category_id', $hukumId)->where('status', ArticleStatus::Published)->count() > 0;
    
    expect($hasPublishedOpini)->toBeTrue();
    expect($hasPublishedHukum)->toBeTrue();
    
    // Test idempotency
    $seeder->run();
    
    $newCount = Article::where('slug', 'like', 'dev-%')->count();
    expect($newCount)->toBe(50); // No duplicates
});

it('5. Popular data is explicitly deterministic (24h vs 7d leaders differ)', function () {
    User::factory()->create();
    $seeder = new DevelopmentSeeder();
    $seeder->run();
    
    // Group 1 (index 0-9) leads 24h
    // Group 2 (index 10-19) leads 7d
    
    $now = now();
    $startOf24h = (clone $now)->subHours(24);
    $startOf7d = (clone $now)->subDays(7);
    
    $stats24h = ArticleViewStat::where('period_start', '>=', $startOf24h)
        ->selectRaw('article_id, sum(views_count) as total')
        ->groupBy('article_id')
        ->orderByDesc('total')
        ->get();
        
    expect($stats24h->count())->toBeGreaterThanOrEqual(10);
    
    $leader24h = $stats24h->first()->article_id;
    
    $stats7d = ArticleViewStat::where('period_start', '>=', $startOf7d)
        ->selectRaw('article_id, sum(views_count) as total')
        ->groupBy('article_id')
        ->orderByDesc('total')
        ->get();
        
    $leader7d = $stats7d->first()->article_id;
    
    // Leaders must differ
    expect($leader24h)->not->toBe($leader7d);
    
    // Ensure all view counts are positive
    $allStats = ArticleViewStat::all();
    foreach ($allStats as $stat) {
        expect($stat->views_count)->toBeGreaterThan(0);
    }
    
    // Check old stats exist
    $oldStats = ArticleViewStat::where('period_start', '<', $startOf7d)->count();
    expect($oldStats)->toBeGreaterThan(0);
});

it('6. Lifecycle states are correct', function () {
    User::factory()->create();
    (new DevelopmentSeeder())->run();
    
    $published = Article::where('status', ArticleStatus::Published)->get();
    expect($published->count())->toBe(40);
    foreach ($published as $article) {
        expect($article->published_at)->not->toBeNull();
        expect($article->published_at->isPast())->toBeTrue();
        expect($article->scheduled_at)->toBeNull();
        expect($article->archived_at)->toBeNull();
    }
    
    $draft = Article::where('status', ArticleStatus::Draft)->get();
    expect($draft->count())->toBe(5);
    foreach ($draft as $article) {
        expect($article->published_at)->toBeNull();
        expect($article->scheduled_at)->toBeNull();
        expect($article->archived_at)->toBeNull();
    }
    
    $scheduled = Article::where('status', ArticleStatus::Scheduled)->get();
    expect($scheduled->count())->toBe(3);
    foreach ($scheduled as $article) {
        expect($article->scheduled_at)->not->toBeNull();
        expect($article->scheduled_at->isFuture())->toBeTrue();
        expect($article->published_at)->toBeNull();
        expect($article->archived_at)->toBeNull();
    }
    
    $archived = Article::where('status', ArticleStatus::Archived)->get();
    expect($archived->count())->toBe(2);
    foreach ($archived as $article) {
        expect($article->published_at)->not->toBeNull();
        expect($article->published_at->isPast())->toBeTrue();
        expect($article->archived_at)->not->toBeNull();
        expect($article->scheduled_at)->toBeNull();
    }
});
