<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\MediaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Storage;

class ManageMedia extends ManageRecords
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): \Illuminate\Database\Eloquent\Model {
                    $record = new $model($data);
                    $record->uploaded_by = auth()->id();
                    $record->disk = 'public';

                    if (isset($data['path'])) {
                        $path = $data['path'];
                        $fullPath = Storage::disk('public')->path($path);

                        if (file_exists($fullPath)) {
                            $record->filename = basename($path);
                            $record->extension = pathinfo($fullPath, PATHINFO_EXTENSION);
                            $record->size = filesize($fullPath);
                            $record->mime_type = mime_content_type($fullPath);

                            // Extract dimensions for images
                            if (str_starts_with($record->mime_type, 'image/')) {
                                $sizeInfo = @getimagesize($fullPath);
                                if ($sizeInfo) {
                                    $record->width = $sizeInfo[0];
                                    $record->height = $sizeInfo[1];
                                }
                            }
                        }
                    }

                    $record->save();

                    return $record;
                }),
        ];
    }
}
