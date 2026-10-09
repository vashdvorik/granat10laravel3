<?php

namespace App\Http\Controllers;

use App\Services\SitemapBuilder;
use Illuminate\Http\Response;

/**
 * /sitemap.xml — карта сайта. Если страниц больше лимита одного файла (по умолчанию 40 000), отдаёт индекс
 * со ссылками на /sitemap-1.xml, /sitemap-2.xml ...
 */
class SitemapController extends Controller
{
    public function __invoke(SitemapBuilder $builder, ?int $page = null): Response
    {
        $entries = $builder->entries();
        $chunk = max(1, (int) config('seo.sitemap_chunk', 40000));
        $pages = (int) ceil(count($entries) / $chunk);

        if ($page === null && $pages <= 1) {
            return $this->xml('sitemap', ['entries' => $entries]);
        }

        if ($page === null) {
            return $this->xml('sitemap-index', [
                'sitemaps' => array_map(
                    fn (int $number): string => route('sitemap.page', ['page' => $number]),
                    range(1, $pages)
                ),
            ]);
        }

        abort_if($page < 1 || $page > max(1, $pages), 404);

        return $this->xml('sitemap', ['entries' => array_slice($entries, ($page - 1) * $chunk, $chunk)]);
    }

    /** @param  array<string, mixed>  $data */
    private function xml(string $view, array $data): Response
    {
        return response()
            ->view($view, $data)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
