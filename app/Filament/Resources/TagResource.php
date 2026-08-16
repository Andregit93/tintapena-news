<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TagResource\Pages;
use App\Models\Tag;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;
    protected static ?string $slug = 'tag';
    protected static ?string $modelLabel = 'Tag';
    protected static ?string $pluralModelLabel = 'Tag';

    public static function getNavigationIcon(): string|\Illuminate\View\ComponentAttributeBag|null
    {
        return 'heroicon-o-tag';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Konten';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
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
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(Tag::class, 'slug', ignoreRecord: true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('articles_count')
                    ->counts('articles')
                    ->label('Articles'),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make()
                    ->before(function (\Filament\Actions\DeleteAction $action, Tag $record) {
                        if ($record->articles()->count() > 0) {
                            Notification::make()
                                ->warning()
                                ->title('Cannot delete tag')
                                ->body('This tag is currently attached to one or more articles.')
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $deleted = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                if ($record->articles()->count() > 0) {
                                    $skipped++;
                                } else {
                                    $record->delete();
                                    $deleted++;
                                }
                            }

                            if ($skipped > 0) {
                                Notification::make()
                                    ->warning()
                                    ->title('Some tags were not deleted')
                                    ->body("{$skipped} tag(s) could not be deleted because they are attached to articles. {$deleted} tag(s) deleted.")
                                    ->send();
                            } else {
                                Notification::make()
                                    ->success()
                                    ->title('Tags deleted')
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTags::route('/'),
        ];
    }
}
