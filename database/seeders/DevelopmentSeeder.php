<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleViewStat;
use App\Models\Category;
use App\Models\Region;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('DevelopmentSeeder may only be run in local or testing environments.');
        }

        $user = User::first();
        if (! $user) {
            throw new \RuntimeException('No user found. Please run UserSeeder or create an admin user first.');
        }

        $categories = [
            'politik', 'pemerintahan', 'ekonomi', 'hukum-kriminal',
            'pendidikan', 'kesehatan', 'pariwisata', 'olahraga', 'opini'
        ];
        
        $categoryModels = [];
        foreach ($categories as $slug) {
            $cat = Category::where('slug', $slug)->first();
            if (! $cat) {
                throw new \RuntimeException("Required category not found: {$slug}");
            }
            $categoryModels[$slug] = $cat->id;
        }

        $regions = [
            'pangkalpinang', 'bangka', 'bangka-barat', 'bangka-tengah',
            'bangka-selatan', 'belitung', 'belitung-timur'
        ];
        
        $regionModels = [];
        foreach ($regions as $slug) {
            $reg = Region::where('slug', $slug)->first();
            if (! $reg) {
                throw new \RuntimeException("Required region not found: {$slug}");
            }
            $regionModels[$slug] = $reg->id;
        }

        // Tags
        $tagsData = [
            'Bangka Belitung', 'Pangkalpinang', 'DPRD', 'Pemprov Babel', 
            'UMKM', 'Timah', 'Ekonomi Daerah', 'Pendidikan', 'Kesehatan', 
            'Pariwisata Babel', 'Olahraga Babel', 'Polisi', 'Cuaca', 
            'Pelayanan Publik', 'Opini Publik'
        ];
        
        $tagIds = [];
        foreach ($tagsData as $tagName) {
            $tagIds[] = Tag::updateOrCreate([
                'slug' => Str::slug($tagName),
            ], [
                'name' => $tagName,
            ])->id;
        }

        // We need exactly 50 articles.
        // We will cycle through regions and categories to ensure all are represented.
        $allGenerated = [];
        $catCount = count($categories);
        $regCount = count($regions);
        
        for ($i = 0; $i < 50; $i++) {
            $catSlug = $categories[$i % $catCount];
            $regSlug = $regions[$i % $regCount];
            $regName = ucfirst(str_replace('-', ' ', $regSlug));
            
            $title = "Simulasi Berita {$catSlug} di {$regName} Seri {$i}";
            if ($catSlug === 'opini') {
                $title = "Opini Simulasi: Pandangan Pembangunan di {$regName} Seri {$i}";
            } elseif ($catSlug === 'hukum-kriminal') {
                $title = "Simulasi Laporan Kriminalitas di Wilayah {$regName} Seri {$i}";
            }
            
            $allGenerated[] = [
                'title' => $title,
                'cat' => $catSlug,
                'reg' => $regSlug,
            ];
        }

        // Assign statuses deterministically:
        // 40 Published, 5 Draft, 3 Scheduled, 2 Archived
        $statuses = array_merge(
            array_fill(0, 40, 'published'),
            array_fill(0, 5, 'draft'),
            array_fill(0, 3, 'scheduled'),
            array_fill(0, 2, 'archived')
        );

        $now = Carbon::now();
        
        foreach ($allGenerated as $index => $data) {
            $statusStr = $statuses[$index];
            $title = $data['title'];
            $baseSlug = Str::slug($title);
            $slug = 'dev-' . $baseSlug;
            
            $statusEnum = match($statusStr) {
                'published' => ArticleStatus::Published,
                'draft' => ArticleStatus::Draft,
                'scheduled' => ArticleStatus::Scheduled,
                'archived' => ArticleStatus::Archived,
            };

            $publishedAt = null;
            $scheduledAt = null;
            $archivedAt = null;
            $viewsCount = 0;

            if ($statusStr === 'published') {
                // Determine days ago based on index to distribute times
                $daysAgo = $index % 30;
                $publishedAt = clone $now;
                $publishedAt->subDays($daysAgo)->subHours(($index % 12) + 1);
                $viewsCount = 100 + ($index * 10);
            } elseif ($statusStr === 'scheduled') {
                $scheduledAt = clone $now;
                $scheduledAt->addDays(($index % 5) + 1)->addHours(($index % 12) + 1);
            } elseif ($statusStr === 'archived') {
                $publishedAt = clone $now;
                $publishedAt->subDays(40);
                $archivedAt = clone $now;
                $archivedAt->subDays(2);
            }

            $article = Article::where('slug', $slug)->first();
            
            if ($article && !str_contains($article->subtitle ?? '', 'Konten simulasi')) {
                throw new \RuntimeException("Slug collision with non-development article: {$slug}");
            }
            
            if (!$article) {
                $article = new Article(['slug' => $slug]);
            }
            
            $article->fill([
                'title' => $title,
                'subtitle' => 'Konten simulasi pengembangan TINTAPENA — bukan laporan peristiwa aktual.',
                'excerpt' => "Ini adalah contoh ringkasan untuk konten simulasi. Tidak ada kejadian faktual yang dilaporkan di sini.",
                'content' => "Ini adalah paragraf pertama dari konten simulasi pengembangan. Semua nama, tempat, dan peristiwa dalam tulisan ini bersifat demonstrasi untuk keperluan pengujian sistem TINTAPENA.\n\nDalam pengembangan platform berita, sangat penting untuk memiliki data uji yang memadai guna memastikan fitur-fitur seperti pencarian, pemfilteran kategori, dan peringkat berita terpopuler berfungsi sebagaimana mestinya.\n\nKonten simulasi pengembangan TINTAPENA — bukan laporan peristiwa aktual. Masyarakat dan pembaca diimbau untuk tidak menganggap ini sebagai informasi nyata.",
                'category_id' => $categoryModels[$data['cat']],
                'region_id' => $regionModels[$data['reg']],
                'seo_title' => $title,
                'meta_description' => "Konten simulasi pengembangan untuk {$title}.",
            ]);
            
            // Assign guarded fields
            $article->author_id = $user->id;
            $article->status = $statusEnum;
            $article->published_at = $publishedAt;
            $article->scheduled_at = $scheduledAt;
            $article->archived_at = $archivedAt;
            $article->views_count = $viewsCount;
            
            $article->save();

            // Tags (deterministic)
            $selectedTags = [
                $tagIds[$index % count($tagIds)],
                $tagIds[($index + 1) % count($tagIds)],
            ];
            $article->tags()->sync($selectedTags);
            
            // View stats for popular news
            if ($statusStr === 'published') {
                // Delete existing stats to maintain idempotency
                $article->viewStats()->delete();
                
                // Deterministic Popular Stats Logic
                // Make sure 24h ranking differs from 7d ranking
                // Let's divide the 40 published articles into segments
                
                if ($index < 10) {
                    $stat24 = new ArticleViewStat();
                    $stat24->article_id = $article->id;
                    $stat24->period_start = (clone $now)->subHours(($index % 12) + 1)->startOfHour();
                    $stat24->views_count = 500 + ($index * 10);
                    $stat24->save();
                    
                    $stat7d = new ArticleViewStat();
                    $stat7d->article_id = $article->id;
                    $stat7d->period_start = (clone $now)->subDays(3)->startOfHour();
                    $stat7d->views_count = 50;
                    $stat7d->save();
                } elseif ($index < 20) {
                    $stat24 = new ArticleViewStat();
                    $stat24->article_id = $article->id;
                    $stat24->period_start = (clone $now)->subHours(($index % 12) + 1)->startOfHour();
                    $stat24->views_count = 20;
                    $stat24->save();
                    
                    $stat7d = new ArticleViewStat();
                    $stat7d->article_id = $article->id;
                    $stat7d->period_start = (clone $now)->subDays(4)->startOfHour();
                    $stat7d->views_count = 800 + ($index * 10);
                    $stat7d->save();
                } else {
                    $statOld = new ArticleViewStat();
                    $statOld->article_id = $article->id;
                    $statOld->period_start = (clone $now)->subDays(10)->startOfHour();
                    $statOld->views_count = 200;
                    $statOld->save();
                }
            }
        }
    }
}
