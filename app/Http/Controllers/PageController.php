<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function gobuyer(){
        return view('gobuyer');
    }

    public function help(){
        return view('help');
    }

}
