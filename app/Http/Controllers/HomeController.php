<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// home() - Отображает главную страницу сайта
class HomeController extends Controller
{
    public function home(){
        return view('home');
    }
} 
