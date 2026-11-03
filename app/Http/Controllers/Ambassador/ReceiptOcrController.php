<?php

namespace App\Http\Controllers\Ambassador;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReceiptOcrRequest;
use App\Services\ReceiptOcrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReceiptOcrController extends Controller
{
    public function __invoke(ReceiptOcrRequest $request, ReceiptOcrService $ocrService): JsonResponse
    {
        try {
            $result = $ocrService->read($request->file('receipt_image')->getRealPath());

            return response()->json([
                'total' => $result['total'],
            ]);
        } catch (Throwable $exception) {
            Log::warning('Ambassador receipt OCR failed.', [
                'ambassador_id' => $request->user()?->id,
                'exception' => $exception,
            ]);

            return response()->json([
                'total' => null,
            ], 422);
        }
    }
}
