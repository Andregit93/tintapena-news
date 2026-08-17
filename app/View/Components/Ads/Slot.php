<?php

namespace App\View\Components\Ads;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Actions\Advertisements\GetAdvertisementsForPlacement;

class Slot extends Component
{
    public $position;
    public $advertisements;

    /**
     * Create a new component instance.
     */
    public function __construct(string $position, GetAdvertisementsForPlacement $getAdvertisementsForPlacement)
    {
        $this->position = $position;
        $this->advertisements = $getAdvertisementsForPlacement->execute($position)
            ->filter(function ($ad) {
                if ($ad->type === \App\Enums\AdvertisementType::Image) {
                    return $ad->media !== null;
                }
                if ($ad->type === \App\Enums\AdvertisementType::Script) {
                    return !empty(trim((string)$ad->content));
                }
                return false;
            })
            ->values();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ads.slot');
    }
}
