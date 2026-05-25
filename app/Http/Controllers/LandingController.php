<?php

namespace App\Http\Controllers;

use App\Models\MasterMobil;
use Illuminate\Support\Facades\Auth;

class LandingController extends Controller
{
    public function index()
    {
        $mobils = MasterMobil::whereHas('mobils', function ($q) {
            $q->where('status', '<>', 0)
                ->where('status_approval', 1);
        })->get();
        $user = Auth::user();
        return view('main-content', compact('mobils', 'user'));
    }
}
