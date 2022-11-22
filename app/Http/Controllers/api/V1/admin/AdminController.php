<?php

namespace App\Http\Controllers\api\V1\admin;

use App\Http\Controllers\Controller;
use App\Models\Trainer;

class AdminController extends Controller
{
    public function trainerList(){
        $trainers = Trainer::all();
        return response()->json(['trainers' => $trainers]);
    }
}
