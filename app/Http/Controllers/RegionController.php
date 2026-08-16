<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Region;

class RegionController extends Controller
{
    /**
     * Display the specified region and its published articles.
     */
    public function show(Region $region)
    {
        $articles = Article::published()
            ->where('region_id', $region->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->with(['category', 'region', 'featuredMedia'])
            ->paginate(10);

        // Fetch all regions for the navigation row
        $regions = Region::orderBy('name')->get();

        return view('regions.show', [
            'region' => $region,
            'regions' => $regions,
            'articles' => $articles,
        ]);
    }
}
