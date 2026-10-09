<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $content = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /account/',
            'Disallow: /admin/',
            'Disallow: /orders',
            'Disallow: /*/orders',
            'Disallow: /favorites',
            'Disallow: /*/favorites',
            'Disallow: /search',
            'Disallow: /*/search',
            'Disallow: /location',
            'Disallow: /*load-more',
            'Sitemap: '.route('sitemap'),
            '',
        ]);

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
