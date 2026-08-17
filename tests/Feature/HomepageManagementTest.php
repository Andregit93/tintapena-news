<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\HomepageSlot;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\HomepageSlotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use App\Filament\Pages\ManageHomepage;

uses(RefreshDatabase::class);

$expectedSlots = [
    'headline_main',
    'headline_2',
    'headline_3',
    'editor_pick_1',
    'editor_pick_2',
    'editor_pick_3',
    'editor_pick_4'
];

it('1. HomepageSlotSeeder creates exactly 7 required slots', function () use ($expectedSlots) {
    $this->seed(HomepageSlotSeeder::class);
    
    expect(HomepageSlot::count())->toBe(7);
    
    $keys = HomepageSlot::pluck('slot_key')->toArray();
    foreach ($expectedSlots as $slot) {
        expect(in_array($slot, $keys))->toBeTrue();
    }
});

it('2. Seeder is idempotent', function () {
    $this->seed(HomepageSlotSeeder::class);
    $this->seed(HomepageSlotSeeder::class);
    
    expect(HomepageSlot::count())->toBe(7);
});

it('3. DatabaseSeeder includes HomepageSlotSeeder', function () {
    // Run the main seeder, it should run HomepageSlotSeeder
    Artisan::call('db:seed');
    
    expect(HomepageSlot::count())->toBe(7);
});

it('4. Guest cannot access /admin/homepage', function () {
    $response = $this->get('/admin/homepage');
    // Filament usually redirects guests to login
    expect($response->status())->toBeIn([302]);
});

it('5. Authenticated admin can access manager', function () {
    $admin = User::factory()->create();
    
    $response = $this->actingAs($admin)->get('/admin/homepage');
    $response->assertSuccessful();
});

it('6. Published article can be assigned to headline_main and 11. updated_by is set and 12. article status unchanged', function () {
    $this->seed(HomepageSlotSeeder::class);
    $admin = User::factory()->create();
    
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'scheduled_at' => null,
        'archived_at' => null,
    ]);
    
    Livewire::actingAs($admin)
        ->test(ManageHomepage::class)
        ->set('data.headline_main_article_id', $article->id)
        ->set('data.headline_main_is_active', true)
        ->call('save')
        ->assertHasNoErrors();
        
    $slot = HomepageSlot::where('slot_key', 'headline_main')->first();
    expect($slot->article_id)->toBe($article->id);
    expect($slot->updated_by)->toBe($admin->id); // 11
    
    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Published); // 12
});

it('7. Draft cannot be assigned', function () {
    $this->seed(HomepageSlotSeeder::class);
    $admin = User::factory()->create();
    
    $article = Article::factory()->create([
        'status' => ArticleStatus::Draft,
        'published_at' => null,
    ]);
    
    Livewire::actingAs($admin)
        ->test(ManageHomepage::class)
        ->set('data.headline_main_article_id', $article->id)
        ->set('data.headline_main_is_active', true)
        ->call('save')
        ->assertHasErrors(['data.headline_main_article_id']);
});

it('8. Scheduled future article cannot be assigned', function () {
    $this->seed(HomepageSlotSeeder::class);
    $admin = User::factory()->create();
    
    $article = Article::factory()->create([
        'status' => ArticleStatus::Scheduled,
        'published_at' => null,
        'scheduled_at' => now()->addDays(2),
    ]);
    
    Livewire::actingAs($admin)
        ->test(ManageHomepage::class)
        ->set('data.headline_main_article_id', $article->id)
        ->set('data.headline_main_is_active', true)
        ->call('save')
        ->assertHasErrors(['data.headline_main_article_id']);
});

it('9. Archived article cannot be assigned', function () {
    $this->seed(HomepageSlotSeeder::class);
    $admin = User::factory()->create();
    
    $article = Article::factory()->create([
        'status' => ArticleStatus::Archived,
        'published_at' => now()->subDays(10),
        'archived_at' => now()->subDay(),
    ]);
    
    Livewire::actingAs($admin)
        ->test(ManageHomepage::class)
        ->set('data.headline_main_article_id', $article->id)
        ->set('data.headline_main_is_active', true)
        ->call('save')
        ->assertHasErrors(['data.headline_main_article_id']);
});

it('10. Future-dated Published article cannot be assigned', function () {
    $this->seed(HomepageSlotSeeder::class);
    $admin = User::factory()->create();
    
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->addDays(1),
    ]);
    
    Livewire::actingAs($admin)
        ->test(ManageHomepage::class)
        ->set('data.headline_main_article_id', $article->id)
        ->set('data.headline_main_is_active', true)
        ->call('save')
        ->assertHasErrors(['data.headline_main_article_id']);
});

it('13. slot_key cannot be changed through the manager and 15. all keys remain present', function () use ($expectedSlots) {
    $this->seed(HomepageSlotSeeder::class);
    $admin = User::factory()->create();
    
    // Attempting to override data keys shouldn't change the db structure since it's driven by HomepageSlot::all() on backend
    Livewire::actingAs($admin)
        ->test(ManageHomepage::class)
        ->set('data.headline_main_article_id', null)
        ->set('data.non_existent_key_article_id', 999) // Should be ignored
        ->call('save')
        ->assertHasNoErrors();
        
    expect(HomepageSlot::count())->toBe(7);
    $keys = HomepageSlot::pluck('slot_key')->toArray();
    foreach ($expectedSlots as $slot) {
        expect(in_array($slot, $keys))->toBeTrue();
    }
});

it('14. Duplicate active article assignment is rejected', function () {
    $this->seed(HomepageSlotSeeder::class);
    $admin = User::factory()->create();
    
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
    ]);
    
    Livewire::actingAs($admin)
        ->test(ManageHomepage::class)
        ->set('data.headline_main_article_id', $article->id)
        ->set('data.headline_main_is_active', true)
        ->set('data.headline_2_article_id', $article->id)
        ->set('data.headline_2_is_active', true)
        ->call('save')
        ->assertHasErrors(); // Will have a validation error for headline_2_article_id
});

it('16. Draft cannot be assigned even if the slot is inactive', function () {
    $this->seed(HomepageSlotSeeder::class);
    $admin = User::factory()->create();
    
    $article = Article::factory()->create([
        'status' => ArticleStatus::Draft,
        'published_at' => null,
    ]);
    
    Livewire::actingAs($admin)
        ->test(ManageHomepage::class)
        ->set('data.headline_main_article_id', $article->id)
        ->set('data.headline_main_is_active', false)
        ->call('save')
        ->assertHasErrors(['data.headline_main_article_id']);
        
    $slot = HomepageSlot::where('slot_key', 'headline_main')->first();
    expect($slot->article_id)->toBeNull();
});
