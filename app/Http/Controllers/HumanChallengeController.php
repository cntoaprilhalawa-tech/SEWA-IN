<?php

namespace App\Http\Controllers;

use App\Services\HumanCheck;

class HumanChallengeController extends Controller
{
    /** Memberi tantangan baru untuk widget "saya bukan robot". */
    public function show()
    {
        return response()
            ->json(HumanCheck::challenge())
            ->header('Cache-Control', 'no-store, max-age=0');
    }
}
