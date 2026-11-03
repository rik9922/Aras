<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ButtonShowcaseController extends Controller
{
    public function index()
    {
        return view('admin.buttons.index', ['page_name' => 'Button Showcase']);
    }
}
