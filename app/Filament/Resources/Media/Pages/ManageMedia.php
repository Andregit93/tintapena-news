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
                ->mutateFormDataUsing(function (array $data): array {
                    $data['uploaded_by'] = auth()->id();
                    $data['disk'] = 'public';

                    // Ensure we don't allow arbitrary uploaded_by from client
                    if (isset($data['uploaded_by']) && $data['uploaded_by'] !== auth()->id()) {
                        $data['uploaded_by'] = auth()->id();
                    }

                    if (isset($data['path'])) {
                        $path = $data['path'];
                        $fullPath = Storage::disk('public')->path($path);

                        if (file_exists($fullPath)) {
                            $data['filename'] = basename($path);
                            $data['extension'] = pathinfo($fullPath, PATHINFO_EXTENSION);
                            $data['size'] = filesize($fullPath);
                            $data['mime_type'] = mime_content_type($fullPath);

                            // Extract dimensions for images
                            if (str_starts_with($data['mime_type'], 'image/')) {
                                $sizeInfo = @getimagesize($fullPath);
                                if ($sizeInfo) {
                                    $data['width'] = $sizeInfo[0];
                                    $data['height'] = $sizeInfo[1];
                                }
                            }
                        }
                    }

                    return $data;
                }),
        ];
    }
}
