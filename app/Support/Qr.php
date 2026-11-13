<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/** QR code sebagai SVG (tanpa ekstensi GD/Imagick), dipakai label LPN, label lokasi dan surat jalan (POD). */
class Qr
{
    private static array $cache = [];

    public static function svg(string $text, int $size = 160): string
    {
        $key = $size.'|'.$text;
        if (! isset(self::$cache[$key])) {
            $w = new Writer(new ImageRenderer(new RendererStyle($size, 0), new SvgImageBackEnd()));
            self::$cache[$key] = preg_replace('/^<\?xml[^>]*>\s*/', '', $w->writeString($text));
        }

        return self::$cache[$key];
    }

    /** Data URI untuk <img src>, praktis bila QR yang sama dipakai berulang (stiker karton). */
    public static function uri(string $text, int $size = 160): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode(self::svg($text, $size));
    }
}
