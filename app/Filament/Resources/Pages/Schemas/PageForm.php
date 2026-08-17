<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Enums\PageStatus;
use App\Models\Page;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Grid::make()
                            ->schema([
                                Section::make('Konten')
                                    ->schema([
                                        TextInput::make('title')
                                            ->label('Judul')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state, ?Page $record) {
                                                if ($record !== null) {
                                                    return;
                                                }

                                                $currentSlug = (string) ($get('slug') ?? '');
                                                $oldAutoSlug = Str::slug((string) $old);

                                                if ($currentSlug === '' || $currentSlug === $oldAutoSlug) {
                                                    $set('slug', Str::slug($state));
                                                }
                                            }),

                                        TextInput::make('slug')
                                            ->label('Slug')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true)
                                            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/'),

                                        RichEditor::make('content')
                                            ->label('Konten')
                                            ->required()
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('SEO')
                                    ->schema([
                                        TextInput::make('seo_title')
                                            ->label('SEO Title')
                                            ->maxLength(255),

                                        TextInput::make('meta_description')
                                            ->label('Meta Description')
                                            ->maxLength(320),
                                    ]),
                            ])
                            ->columnSpan(['lg' => 2]),

                        Grid::make()
                            ->schema([
                                Section::make('Status')
                                    ->schema([
                                        Select::make('status')
                                            ->label('Status')
                                            ->options(PageStatus::class)
                                            ->default(PageStatus::Draft)
                                            ->rules([Rule::enum(PageStatus::class)])
                                            ->required(),
                                    ]),
                            ])
                            ->columnSpan(['lg' => 1]),
                    ]),
            ]);
    }
}
