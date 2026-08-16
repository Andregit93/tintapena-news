<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Cache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreviewArticle extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ArticleResource::class;

    protected string $view = 'filament.resources.articles.pages.preview-article';

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string | Htmlable
    {
        return 'Preview: ' . $this->record->title;
    }

    public function getHeading(): string | Htmlable
    {
        return 'Preview: ' . $this->record->title;
    }

    public static function getRouteMiddleware(\Filament\Panel $panel): string|array
    {
        return [
            \App\Http\Middleware\PreviewSecurityHeaders::class,
        ];
    }
}
