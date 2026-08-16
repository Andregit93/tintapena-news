<x-filament-panels::page>
    <x-filament::section>
        <div class="prose max-w-none">
            <h1 class="text-3xl font-bold mb-2">{{ $record->title }}</h1>
            @if($record->subtitle)
                <h2 class="text-xl text-gray-600 mb-4">{{ $record->subtitle }}</h2>
            @endif
            
            <div class="flex items-center text-sm text-gray-500 space-x-4 mb-6">
                @if($record->author)
                    <span>Author: {{ $record->author->name }}</span>
                @endif
                @if($record->category)
                    <span>Category: {{ $record->category->name }}</span>
                @endif
                @if($record->region)
                    <span>Region: {{ $record->region->name }}</span>
                @endif
                <span>Status: 
                    <x-filament::badge color="primary">
                        {{ ucfirst($record->status->value) }}
                    </x-filament::badge>
                </span>
            </div>

            @if($record->featuredMedia)
                <div class="mb-6">
                    <img src="{{ Storage::url($record->featuredMedia->path) }}" alt="{{ $record->featuredMedia->alt_text ?? $record->title }}" class="w-full max-w-3xl rounded-lg shadow-sm">
                    @if($record->featuredMedia->caption || $record->featuredMedia->photo_credit)
                        <p class="text-sm text-gray-500 mt-2">
                            {{ $record->featuredMedia->caption }}
                            @if($record->featuredMedia->photo_credit)
                                (Photo: {{ $record->featuredMedia->photo_credit }})
                            @endif
                        </p>
                    @endif
                </div>
            @endif

            <div class="mt-8">
                {!! str($record->content)->sanitizeHtml() !!}
            </div>

            @if($record->tags->count() > 0)
                <div class="mt-8 pt-4 border-t">
                    <h3 class="text-sm font-semibold mb-2">Tags:</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($record->tags as $tag)
                            <x-filament::badge color="info">{{ $tag->name }}</x-filament::badge>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-panels::page>
