<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
//gobuyer() - Отображает страницу "Стать покупателем"

// help() - Отображает страницу помощи

class PageController extends Controller
{
    public function sellerHelp(){
        return view('gobuyer');
    }

    public function gobuyer(){
        return $this->sellerHelp();
    }

    public function help(){
        return view('help');
    }

}
