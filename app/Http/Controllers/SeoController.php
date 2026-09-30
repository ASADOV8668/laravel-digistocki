<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\SellerStore;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => route('listings.index'), 'changefreq' => 'hourly', 'priority' => '0.9'],
            ['loc' => route('support.index'), 'changefreq' => 'monthly', 'priority' => '0.5'],
        ])->merge(
            Listing::query()
                ->published()
                ->select(['id', 'slug', 'updated_at'])
                ->latest('published_at')
                ->get()
                ->map(fn (Listing $listing) => [
                    'loc' => route('listings.show', ['listing' => $listing->slug]),
                    'lastmod' => $listing->updated_at?->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ])
        )->merge(
            SellerStore::query()
                ->where('is_enabled', true)
                ->where('is_admin_disabled', false)
                ->select(['slug', 'updated_at'])
                ->latest('updated_at')
                ->get()
                ->map(fn (SellerStore $store) => [
                    'loc' => route('storefront.show', ['sellerStore' => $store->slug]),
                    'lastmod' => $store->updated_at?->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ])
        );

        return response()
            ->view('seo.sitemap', compact('urls'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $body = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /dashboard',
            'Disallow: /profile',
            'Disallow: /notifications',
            'Disallow: /favorites',
            'Disallow: /listings/create',
            'Sitemap: '.url('/sitemap.xml'),
        ])."\n";

        return response($body)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
