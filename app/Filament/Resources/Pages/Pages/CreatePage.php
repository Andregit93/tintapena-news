<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Enums\PageStatus;
use App\Filament\Resources\Pages\PageResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $modelClass = static::$resource::getModel();
        $record = new $modelClass($data);

        $record->created_by = Auth::id();
        $record->updated_by = Auth::id();

        if (isset($data['status'])) {
            $status = $data['status'] instanceof PageStatus ? $data['status'] : PageStatus::tryFrom($data['status']);
            $record->status = $status;
            if ($record->status === PageStatus::Published) {
                $record->published_at = now();
            }
        }

        $record->save();

        return $record;
    }
}
