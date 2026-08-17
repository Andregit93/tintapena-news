<?php

namespace App\Filament\Resources\Advertisements;

use App\Filament\Resources\Advertisements\Pages\CreateAdvertisement;
use App\Filament\Resources\Advertisements\Pages\EditAdvertisement;
use App\Filament\Resources\Advertisements\Pages\ListAdvertisements;
use App\Filament\Resources\Advertisements\Schemas\AdvertisementForm;
use App\Filament\Resources\Advertisements\Tables\AdvertisementsTable;
use App\Models\Advertisement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AdvertisementResource extends Resource
{
    protected static ?string $model = Advertisement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static \UnitEnum|string|null $navigationGroup = 'Monetisasi';
    protected static ?string $modelLabel = 'Iklan';
    protected static ?string $pluralModelLabel = 'Iklan';

    public static function getSlug(?\Filament\Panel $panel = null): string
    {
        return 'iklan';
    }

    public static function form(Schema $schema): Schema
    {
        return AdvertisementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdvertisementsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdvertisements::route('/'),
            'create' => CreateAdvertisement::route('/create'),
            'edit' => EditAdvertisement::route('/{record}/edit'),
        ];
    }

    public static function normalizeAdvertisementData(array $data): array
    {
        if (! isset($data['type'])) {
            throw new \InvalidArgumentException('Advertisement type is required.');
        }

        $type = \App\Enums\AdvertisementType::tryFrom($data['type']);
        if (! $type) {
            throw new \InvalidArgumentException('Unknown advertisement type.');
        }

        if ($type === \App\Enums\AdvertisementType::Image) {
            $data['content'] = null;
            if (empty($data['media_id'])) {
                throw new \InvalidArgumentException('Media ID is required for image advertisements.');
            }
        } elseif ($type === \App\Enums\AdvertisementType::Script) {
            $data['media_id'] = null;
            $content = trim($data['content'] ?? '');
            if ($content === '') {
                throw new \InvalidArgumentException('Content is required for script advertisements.');
            }
            // Do not sanitize, just assign original
        }

        if (array_key_exists('target_url', $data)) {
            $targetUrl = trim($data['target_url'] ?? '');
            $data['target_url'] = $targetUrl === '' ? null : $targetUrl;
        }

        return $data;
    }
}
