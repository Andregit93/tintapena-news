<?php

namespace App\Filament\Resources\Articles\Tables;

use App\Enums\ArticleStatus;
use Filament\Actions\BulkActionGroup;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (ArticleStatus $state): string => match ($state) {
                        ArticleStatus::Draft => 'gray',
                        ArticleStatus::Scheduled => 'warning',
                        ArticleStatus::Published => 'success',
                        ArticleStatus::Archived => 'danger',
                    })
                    ->formatStateUsing(fn (ArticleStatus $state): string => match ($state) {
                        ArticleStatus::Draft => 'Draft',
                        ArticleStatus::Scheduled => 'Terjadwal',
                        ArticleStatus::Published => 'Diterbitkan',
                        ArticleStatus::Archived => 'Arsip',
                    }),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable(),
                TextColumn::make('region.name')
                    ->label('Wilayah')
                    ->default('-')
                    ->sortable(),
                TextColumn::make('author.name')
                    ->label('Penulis')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        ArticleStatus::Draft->value => 'Draft',
                        ArticleStatus::Scheduled->value => 'Terjadwal',
                        ArticleStatus::Published->value => 'Diterbitkan',
                        ArticleStatus::Archived->value => 'Arsip',
                    ]),
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('region')
                    ->label('Wilayah')
                    ->relationship('region', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([

                ]),
            ]);
    }
}
