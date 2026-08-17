<?php

namespace App\Filament\Pages;

use App\Models\Article;
use App\Models\HomepageSlot;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ManageHomepage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Homepage';
    protected static ?string $title = 'Homepage Manager';
    protected static ?string $slug = 'homepage';
    
    protected string $view = 'filament.pages.manage-homepage';

    public ?array $data = [];

    public function mount(): void
    {
        $slots = HomepageSlot::all();
        $initialData = [];
        foreach ($slots as $slot) {
            $initialData[$slot->slot_key . '_article_id'] = $slot->article_id;
            $initialData[$slot->slot_key . '_is_active'] = $slot->is_active;
        }

        $this->form->fill($initialData);
    }

    public function form(Schema $form): Schema
    {
        $slots = HomepageSlot::orderBy('sort_order')->get();

        $schema = [];

        foreach ($slots as $slot) {
            $schema[] = Section::make($slot->slot_name)
                ->schema([
                    Select::make($slot->slot_key . '_article_id')
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
                            $date = $article->published_at ? $article->published_at->format('d M Y') : '';
                            return "{$article->title} - {$cat} ({$date})";
                        })
                        ->nullable(),
                    Toggle::make($slot->slot_key . '_is_active')
                        ->label('Active')
                        ->default(true),
                ])
                ->collapsed(false);
        }

        return $form
            ->schema($schema)
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $slots = HomepageSlot::all();
        
        $selectedArticles = [];
        
        foreach ($slots as $slot) {
            $articleId = $data[$slot->slot_key . '_article_id'] ?? null;
            $isActive = $data[$slot->slot_key . '_is_active'] ?? false;
            
            if ($articleId) {
                if (!Article::published()->whereKey($articleId)->exists()) {
                    throw ValidationException::withMessages([
                        'data.' . $slot->slot_key . '_article_id' => 'The selected article must be currently published.',
                    ]);
                }

                if ($isActive) {
                    if (in_array($articleId, $selectedArticles)) {
                        throw ValidationException::withMessages([
                            'data.' . $slot->slot_key . '_article_id' => 'This article is already selected in another active slot.',
                        ]);
                    }
                    $selectedArticles[] = $articleId;
                }
            }
        }

        foreach ($slots as $slot) {
            $slot->article_id = $data[$slot->slot_key . '_article_id'] ?? null;
            $slot->is_active = $data[$slot->slot_key . '_is_active'] ?? false;
            $slot->updated_by = Auth::id();
            $slot->save();
        }

        Notification::make()
            ->success()
            ->title('Homepage slots updated successfully')
            ->send();
    }
}
