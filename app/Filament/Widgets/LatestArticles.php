<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Articles\ArticleResource;
use App\Models\Article;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestArticles extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Berita Terbaru';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Article::published()
                    ->with('category')
                    ->orderByDesc('published_at')
                    ->orderByDesc('id')
                    ->limit(5)
            )
            ->paginated(false)
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->limit(50),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->visibleFrom('md'),
                TextColumn::make('published_at')
                    ->label('Terbit')
                    ->dateTime('d M Y, H:i')
                    ->visibleFrom('md'),
            ])
            ->recordUrl(
                fn (Article $record): string => ArticleResource::getUrl('edit', ['record' => $record]),
            )
            ->emptyStateHeading('Belum ada berita diterbitkan');
    }
}
