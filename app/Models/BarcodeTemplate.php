<?php

namespace App\Models;

use App\Support\WmsException;
use Illuminate\Database\Eloquent\Model;

/** Format barcode karton per item: GS1-128 atau pola regex dengan grup bernama (weight, lot, expiry, carton). */
class BarcodeTemplate extends Model
{
    protected $guarded = [];

    protected $casts = ['active' => 'bool', 'min_weight' => 'float', 'max_weight' => 'float'];

    public const LB_TO_KG = 0.45359237;
    private const GS = "\x1d";
    private const GS1_FIXED = ['00' => 18, '01' => 14, '02' => 14, '11' => 6, '13' => 6, '15' => 6, '17' => 6];
    private const GS1_VARIABLE = ['10', '21', '37'];

    public function items() { return $this->belongsToMany(Item::class); }

    public static function gs1Date(string $t): string
    {
        $yy = (int) substr($t, 0, 2); $mm = (int) substr($t, 2, 2); $dd = (int) substr($t, 4, 2);

        return sprintf('%04d-%02d-%02d', 2000 + $yy, $mm, $dd ?: 28);
    }

    /** Urai GS1-128 dalam bentuk "(01)..(3102).." atau string mentah dengan pemisah FNC1. */
    public static function parseGs1(string $raw): array
    {
        $out = [];
        $text = trim($raw);
        if (str_starts_with($text, '(')) {
            preg_match_all('/\((\d{2,4})\)([^(]*)/', $text, $m, PREG_SET_ORDER);
            foreach ($m as $g) {
                $out[$g[1]] = $g[2];
            }

            return $out;
        }
        $text = str_replace(']C1', '', $text);
        $i = 0;
        $n = strlen($text);
        while ($i < $n) {
            if ($text[$i] === self::GS) { $i++; continue; }
            $ai2 = substr($text, $i, 2);
            if (isset(self::GS1_FIXED[$ai2])) {
                $out[$ai2] = substr($text, $i + 2, self::GS1_FIXED[$ai2]);
                $i += 2 + self::GS1_FIXED[$ai2];
            } elseif (in_array(substr($text, $i, 3), ['310', '320'])) {
                $out[substr($text, $i, 4)] = substr($text, $i + 4, 6);
                $i += 10;
            } elseif (in_array($ai2, self::GS1_VARIABLE)) {
                $end = strpos($text, self::GS, $i + 2);
                $end = $end === false ? $n : $end;
                $out[$ai2] = substr($text, $i + 2, $end - $i - 2);
                $i = $end;
            } else {
                throw new \InvalidArgumentException('AI GS1 tidak dikenal di posisi '.$i.': '.substr($text, $i, 4));
            }
        }

        return $out;
    }

    /** Kembalikan [weight_kg, weight_lb, lot, expiry, carton] dari satu scan. */
    public function parse(string $raw): array
    {
        try {
            $data = $this->kind === 'gs1' ? $this->parseGs1Template($raw) : $this->parseRegex($raw);
        } catch (\InvalidArgumentException $e) {
            throw new WmsException("Barcode {$raw} tidak cocok dengan template {$this->name}: ".$e->getMessage());
        }
        $w = $data['weight_kg'] ?? 0;
        if (($this->min_weight && $w < $this->min_weight) || ($this->max_weight && $w > $this->max_weight)) {
            throw new WmsException(sprintf('Berat %.2f kg di luar batas template %s (salah baca?).', $w, $this->name));
        }

        return $data;
    }

    private function parseGs1Template(string $raw): array
    {
        $ais = self::parseGs1($raw);
        $weight = 0.0; $lb = 0.0;
        foreach ($ais as $ai => $v) {
            $ai = (string) $ai; // kunci numerik seperti "3102" otomatis menjadi int di PHP
            $p = substr($ai, 0, 3);
            if ($p === '310' || $p === '320') {
                $weight = (int) $v / (10 ** (int) $ai[3]);
                if ($p === '320') { $lb = $weight; $weight *= self::LB_TO_KG; }
            }
        }
        if (! $weight) {
            throw new \InvalidArgumentException('berat (AI 310x/320x) tidak ada');
        }
        $exp = $ais['17'] ?? $ais['15'] ?? null;

        return ['weight_kg' => round($weight, 3), 'weight_lb' => $lb, 'lot' => $ais['10'] ?? null,
            'expiry' => $exp ? self::gs1Date($exp) : null, 'carton' => $ais['21'] ?? $ais['00'] ?? null, 'gtin' => $ais['01'] ?? null];
    }

    private function parseRegex(string $raw): array
    {
        $pattern = '/'.str_replace('/', '\/', (string) $this->pattern).'/';
        if (! @preg_match($pattern, trim($raw), $m)) {
            throw new \InvalidArgumentException('pola tidak cocok');
        }
        $weight = isset($m['weight']) ? (int) $m['weight'] / (10 ** $this->weight_decimals) : 0.0;
        $lb = $this->weight_unit === 'lb' ? $weight : 0.0;
        if ($this->weight_unit === 'lb') {
            $weight *= self::LB_TO_KG;
        }

        return ['weight_kg' => round($weight, 3), 'weight_lb' => $lb, 'lot' => $m['lot'] ?? null,
            'expiry' => isset($m['expiry']) ? self::gs1Date($m['expiry']) : null, 'carton' => $m['carton'] ?? null];
    }

    /** Coba semua template milik item (berurutan); template pertama yang cocok dipakai. */
    public static function parseForItem(Item $item, string $raw): array
    {
        $errors = [];
        foreach ($item->barcodeTemplates()->where('active', true)->get() as $tpl) {
            try {
                return [$tpl, $tpl->parse($raw)];
            } catch (WmsException $e) {
                $errors[] = $e->getMessage();
            }
        }
        throw new WmsException("Barcode tidak terbaca untuk {$item->label()}. Isi berat manual. ".(implode(' ', $errors) ?: 'Item belum punya template.'));
    }
}
