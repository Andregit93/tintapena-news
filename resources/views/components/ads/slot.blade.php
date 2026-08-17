@if($advertisements->isNotEmpty())
    <div data-ad-slot="{{ $position }}" class="w-full my-6 text-center flex flex-col items-center justify-center gap-4">
        @foreach($advertisements as $ad)
            <div data-ad-id="{{ $ad->id }}" data-ad-type="{{ $ad->type->value }}" class="max-w-full">
                @if($ad->type === \App\Enums\AdvertisementType::Image)
                    @include('components.ads.image', ['ad' => $ad])
                @elseif($ad->type === \App\Enums\AdvertisementType::Script)
                    @include('components.ads.script', ['ad' => $ad])
                @endif
            </div>
        @endforeach
    </div>
@endif