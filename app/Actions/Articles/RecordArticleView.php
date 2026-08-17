<?php

namespace App\Actions\Articles;

use App\Models\Article;
use App\Models\ArticleViewStat;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class RecordArticleView
{
    /**
     * Record a view for an article.
     * Guaranteed atomic increments, database-safe, and avoids race conditions.
     */
    public function execute(Article $article): void
    {
        $now = now('UTC');
        $periodStart = $now->copy()->startOfHour();

        DB::transaction(function () use ($article, $periodStart, $now) {
            // Increment overall views count atomically, but ONLY if the article is still valid
            $lifetimeIncremented = Article::query()
                ->whereKey($article->id)
                ->where('status', \App\Enums\ArticleStatus::Published)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', $now)
                ->increment('views_count');

            if ($lifetimeIncremented === 0) {
                return;
            }

            // 1. Try to increment an existing view stat bucket
            $updated = ArticleViewStat::where('article_id', $article->id)
                ->where('period_start', $periodStart)
                ->increment('views_count');

            // 2. If no bucket existed, attempt to create the first one
            if ($updated === 0) {
                try {
                    $stat = new ArticleViewStat();
                    $stat->article_id = $article->id;
                    $stat->period_start = $periodStart;
                    $stat->views_count = 1;
                    $stat->save();
                } catch (UniqueConstraintViolationException $e) {
                    // 3. If a concurrent request created the bucket between step 1 and 2,
                    // the unique constraint will be violated. We can now safely increment it.
                    ArticleViewStat::where('article_id', $article->id)
                        ->where('period_start', $periodStart)
                        ->increment('views_count');
                }
            }
        });
    }
}
