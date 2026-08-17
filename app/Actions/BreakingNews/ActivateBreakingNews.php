<?php

namespace App\Actions\BreakingNews;

use App\Models\Article;
use App\Models\BreakingNews;
use Illuminate\Validation\ValidationException;

class ActivateBreakingNews
{
    public function execute(BreakingNews $breakingNews): bool
    {
        if ($breakingNews->article_id) {
            if (!Article::published()->whereKey($breakingNews->article_id)->exists()) {
                throw ValidationException::withMessages([
                    'article_id' => 'The selected article must be currently published.'
                ]);
            }
        } else {
            if (empty($breakingNews->headline)) {
                throw ValidationException::withMessages([
                    'headline' => 'Headline is required for manual breaking news.'
                ]);
            }

            if (empty($breakingNews->target_url) || !filter_var($breakingNews->target_url, FILTER_VALIDATE_URL)) {
                throw ValidationException::withMessages([
                    'target_url' => 'A valid URL is required.'
                ]);
            }

            $scheme = parse_url($breakingNews->target_url, PHP_URL_SCHEME);
            if (!in_array(strtolower((string) $scheme), ['http', 'https'])) {
                throw ValidationException::withMessages([
                    'target_url' => 'A valid HTTP or HTTPS URL is required.'
                ]);
            }
        }

        $breakingNews->is_active = true;
        return $breakingNews->save();
    }
}
