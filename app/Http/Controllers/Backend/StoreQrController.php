<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\ShopQrPoster;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** QR-код магазина для печати: ведёт покупателя на публичную страницу магазина, оформлен в стиле BIMM. */
class StoreQrController extends Controller
{
    public function __construct(private readonly ShopQrPoster $poster) {}

    public function index(Request $request)
    {
        $seller = $request->user()->seller;

        if ($seller === null) {
            return redirect()->route('account.store.create')
                ->with('error', 'QR-код доступен после создания магазина.');
        }

        return view('backend.cabinet.store.qr', [
            'user' => $request->user(),
            'seller' => $seller,
            'shopUrl' => $this->poster->url($seller),
            'displayUrl' => $this->poster->displayUrl($seller),
            'layouts' => [
                ShopQrPoster::POSTER => [
                    'title' => 'Плакат А4',
                    'hint' => 'Для витрины, двери или прилавка. Печатается на обычном листе А4.',
                    'svg' => $this->poster->inlineSvg($seller, ShopQrPoster::POSTER),
                    'filename' => $this->poster->filename($seller, ShopQrPoster::POSTER, 'png'),
                    'width' => 1000,
                    'height' => 1414,
                ],
                ShopQrPoster::STICKER => [
                    'title' => 'Наклейка',
                    'hint' => 'Компактный вариант для упаковки, чеков и визиток. Можно печатать мелко.',
                    'svg' => $this->poster->inlineSvg($seller, ShopQrPoster::STICKER),
                    'filename' => $this->poster->filename($seller, ShopQrPoster::STICKER, 'png'),
                    'width' => 900,
                    'height' => 1000,
                ],
            ],
        ]);
    }

    public function download(Request $request, string $layout): Response
    {
        $seller = $request->user()->seller;

        if ($seller === null) {
            return redirect()->route('account.store.create')
                ->with('error', 'QR-код доступен после создания магазина.');
        }

        return response($this->poster->svg($seller, $layout), 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$this->poster->filename($seller, $layout, 'svg').'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
