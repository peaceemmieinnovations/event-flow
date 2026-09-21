<?php

namespace App\Http\Controllers;

class EventController extends Controller
{
    public function index()
    {
        $pageTitle = 'Upcoming Events';

        return view('events', [
            'pageTitle' => $pageTitle
        ]);
    }
}