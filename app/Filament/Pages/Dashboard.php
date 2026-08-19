<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createArticle')
                ->label('Buat Berita')
                ->icon('heroicon-o-document-plus')
                ->url(fn (): string => ArticleResource::getUrl('create'))
                ->visible(fn (): bool => ArticleResource::canCreate()),
        ];
    }
}
