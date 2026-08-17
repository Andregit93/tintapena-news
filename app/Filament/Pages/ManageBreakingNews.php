<?php

namespace App\Filament\Pages;

use App\Actions\BreakingNews\ActivateBreakingNews;
use App\Models\Article;
use App\Models\BreakingNews;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ManageBreakingNews extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationLabel = 'Breaking News';
    protected static ?string $title = 'Manage Breaking News';
    protected static ?string $slug = 'breaking-news';

    protected string $view = 'filament.pages.manage-breaking-news';

    public function table(Table $table): Table
    {
        return $table
            ->query(BreakingNews::query()->with(['article', 'creator'])->orderByDesc('created_at')->orderByDesc('id'))
            ->columns([
                TextColumn::make('source_type')
                    ->label('Type')
                    ->state(fn (BreakingNews $record) => $record->article_id ? 'Internal' : 'Manual')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Internal' => 'primary',
                        'Manual' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('content')
                    ->label('Headline')
                    ->state(function (BreakingNews $record) {
                        return $record->article_id ? ($record->article->title ?? 'Unknown Article') : $record->headline;
                    })
                    ->description(function (BreakingNews $record) {
                        return $record->article_id ? 'Target: Internal Article' : $record->target_url;
                    })
                    ->limit(50),
                TextColumn::make('starts_at')
                    ->dateTime('d M Y, H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('ends_at')
                    ->dateTime('d M Y, H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable()
                    ->placeholder('-'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('creator.name')
                    ->label('Created By'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->form($this->getFormSchema())
                    ->using(function (array $data) {
                        $normalizedData = $this->normalizeAndValidateData($data);
                        
                        $record = new BreakingNews();
                        $record->fill($normalizedData);
                        $record->created_by = Auth::id();
                        $record->save();
                        
                        return $record;
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->form($this->getFormSchema())
                    ->mutateRecordDataUsing(function (array $data): array {
                        $data['source_type'] = isset($data['article_id']) ? 'internal' : 'manual';
                        return $data;
                    })
                    ->using(function (BreakingNews $record, array $data) {
                        $normalizedData = $this->normalizeAndValidateData($data);
                        $record->fill($normalizedData);
                        $record->save();
                        return $record;
                    }),
                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (BreakingNews $record) => !$record->is_active)
                    ->action(function (BreakingNews $record) {
                        try {
                            app(ActivateBreakingNews::class)->execute($record);
                            Notification::make()->success()->title('Activated successfully')->send();
                        } catch (ValidationException $e) {
                            Notification::make()->danger()->title('Validation Failed')->body(collect($e->errors())->flatten()->first())->send();
                        }
                    }),
                Action::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->visible(fn (BreakingNews $record) => $record->is_active)
                    ->action(function (BreakingNews $record) {
                        $record->is_active = false;
                        $record->save();
                        Notification::make()->success()->title('Deactivated successfully')->send();
                    }),
            ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('source_type')
                ->options([
                    'internal' => 'Internal Article',
                    'manual' => 'Manual URL',
                ])
                ->reactive()
                ->required(),
            Select::make('article_id')
                ->label('Article')
                ->options(function () {
                    return Article::published()
                        ->orderBy('published_at', 'desc')
                        ->limit(100)
                        ->pluck('title', 'id');
                })
                ->searchable()
                ->getSearchResultsUsing(function (string $search) {
                    return Article::published()
                        ->where('title', 'like', "%{$search}%")
                        ->orderBy('published_at', 'desc')
                        ->limit(50)
                        ->pluck('title', 'id');
                })
                ->getOptionLabelUsing(function ($value) {
                    $article = Article::with('category')->find($value);
                    if (!$article) return '';
                    $cat = $article->category ? $article->category->name : 'No Category';
                    $date = $article->published_at ? $article->published_at->timezone('Asia/Jakarta')->format('d M Y') : '';
                    return "{$article->title} - {$cat} ({$date})";
                })
                ->visible(fn (Get $get) => $get('source_type') === 'internal')
                ->required(fn (Get $get) => $get('source_type') === 'internal'),
            TextInput::make('headline')
                ->maxLength(255)
                ->visible(fn (Get $get) => $get('source_type') === 'manual')
                ->required(fn (Get $get) => $get('source_type') === 'manual'),
            TextInput::make('target_url')
                ->maxLength(500)
                ->url()
                ->visible(fn (Get $get) => $get('source_type') === 'manual')
                ->required(fn (Get $get) => $get('source_type') === 'manual'),
            DateTimePicker::make('starts_at')
                ->timezone('Asia/Jakarta')
                ->nullable(),
            DateTimePicker::make('ends_at')
                ->timezone('Asia/Jakarta')
                ->after('starts_at')
                ->nullable(),
            Toggle::make('is_active')
                ->default(false),
        ];
    }

    protected function normalizeAndValidateData(array $data): array
    {
        $normalizedData = [];
        $normalizedData['starts_at'] = $data['starts_at'] ?? null;
        $normalizedData['ends_at'] = $data['ends_at'] ?? null;
        $normalizedData['is_active'] = $data['is_active'] ?? false;

        $sourceType = $data['source_type'] ?? null;
        
        if (! in_array($sourceType, ['internal', 'manual'], true)) {
            throw ValidationException::withMessages([
                'source_type' => 'A valid source type is required.',
            ]);
        }

        if ($sourceType === 'internal') {
            $articleId = $data['article_id'] ?? null;
            if (!$articleId || !Article::published()->whereKey($articleId)->exists()) {
                throw ValidationException::withMessages([
                    'article_id' => 'The selected article must be currently published.'
                ]);
            }
            $normalizedData['article_id'] = $articleId;
            $normalizedData['headline'] = null;
            $normalizedData['target_url'] = null;
        } elseif ($sourceType === 'manual') {
            $headline = $data['headline'] ?? null;
            $targetUrl = $data['target_url'] ?? null;
            
            if (empty($headline)) {
                throw ValidationException::withMessages([
                    'headline' => 'Headline is required for manual breaking news.'
                ]);
            }
            
            if (empty($targetUrl) || filter_var($targetUrl, FILTER_VALIDATE_URL) === false) {
                throw ValidationException::withMessages([
                    'target_url' => 'A valid URL is required.'
                ]);
            }
            
            $scheme = parse_url($targetUrl, PHP_URL_SCHEME);
            if (!in_array(strtolower((string) $scheme), ['http', 'https'])) {
                throw ValidationException::withMessages([
                    'target_url' => 'A valid HTTP or HTTPS URL is required.'
                ]);
            }
            
            $normalizedData['article_id'] = null;
            $normalizedData['headline'] = $headline;
            $normalizedData['target_url'] = $targetUrl;
        }

        return $normalizedData;
    }
}
