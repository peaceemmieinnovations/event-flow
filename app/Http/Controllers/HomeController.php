<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    public function index()
    {
        $name = 'Peace';

        return view('home', [
            'name' => $name
        ]);
    }
}