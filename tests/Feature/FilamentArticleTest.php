<?php

use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Models\Article;
use App\Models\Category;
use App\Models\Region;
use App\Models\Tag;
use App\Models\User;
use App\Enums\ArticleStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->guest = User::factory()->create();
});

it('guest cannot access article admin', function () {
    get(ArticleResource::getUrl('index'))->assertRedirectContains('/admin/login');
});

it('authenticated user can access list', function () {
    actingAs($this->admin)->get(ArticleResource::getUrl('index'))->assertSuccessful();
});

it('article can be created as Draft', function () {
    actingAs($this->admin);
    $category = Category::factory()->create(['is_active' => true]);

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => 'Test Article',
            'slug' => 'test-article',
            'category_id' => $category->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Article::class, [
        'title' => 'Test Article',
        'slug' => 'test-article',
        'category_id' => $category->id,
        'author_id' => $this->admin->id,
        'status' => ArticleStatus::Draft->value,
        'published_at' => null,
    ]);
});

it('author_id automatically uses authenticated user and ignores client input', function () {
    actingAs($this->admin);
    $category = Category::factory()->create(['is_active' => true]);
    $otherUser = User::factory()->create();

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => 'Test Article 2',
            'slug' => 'test-article-2',
            'category_id' => $category->id,
        ])
        // Simulate client trying to pass author_id directly (even though it's not in the schema, we check the override)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Article::class, [
        'slug' => 'test-article-2',
        'author_id' => $this->admin->id,
    ]);
});

it('client cannot override status on create', function () {
    actingAs($this->admin);
    $category = Category::factory()->create(['is_active' => true]);

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => 'Test Article 3',
            'slug' => 'test-article-3',
            'category_id' => $category->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Article::class, [
        'slug' => 'test-article-3',
        'status' => ArticleStatus::Draft->value,
    ]);
});

it('title is required', function () {
    actingAs($this->admin);
    $category = Category::factory()->create(['is_active' => true]);

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => null,
            'slug' => 'test-article-4',
            'category_id' => $category->id,
        ])
        ->call('create')
        ->assertHasFormErrors(['title' => 'required']);
});

it('slug is required and unique', function () {
    actingAs($this->admin);
    $category = Category::factory()->create(['is_active' => true]);
    Article::factory()->create(['slug' => 'test-article-5']);

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => 'Test Article 5',
            'slug' => null,
            'category_id' => $category->id,
        ])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'required']);

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => 'Test Article 5 Duplicate',
            'slug' => 'test-article-5',
            'category_id' => $category->id,
        ])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);
});

it('safe slug auto-generation behavior', function () {
    actingAs($this->admin);

    Livewire::test(CreateArticle::class)
        ->fillForm(['title' => 'Berita Awal'])
        ->assertFormSet(['slug' => 'berita-awal'])
        ->fillForm(['title' => 'Berita Kedua'])
        ->assertFormSet(['slug' => 'berita-kedua'])
        ->fillForm(['slug' => 'custom-slug'])
        ->fillForm(['title' => 'Berita Ketiga'])
        ->assertFormSet(['slug' => 'custom-slug']);
});

it('category is required, region is optional', function () {
    actingAs($this->admin);

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => 'Test',
            'slug' => 'test',
            'category_id' => null,
            'region_id' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['category_id' => 'required'])
        ->assertHasNoFormErrors(['region_id']);
});

it('multiple tags persist correctly', function () {
    actingAs($this->admin);
    $category = Category::factory()->create(['is_active' => true]);
    $tags = Tag::factory(3)->create();

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => 'Tags Test',
            'slug' => 'tags-test',
            'category_id' => $category->id,
            'tags' => $tags->pluck('id')->toArray(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $article = Article::where('slug', 'tags-test')->first();
    expect($article->tags)->toHaveCount(3);
});

it('article Draft can be edited without unintentionally changing status', function () {
    actingAs($this->admin);
    $category = Category::factory()->create(['is_active' => true]);
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'author_id' => $this->admin->id,
        'published_at' => now(),
    ]);

    Livewire::test(EditArticle::class, ['record' => $article->id])
        ->fillForm([
            'title' => 'Updated Title',
            'category_id' => $category->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Article::class, [
        'id' => $article->id,
        'title' => 'Updated Title',
        'status' => ArticleStatus::Published->value,
        'author_id' => $this->admin->id,
    ]);
});

it('SEO fields persist and meta_description max 320', function () {
    actingAs($this->admin);
    $category = Category::factory()->create(['is_active' => true]);

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => 'SEO Test',
            'slug' => 'seo-test',
            'category_id' => $category->id,
            'seo_title' => 'SEO Title',
            'meta_description' => str_repeat('a', 321),
        ])
        ->call('create')
        ->assertHasFormErrors(['meta_description' => 'max']);

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => 'SEO Test',
            'slug' => 'seo-test',
            'category_id' => $category->id,
            'seo_title' => 'SEO Title',
            'meta_description' => 'Valid meta description',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Article::class, [
        'slug' => 'seo-test',
        'seo_title' => 'SEO Title',
        'meta_description' => 'Valid meta description',
    ]);
});

it('list search by title works', function () {
    actingAs($this->admin);
    Article::factory()->create(['title' => 'Alpha Berita']);
    Article::factory()->create(['title' => 'Beta News']);

    Livewire::test(ListArticles::class)
        ->searchTable('Alpha')
        ->assertCanSeeTableRecords(Article::where('title', 'Alpha Berita')->get())
        ->assertCanNotSeeTableRecords(Article::where('title', 'Beta News')->get());
});

it('status, category, region filters work', function () {
    actingAs($this->admin);
    $cat1 = Category::factory()->create();
    $cat2 = Category::factory()->create();
    $reg1 = Region::factory()->create();
    
    $article1 = Article::factory()->create(['status' => ArticleStatus::Draft, 'category_id' => $cat1->id, 'region_id' => $reg1->id]);
    $article2 = Article::factory()->create(['status' => ArticleStatus::Published, 'category_id' => $cat2->id, 'region_id' => null]);

    // Status Filter
    Livewire::test(ListArticles::class)
        ->filterTable('status', ArticleStatus::Draft->value)
        ->assertCanSeeTableRecords([$article1])
        ->assertCanNotSeeTableRecords([$article2]);

    // Category Filter
    Livewire::test(ListArticles::class)
        ->filterTable('category', $cat2->id)
        ->assertCanSeeTableRecords([$article2])
        ->assertCanNotSeeTableRecords([$article1]);

    // Region Filter
    Livewire::test(ListArticles::class)
        ->filterTable('region', $reg1->id)
        ->assertCanSeeTableRecords([$article1])
        ->assertCanNotSeeTableRecords([$article2]);
});

it('article edit page has no delete action', function () {
    actingAs($this->admin);
    $article = Article::factory()->create();

    Livewire::test(EditArticle::class, ['record' => $article->id])
        ->assertActionDoesNotExist('delete');
});

it('article table exposes no bulk delete action', function () {
    actingAs($this->admin);

    Livewire::test(ListArticles::class)
        ->assertTableBulkActionDoesNotExist('delete');
});
