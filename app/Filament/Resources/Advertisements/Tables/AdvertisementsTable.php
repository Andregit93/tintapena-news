<?php

namespace App\Filament\Resources\Advertisements\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\Layout\Stack;
use App\Enums\AdvertisementType;

class AdvertisementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (AdvertisementType $state): string => match ($state) {
                        AdvertisementType::Image => 'success',
                        AdvertisementType::Script => 'warning',
                    })
                    ->formatStateUsing(fn (AdvertisementType $state): string => match ($state) {
                        AdvertisementType::Image => 'Image',
                        AdvertisementType::Script => 'Script',
                    }),

                TextColumn::make('placement_key')
                    ->label('Penempatan')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('starts_at')
                    ->label('Mulai Tayang')
                    ->dateTime('d M Y H:i', 'Asia/Jakarta')
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->label('Selesai Tayang')
                    ->dateTime('d M Y H:i', 'Asia/Jakarta')
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i', 'Asia/Jakarta')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->orderBy('sort_order', 'asc')->orderBy('created_at', 'desc'))
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                // No bulk delete requested
            ]);
    }
}
