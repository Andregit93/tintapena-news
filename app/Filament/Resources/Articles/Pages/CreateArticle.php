<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Enums\ArticleStatus;
use App\Filament\Resources\Articles\ArticleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['author_id'] = auth()->id();
        $data['status'] = ArticleStatus::Draft->value;
        unset($data['published_at']);
        unset($data['scheduled_at']);
        unset($data['archived_at']);

        return $data;
    }
}
