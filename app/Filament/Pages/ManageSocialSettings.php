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

class ManageSocialSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-share';

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Pengaturan Media Sosial';

    protected static ?string $title = 'Pengaturan Media Sosial';

    protected static ?string $slug = 'settings/social';

    protected string $view = 'filament.pages.manage-social-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $instagram = Setting::where('setting_key', 'social.instagram')->first();
        $facebook = Setting::where('setting_key', 'social.facebook')->first();

        $this->form->fill([
            'instagram' => $instagram ? $instagram->value : null,
            'facebook' => $facebook ? $facebook->value : null,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Akun Media Sosial')
                    ->schema([
                        TextInput::make('instagram')
                            ->label('Instagram')
                            ->helperText('Masukkan URL profil lengkap.')
                            ->url()
                            ->rules(['url:http,https'])
                            ->maxLength(255)
                            ->nullable(),
                        TextInput::make('facebook')
                            ->label('Facebook')
                            ->helperText('Masukkan URL profil lengkap.')
                            ->url()
                            ->rules(['url:http,https'])
                            ->maxLength(255)
                            ->nullable(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->saveSetting('social.instagram', $data['instagram']);
        $this->saveSetting('social.facebook', $data['facebook']);

        Notification::make()
            ->success()
            ->title('Pengaturan media sosial berhasil disimpan')
            ->send();
    }

    protected function saveSetting(string $key, ?string $value): void
    {
        $setting = Setting::where('setting_key', $key)->first() ?? new Setting;

        $setting->setting_key = $key;
        $setting->group_name = 'social';
        $setting->value = $value ?? '';
        $setting->value_type = 'string';
        $setting->save();
    }
}
