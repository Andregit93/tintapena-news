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

class ManageAnalyticsSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Pengaturan Analytics';

    protected static ?string $title = 'Pengaturan Analytics';

    protected static ?string $slug = 'settings/analytics';

    protected string $view = 'filament.pages.manage-analytics-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $measurementId = Setting::where('setting_key', 'analytics.google_measurement_id')->first();

        $this->form->fill([
            'google_measurement_id' => $measurementId ? $measurementId->value : null,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Google Analytics 4')
                    ->description('Konfigurasi pelacakan statistik pengunjung menggunakan Google Analytics 4.')
                    ->schema([
                        TextInput::make('google_measurement_id')
                            ->label('Google Measurement ID')
                            ->helperText('Masukkan Google Analytics 4 Measurement ID, contoh format G-XXXXXXXXXX.')
                            ->regex('/^G-[A-Z0-9]+$/')
                            ->maxLength(50)
                            ->nullable(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $measurementIdSetting = Setting::where('setting_key', 'analytics.google_measurement_id')->first() ?? new Setting;
        $measurementIdSetting->setting_key = 'analytics.google_measurement_id';
        $measurementIdSetting->group_name = 'analytics';
        $measurementIdSetting->value = $data['google_measurement_id'];
        $measurementIdSetting->value_type = 'string';
        $measurementIdSetting->save();

        Notification::make()
            ->success()
            ->title('Pengaturan Analytics berhasil disimpan')
            ->send();
    }
}
