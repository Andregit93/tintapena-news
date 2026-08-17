<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Enums\PageStatus;
use App\Filament\Resources\Pages\PageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->fill($data);
        $record->updated_by = Auth::id();

        if (isset($data['status'])) {
            $newStatus = $data['status'] instanceof PageStatus ? $data['status'] : PageStatus::tryFrom($data['status']);

            // Only set published_at if transitioning to Published from something else, and it's null
            if ($newStatus === PageStatus::Published && $record->status !== PageStatus::Published && is_null($record->published_at)) {
                $record->published_at = now();
            }
            $record->status = $newStatus;
        }

        $record->save();

        return $record;
    }
}
