<?php

namespace App\Filament\Resources\Advertisements\Schemas;

use Filament\Schemas\Schema;

use App\Enums\AdvertisementType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Validation\Rule;

class AdvertisementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Iklan')
                    ->required()
                    ->maxLength(255),

                Select::make('type')
                    ->label('Tipe')
                    ->required()
                    ->options([
                        AdvertisementType::Image->value => 'Gambar (Image)',
                        AdvertisementType::Script->value => 'Script',
                    ])
                    ->rules([
                        Rule::enum(AdvertisementType::class)
                    ])
                    ->live(),

                Select::make('placement_key')
                    ->label('Penempatan')
                    ->required()
                    ->options([
                        'homepage_top' => 'Homepage Top',
                        'homepage_middle' => 'Homepage Middle',
                        'article_inline' => 'Article Inline',
                        'article_sidebar' => 'Article Sidebar',
                        'category_sidebar' => 'Category Sidebar',
                    ])
                    ->rules([
                        Rule::in(['homepage_top', 'homepage_middle', 'article_inline', 'article_sidebar', 'category_sidebar'])
                    ]),

                Select::make('media_id')
                    ->label('Media Gambar')
                    ->relationship('media', 'original_filename')
                    ->getOptionLabelFromRecordUsing(fn (\App\Models\Media $record) => $record->original_filename ?: $record->path)
                    ->searchable()
                    ->preload()
                    ->required(fn (Get $get) => $get('type') === AdvertisementType::Image->value)
                    ->visible(fn (Get $get) => $get('type') === AdvertisementType::Image->value)
                    ->rule('exists:media,id'),

                Textarea::make('content')
                    ->label('Script Content')
                    ->required(fn (Get $get) => $get('type') === AdvertisementType::Script->value)
                    ->visible(fn (Get $get) => $get('type') === AdvertisementType::Script->value)
                    ->columnSpanFull(),

                TextInput::make('target_url')
                    ->label('Target URL')
                    ->url()
                    ->maxLength(500)
                    ->regex('/^https?:\/\//i')
                    ->nullable(),

                DateTimePicker::make('starts_at')
                    ->label('Mulai Tayang')
                    ->timezone('Asia/Jakarta')
                    ->nullable(),

                DateTimePicker::make('ends_at')
                    ->label('Selesai Tayang')
                    ->timezone('Asia/Jakarta')
                    ->after('starts_at')
                    ->nullable(),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),

                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
            ]);
    }
}
