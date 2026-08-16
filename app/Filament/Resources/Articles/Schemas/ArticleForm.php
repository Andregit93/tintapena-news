<?php

namespace App\Filament\Resources\Articles\Schemas;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)->schema([
                    Grid::make(1)->schema([
                        Section::make('Konten Utama')->schema([
                            TextInput::make('title')
                                ->label('Judul')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, $old, $set, $get) {
                                    if (($state ?? '') !== ($old ?? '')) {
                                        $slug = $get('slug');
                                        $oldSlug = \Illuminate\Support\Str::slug($old ?? '');
                                        if (empty($slug) || $slug === $oldSlug) {
                                            $set('slug', \Illuminate\Support\Str::slug($state ?? ''));
                                        }
                                    }
                                }),
                            TextInput::make('slug')
                                ->label('Slug')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),
                            TextInput::make('subtitle')
                                ->label('Sub Judul')
                                ->maxLength(255),
                            Textarea::make('excerpt')
                                ->label('Kutipan (Excerpt)')
                                ->maxLength(500)
                                ->columnSpanFull(),
                            RichEditor::make('content')
                                ->label('Isi Berita')
                                ->columnSpanFull(),
                        ]),

                        Section::make('SEO & Meta')->schema([
                            TextInput::make('seo_title')
                                ->label('SEO Title')
                                ->maxLength(255),
                            Textarea::make('meta_description')
                                ->label('Meta Description')
                                ->maxLength(320)
                                ->columnSpanFull(),
                        ]),
                    ])->columnSpan(['lg' => 2]),

                    Grid::make(1)->schema([
                        Section::make('Klasifikasi')->schema([
                            Select::make('category_id')
                                ->label('Kategori')
                                ->relationship(
                                    name: 'category', 
                                    titleAttribute: 'name', 
                                    modifyQueryUsing: fn ($query, ?\Illuminate\Database\Eloquent\Model $record) => 
                                        $query->where('is_active', true)
                                              ->when($record?->category_id, fn ($q, $id) => $q->orWhere('id', $id))
                                )
                                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('region_id')
                                ->label('Wilayah')
                                ->relationship(
                                    name: 'region', 
                                    titleAttribute: 'name', 
                                    modifyQueryUsing: fn ($query, ?\Illuminate\Database\Eloquent\Model $record) => 
                                        $query->where('is_active', true)
                                              ->when($record?->region_id, fn ($q, $id) => $q->orWhere('id', $id))
                                )
                                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                                ->searchable()
                                ->preload(),
                            Select::make('tags')
                                ->label('Tag')
                                ->relationship('tags', 'name')
                                ->multiple()
                                ->searchable()
                                ->preload(),
                        ]),
                    ])->columnSpan(['lg' => 1]),
                ]),
            ]);
    }
}
