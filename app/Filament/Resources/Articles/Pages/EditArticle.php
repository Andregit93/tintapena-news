<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use App\Enums\ArticleStatus;
use App\Actions\Articles\PublishArticle;
use App\Actions\Articles\ScheduleArticle;
use App\Actions\Articles\ArchiveArticle;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Preview')
                ->color('gray')
                ->icon('heroicon-o-eye')
                ->url(fn ($record) => ArticleResource::getUrl('preview', ['record' => $record]))
                ->openUrlInNewTab(),

            Action::make('publish')
                ->label('Terbitkan')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn ($record) => $record->status === ArticleStatus::Draft)
                ->requiresConfirmation()
                ->modalHeading('Terbitkan Berita')
                ->modalDescription('Apakah Anda yakin ingin menerbitkan berita ini sekarang?')
                ->action(function ($record) {
                    $this->save();
                    app(PublishArticle::class)->execute($record);
                    $this->refreshFormData(['status', 'published_at', 'scheduled_at', 'archived_at']);
                }),

            Action::make('schedule')
                ->label('Jadwalkan')
                ->color('warning')
                ->icon('heroicon-o-clock')
                ->visible(fn ($record) => $record->status === ArticleStatus::Draft)
                ->form([
                    DateTimePicker::make('scheduled_at')
                        ->label('Tanggal & Waktu')
                        ->required()
                        ->after('now')
                        ->native(false),
                ])
                ->modalHeading('Jadwalkan Berita')
                ->action(function ($record, array $data) {
                    $this->save();
                    app(ScheduleArticle::class)->execute($record, \Carbon\Carbon::parse($data['scheduled_at']));
                    $this->refreshFormData(['status', 'published_at', 'scheduled_at', 'archived_at']);
                }),

            Action::make('archive')
                ->label('Arsipkan')
                ->color('danger')
                ->icon('heroicon-o-archive-box')
                ->visible(fn ($record) => $record->status === ArticleStatus::Published)
                ->requiresConfirmation()
                ->modalHeading('Arsipkan Berita')
                ->modalDescription('Berita yang diarsipkan tidak akan tampil di publik. Lanjutkan?')
                ->action(function ($record) {
                    app(ArchiveArticle::class)->execute($record);
                    $this->refreshFormData(['status', 'published_at', 'scheduled_at', 'archived_at']);
                }),
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
