<?php

namespace App\Actions\Articles;

use App\Models\Article;
use App\Enums\ArticleStatus;
use InvalidArgumentException;

class ArchiveArticle
{
    public function execute(Article $article): Article
    {
        if ($article->status !== ArticleStatus::Published) {
            throw new InvalidArgumentException('Only published articles can be archived.');
        }

        $article->status = ArticleStatus::Archived;
        $article->archived_at = now();
        $article->scheduled_at = null;
        // preserve original published_at
        $article->save();

        return $article;
    }
}
