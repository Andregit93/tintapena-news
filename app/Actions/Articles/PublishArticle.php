<?php

namespace App\Actions\Articles;

use App\Models\Article;
use App\Enums\ArticleStatus;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class PublishArticle
{
    public function execute(Article $article, ?CarbonInterface $publishedAt = null): Article
    {
        if (empty($article->title) || empty($article->slug) || empty($article->content) || empty($article->category_id)) {
            throw new InvalidArgumentException('Article is missing required fields (title, slug, content, category) to be published.');
        }

        if (!in_array($article->status, [ArticleStatus::Draft, ArticleStatus::Scheduled])) {
            throw new InvalidArgumentException('Only Draft or Scheduled articles can be published.');
        }

        if ($article->status === ArticleStatus::Scheduled) {
            if (!$article->scheduled_at) {
                throw new InvalidArgumentException('Scheduled article must have a scheduled_at date to be published.');
            }
            
            if (now()->isBefore($article->scheduled_at)) {
                throw new InvalidArgumentException('Cannot publish a scheduled article before its scheduled time.');
            }

            if (!$publishedAt) {
                throw new InvalidArgumentException('Publishing a Scheduled article requires an explicit publishedAt argument (usually original scheduled_at).');
            }
        }

        $article->status = ArticleStatus::Published;
        $article->published_at = $publishedAt ?? now();
        $article->scheduled_at = null;
        $article->archived_at = null;
        $article->save();

        return $article;
    }
}
