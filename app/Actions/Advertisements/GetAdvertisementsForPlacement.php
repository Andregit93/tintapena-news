<?php

namespace App\Actions\Advertisements;

use App\Models\Advertisement;
use Illuminate\Database\Eloquent\Collection;

class GetAdvertisementsForPlacement
{
    /**
     * Get advertisements for a specific placement key.
     *
     * @param string $placementKey
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function execute(string $placementKey): Collection
    {
        $allowedPlacements = [
            'homepage_top',
            'homepage_middle',
            'article_inline',
            'article_sidebar',
            'category_sidebar',
        ];

        if (! in_array($placementKey, $allowedPlacements, true)) {
            return new Collection();
        }

        return Advertisement::query()
            ->with('media')
            ->where('placement_key', $placementKey)
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }
}
