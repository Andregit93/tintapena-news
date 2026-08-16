<?php

namespace App\Actions\Articles;

use App\Models\Article;
use App\Enums\ArticleStatus;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class ScheduleArticle
{
    public function execute(Article $article, CarbonInterface $scheduledAt): Article
    {
        if (empty($article->title) || empty($article->slug) || blank(trim(strip_tags($article->content ?? ''))) || empty($article->category_id)) {
            throw new InvalidArgumentException('Article is missing required fields (title, slug, content, category) to be scheduled.');
        }

        if ($article->status !== ArticleStatus::Draft) {
            throw new InvalidArgumentException('Only Draft articles can be scheduled.');
        }

        if ($scheduledAt->isPast()) {
            throw new InvalidArgumentException('Scheduled time must be in the future.');
        }

        $article->status = ArticleStatus::Scheduled;
        $article->scheduled_at = $scheduledAt;
        $article->published_at = null;
        $article->archived_at = null;
        $article->save();

        return $article;
    }
}
