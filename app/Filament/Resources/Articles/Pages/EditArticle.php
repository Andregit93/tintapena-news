<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;

use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['status']);
        unset($data['author_id']);
        unset($data['published_at']);
        unset($data['scheduled_at']);
        unset($data['archived_at']);

        return $data;
    }
}
