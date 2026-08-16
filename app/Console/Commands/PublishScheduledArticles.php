<?php

namespace App\Console\Commands;

use App\Actions\Articles\PublishArticle;
use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Console\Command;
use Throwable;

class PublishScheduledArticles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'articles:publish-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish articles that are scheduled and due for publication';

    /**
     * Execute the console command.
     */
    public function handle(PublishArticle $publishAction): void
    {
        $dueArticles = Article::where('status', ArticleStatus::Scheduled)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        $publishedCount = 0;
        $failedCount = 0;

        foreach ($dueArticles as $article) {
            try {
                // Pass the scheduled_at time to use as the published_at time
                $publishAction->execute($article, $article->scheduled_at);
                $publishedCount++;
            } catch (Throwable $e) {
                $failedCount++;
                $this->error("Failed to publish article #{$article->id}: {$e->getMessage()}");
            }
        }

        $this->info("Scheduled publication complete. Published: {$publishedCount}, Failed: {$failedCount}");
    }
}
