<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SingleChoiceController extends Controller
{
    public function index(){
        return view('frontend.test.single_choice');
    }
}


