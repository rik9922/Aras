<?php

namespace App\Http\Controllers;

use App\Models\Recap;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
class PDFController extends Controller
{
    public function downloadRecap($id)
    {
        $recap = Recap::where('event_id',$id)->first();
        return view('pdf.myfile');

        $pdf = Pdf::loadView('pdf.myfile');

        $filename = time().'_recap'.'.pdf';
        return $pdf->download($filename);
    }
}
