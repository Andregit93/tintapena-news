<?php

namespace App\Filament\Resources\Advertisements\Pages;

use App\Filament\Resources\Advertisements\AdvertisementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdvertisement extends CreateRecord
{
    protected static string $resource = AdvertisementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return AdvertisementResource::normalizeAdvertisementData($data);
    }
}
