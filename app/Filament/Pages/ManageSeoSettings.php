<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManageSeoSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Pengaturan SEO';

    protected static ?string $title = 'Pengaturan SEO';

    protected static ?string $slug = 'settings/seo';

    protected string $view = 'filament.pages.manage-seo-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $defaultTitle = Setting::where('setting_key', 'seo.default_title')->first();
        $defaultDescription = Setting::where('setting_key', 'seo.default_description')->first();

        $this->form->fill([
            'default_title' => $defaultTitle ? $defaultTitle->value : null,
            'default_description' => $defaultDescription ? $defaultDescription->value : null,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('SEO Default (Fallback)')
                    ->description('Nilai ini akan digunakan jika halaman tidak memiliki pengaturan SEO spesifik.')
                    ->schema([
                        TextInput::make('default_title')
                            ->label('Default SEO Title')
                            ->maxLength(255)
                            ->nullable(),
                        Textarea::make('default_description')
                            ->label('Default Meta Description')
                            ->maxLength(320)
                            ->nullable(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $titleSetting = Setting::where('setting_key', 'seo.default_title')->first() ?? new Setting;
        $titleSetting->setting_key = 'seo.default_title';
        $titleSetting->group_name = 'seo';
        $titleSetting->value = $data['default_title'];
        $titleSetting->value_type = 'string';
        $titleSetting->save();

        $descriptionSetting = Setting::where('setting_key', 'seo.default_description')->first() ?? new Setting;
        $descriptionSetting->setting_key = 'seo.default_description';
        $descriptionSetting->group_name = 'seo';
        $descriptionSetting->value = $data['default_description'];
        $descriptionSetting->value_type = 'string';
        $descriptionSetting->save();

        Notification::make()
            ->success()
            ->title('Pengaturan SEO berhasil disimpan')
            ->send();
    }
}
