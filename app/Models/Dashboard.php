<?php

namespace App\Models;

class Dashboard
{
    public static function getAllTransactionData()
    {
        return Transaction::with('details')->get();
    }
}
