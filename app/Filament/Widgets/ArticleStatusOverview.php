<?php

namespace App\Filament\Widgets;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ArticleStatusOverview extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            Stat::make('Diterbitkan', Article::query()->where('status', ArticleStatus::Published)->count()),
            Stat::make('Draft', Article::query()->where('status', ArticleStatus::Draft)->count()),
            Stat::make('Terjadwal', Article::query()->where('status', ArticleStatus::Scheduled)->count()),
        ];
    }
}
