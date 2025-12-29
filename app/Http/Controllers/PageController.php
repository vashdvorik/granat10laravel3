<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
//gobuyer() - Отображает страницу "Стать покупателем"

// help() - Отображает страницу помощи

class PageController extends Controller
{
    public function gobuyer(){
        return view('gobuyer');
    }

    public function help(){
        return view('help');
    }

}
