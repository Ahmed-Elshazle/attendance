<?php
namespace App\Helpers;

class TermHelper
{
    public static function getCurrentTerm(): string
    {
        $month = now()->month;

        if ($month >= 9 || $month <= 1) {
            return 'first';
        } elseif ($month >= 2 && $month <= 6) {
            return 'second';
        } else {
            return 'summer';
        }
    }
}
