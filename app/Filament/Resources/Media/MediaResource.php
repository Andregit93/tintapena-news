<?php

namespace App\Filament\Resources\Media;

use App\Filament\Resources\Media\Pages\ManageMedia;
use App\Models\Media;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return Heroicon::OutlinedPhoto;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Konten';
    }

    public static function getNavigationLabel(): string
    {
        return 'Media';
    }

    public static function getModelLabel(): string
    {
        return 'Media';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Media';
    }

    public static function getSlug(?\Filament\Panel $panel = null): string
    {
        return 'media';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)->schema([
                    FileUpload::make('path')
                        ->label('Upload File')
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(5120)
                        ->disk('public')
                        ->directory('media')
                        ->visibility('public')
                        ->storeFileNamesIn('original_filename')
                        ->getUploadedFileNameForStorageUsing(
                            fn (TemporaryUploadedFile $file): string => (string) Str::uuid() . '.' . $file->getClientOriginalExtension(),
                        )
                        ->required()
                        ->hiddenOn('edit'),

                    Section::make('Metadata')->schema([
                        TextInput::make('alt_text')
                            ->label('Alt Text')
                            ->maxLength(255),
                        TextInput::make('caption')
                            ->label('Caption')
                            ->maxLength(255),
                        TextInput::make('photo_credit')
                            ->label('Kredit Foto')
                            ->maxLength(255),
                    ])->visibleOn('edit'),
                    
                    Section::make('Info File')->schema([
                        Placeholder::make('original_filename_display')
                            ->label('Nama File Asli')
                            ->content(fn ($record) => $record?->original_filename),
                        Placeholder::make('dimensions')
                            ->label('Dimensi')
                            ->content(fn ($record) => $record ? "{$record->width} x {$record->height} px" : '-'),
                        Placeholder::make('size_display')
                            ->label('Ukuran')
                            ->content(fn ($record) => $record ? number_format($record->size / 1024, 2) . ' KB' : '-'),
                    ])->visibleOn('edit'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('path')
                    ->label('Preview')
                    ->disk('public')
                    ->square(),
                TextColumn::make('original_filename')
                    ->label('File')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Media $record): string => $record->filename ?? ''),
                TextColumn::make('dimensions')
                    ->label('Dimensi')
                    ->state(fn (Media $record): string => "{$record->width}x{$record->height}"),
                TextColumn::make('size')
                    ->label('Ukuran')
                    ->formatStateUsing(fn ($state) => number_format($state / 1024, 2) . ' KB')
                    ->sortable(),
                TextColumn::make('uploader.name')
                    ->label('Pengunggah')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Diunggah Pada')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Prevent tampering with these fields on edit
                        unset($data['uploaded_by']);
                        unset($data['disk']);
                        unset($data['path']);
                        unset($data['filename']);
                        unset($data['original_filename']);
                        unset($data['mime_type']);
                        unset($data['extension']);
                        unset($data['size']);
                        unset($data['width']);
                        unset($data['height']);
                        return $data;
                    }),
            ])
            ->bulkActions([
                // No delete bulk action
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageMedia::route('/'),
        ];
    }
}
