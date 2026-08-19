<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Articles\ArticleResource;
use App\Models\Article;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PopularArticles extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Terpopuler 24 Jam';

    public function table(Table $table): Table
    {
        $now = now();
        $from = $now->copy()->subHours(24);

        return $table
            ->query(
                Article::published()
                    ->withPeriodViews($from, $now)
                    ->with('category')
                    ->orderByDesc('period_views')
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
                TextColumn::make('period_views')
                    ->label('Views 24 Jam')
                    ->numeric()
                    ->sortable(false),
            ])
            ->recordUrl(
                fn (Article $record): string => ArticleResource::getUrl('edit', ['record' => $record]),
            )
            ->emptyStateHeading('Belum ada data populer 24 jam terakhir');
    }
}
