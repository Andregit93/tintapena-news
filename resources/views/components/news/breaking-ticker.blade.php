@props(['breakingNews'])

@if($breakingNews->isNotEmpty())
    <div class="bg-[#E53935] text-white py-2 px-4 mb-8 md:mb-10 rounded-lg flex flex-col md:flex-row items-start md:items-center w-full shadow-sm text-sm overflow-hidden">
        <span class="font-bold tracking-widest uppercase md:mr-4 shrink-0 bg-white text-[#E53935] px-2 py-1 rounded text-[10px] mb-2 md:mb-0">Breaking News</span>
        <div class="flex-1 overflow-x-auto whitespace-nowrap scrollbar-hide w-full flex items-center">
            <div class="flex items-center gap-6">
                @foreach($breakingNews as $item)
                    @php
                        $headline = $item->article_id ? ($item->article->title ?? 'Untitled') : $item->headline;
                        $link = $item->article_id ? route('articles.show', $item->article) : $item->target_url;
                        
                        $isSafeUrl = false;
                        if (!$item->article_id) {
                            $parsed = parse_url($link);
                            if (isset($parsed['scheme']) && in_array(strtolower($parsed['scheme']), ['http', 'https'])) {
                                $isSafeUrl = true;
                            }
                        } else {
                            $isSafeUrl = true;
                        }
                    @endphp
                    @if($isSafeUrl)
                        <a 
                            href="{{ $link }}" 
                            class="hover:underline" 
                            @if(!$item->article_id)
                                target="_blank" 
                                rel="noopener noreferrer"
                            @endif
                        >
                            {{ $headline }}
                        </a>
                        @if(!$loop->last)
                            <span class="opacity-50 mx-2 text-xs">•</span>
                        @endif
                    @endif
                @endforeach
            </div>
        </div>
    </div>
@endif
