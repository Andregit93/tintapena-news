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

class ManageContactSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-phone';

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Pengaturan Kontak';

    protected static ?string $title = 'Pengaturan Kontak';

    protected static ?string $slug = 'settings/contact';

    protected string $view = 'filament.pages.manage-contact-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $email = Setting::where('setting_key', 'contact.email')->first();
        $whatsapp = Setting::where('setting_key', 'contact.whatsapp')->first();
        $address = Setting::where('setting_key', 'contact.address')->first();
        $hours = Setting::where('setting_key', 'contact.hours')->first();

        $this->form->fill([
            'email' => $email ? $email->value : null,
            'whatsapp' => $whatsapp ? $whatsapp->value : null,
            'address' => $address ? $address->value : null,
            'hours' => $hours ? $hours->value : null,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Informasi Kontak Redaksi')
                    ->schema([
                        TextInput::make('email')
                            ->label('Email Redaksi')
                            ->email()
                            ->maxLength(255)
                            ->nullable(),
                        TextInput::make('whatsapp')
                            ->label('WhatsApp')
                            ->maxLength(255)
                            ->nullable(),
                        Textarea::make('address')
                            ->label('Alamat Redaksi')
                            ->maxLength(1000)
                            ->nullable(),
                        Textarea::make('hours')
                            ->label('Jam Kontak')
                            ->maxLength(255)
                            ->nullable(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->saveSetting('contact.email', $data['email']);
        $this->saveSetting('contact.whatsapp', $data['whatsapp']);
        $this->saveSetting('contact.address', $data['address']);
        $this->saveSetting('contact.hours', $data['hours']);

        Notification::make()
            ->success()
            ->title('Pengaturan kontak berhasil disimpan')
            ->send();
    }

    protected function saveSetting(string $key, ?string $value): void
    {
        $setting = Setting::where('setting_key', $key)->first() ?? new Setting;

        // As per documentation choice B: if blank, we update it as empty string
        // or just store null/blank since it's nullable strings
        $setting->setting_key = $key;
        $setting->group_name = 'contact';
        $setting->value = $value ?? '';
        $setting->value_type = 'string';
        $setting->save();
    }
}
