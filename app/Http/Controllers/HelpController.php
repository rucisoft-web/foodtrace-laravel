<?php

namespace App\Http\Controllers;

class HelpController extends Controller
{
    public static function topic(string $key): array
    {
        $t = config('bantuan.topik.'.$key) ?? config('bantuan.topik.umum');
        $t['gambar'] ??= (config('bantuan.topik.'.$key) ? $key : 'umum').'.jpg';

        return $t;
    }

    /** Topik bantuan untuk layar yang sedang dibuka (berdasarkan nama route). */
    public static function topicForCurrent(): string
    {
        $route = request()->route();
        $name = (string) $route?->getName();
        if ($name === 'reports.show') {
            return 'laporan-'.$route->parameter('report');
        }
        if ($name === 'partners.index') {
            return $route->parameter('kind') === 'source' ? 'source' : 'destination';
        }
        if ($name === 'help.show') {
            return (string) $route->parameter('key');
        }
        if (str_starts_with($name, 'butcher.')) {
            $kind = request('kind') ?? optional($route->parameter('butcher'))->kind;

            return $kind === 'in' ? 'butcher-in' : 'butcher-out';
        }
        foreach (config('bantuan.rute', []) as $pattern => $key) {
            if (\Illuminate\Support\Str::is($pattern, $name)) {
                return config('bantuan.topik.'.$key) ? $key : 'umum';
            }
        }

        return 'umum';
    }

    public function index()
    {
        return view('help.index', ['groups' => config('bantuan.kelompok'), 'concepts' => config('bantuan.konsep')]);
    }

    public function show(string $key)
    {
        abort_unless(config('bantuan.topik.'.$key), 404);

        return view('help.show', ['key' => $key, 't' => self::topic($key)]);
    }

    public function implementation()
    {
        return view('help.implementation', config('implementasi'));
    }
}
