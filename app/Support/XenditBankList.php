<?php

namespace App\Support;

class XenditBankList
{
    protected static ?array $banks = null;

    /**
     * Ambil seluruh daftar bank (terurut alfabet), format:
     * [ ['name' => 'Bank Central Asia (BCA)', 'code' => 'ID_BCA'], ... ]
     */
    public static function all(): array
    {
        if (self::$banks === null) {
            $path = resource_path('data/xendit_banks.json');
            $raw  = json_decode(file_get_contents($path), true) ?? [];

            $banks = [];
            foreach ($raw as $name => $code) {
                $banks[] = ['name' => $name, 'code' => $code];
            }

            usort($banks, fn ($a, $b) => strcmp($a['name'], $b['name']));

            self::$banks = $banks;
        }

        return self::$banks;
    }

    /** Semua channel code yang valid, contoh: ID_BCA, ID_MANDIRI, dst */
    public static function codes(): array
    {
        return array_column(self::all(), 'code');
    }

    /** Cek apakah sebuah channel code benar-benar terdaftar di Xendit */
    public static function isValidCode(string $code): bool
    {
        return in_array($code, self::codes(), true);
    }

    /** Ambil nama bank yang enak dibaca dari channel code (untuk ditampilkan ke admin/EO/pembeli) */
    public static function nameForCode(?string $code): ?string
    {
        if (!$code) {
            return null;
        }

        foreach (self::all() as $bank) {
            if ($bank['code'] === $code) {
                return $bank['name'];
            }
        }

        return null;
    }
}