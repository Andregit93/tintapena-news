<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManageGeneralSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Pengaturan Umum';

    protected static ?string $title = 'Pengaturan Umum';

    protected static ?string $slug = 'settings/general';

    protected string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $siteName = Setting::where('setting_key', 'general.site_name')->first();
        $tagline = Setting::where('setting_key', 'general.tagline')->first();

        $this->form->fill([
            'site_name' => $siteName ? $siteName->value : null,
            'tagline' => $tagline ? $tagline->value : null,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Identitas Website')
                    ->schema([
                        TextInput::make('site_name')
                            ->label('Nama Website')
                            ->required()
                            ->maxLength(150),
                        TextInput::make('tagline')
                            ->label('Tagline')
                            ->maxLength(255),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $siteNameSetting = Setting::where('setting_key', 'general.site_name')->first() ?? new Setting;
        $siteNameSetting->setting_key = 'general.site_name';
        $siteNameSetting->group_name = 'general';
        $siteNameSetting->value = $data['site_name'];
        $siteNameSetting->value_type = 'string';
        $siteNameSetting->save();

        $taglineSetting = Setting::where('setting_key', 'general.tagline')->first() ?? new Setting;
        $taglineSetting->setting_key = 'general.tagline';
        $taglineSetting->group_name = 'general';
        $taglineSetting->value = $data['tagline'];
        $taglineSetting->value_type = 'string';
        $taglineSetting->save();

        Notification::make()
            ->success()
            ->title('Pengaturan berhasil disimpan')
            ->send();
    }
}
