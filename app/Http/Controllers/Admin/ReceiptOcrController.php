<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReceiptOcrRequest;
use App\Services\ReceiptOcrService;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReceiptOcrController extends Controller
{
    public function index(ReceiptOcrService $ocrService)
    {
        return view('admin.receipt-ocr.index', [
            'tesseract' => $ocrService->status(),
        ]);
    }

    public function store(ReceiptOcrRequest $request, ReceiptOcrService $ocrService)
    {
        $tesseract = $ocrService->status();

        if (! $tesseract['available']) {
            return back()->with('error', $tesseract['message']);
        }

        try {
            $file = $request->file('receipt_image');
            $result = $ocrService->read($file->getRealPath());

            if ($result['text'] === '') {
                return back()->with('error', 'Tesseract completed, but no readable text was found in that image. Try a sharper, well-lit image.');
            }

            return view('admin.receipt-ocr.index', [
                'tesseract' => $tesseract,
                'ocrText' => $result['text'],
                'receiptTotal' => $result['total'],
                'uploadedFileName' => $request->file('receipt_image')->getClientOriginalName(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Receipt OCR test failed.', [
                'exception' => $exception,
                'file_name' => $request->file('receipt_image')->getClientOriginalName(),
            ]);

            return back()->with('error', 'The receipt could not be processed. Confirm that Tesseract and its English language data are installed, then try another image.');
        }
    }
}
