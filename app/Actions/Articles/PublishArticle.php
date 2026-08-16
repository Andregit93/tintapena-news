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

        if ($article->status === ArticleStatus::Archived) {
            throw new InvalidArgumentException('Archived articles cannot be published.');
        }

        $article->status = ArticleStatus::Published;
        $article->published_at = $publishedAt ?? now();
        $article->scheduled_at = null;
        $article->archived_at = null;
        $article->save();

        return $article;
    }
}
