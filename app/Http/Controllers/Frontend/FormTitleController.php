<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\FormTitle;
use Illuminate\Http\Request;

class FormTitleController extends Controller
{
    public function getFormTitles()
    {
        $formTitles = FormTitle::orderBy('id')->get();
        $titleNames = $formTitles->pluck('title_name')->toArray();
        return response()->json($titleNames);
    }
}
