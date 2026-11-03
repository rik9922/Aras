@extends('layout.app', ['page_name' => 'Receipt OCR Testing'])

@section('content')
@push('styles')
<style>
    .receipt-ocr-wrap { max-width: 860px; margin: 0 auto; padding: 2rem 1rem; }
    .receipt-ocr-heading { color: #0f2745; font-size: 1.45rem; font-weight: 700; margin-bottom: .35rem; }
    .receipt-ocr-desc { color: #6b7280; font-size: .9rem; margin-bottom: 1.5rem; }
    .receipt-ocr-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; box-shadow: 0 2px 10px rgba(0,0,0,.06); margin-bottom: 1.5rem; padding: 1.75rem; }
    .receipt-ocr-label { color: #374151; display: block; font-size: .86rem; font-weight: 700; margin-bottom: .5rem; }
    .receipt-ocr-input { border: 1.5px solid #d1d5db; border-radius: 8px; display: block; font-size: .9rem; padding: .65rem; width: 100%; }
    .receipt-ocr-help { color: #6b7280; font-size: .78rem; margin: .5rem 0 1.25rem; }
    .receipt-status { border-radius: 9px; font-size: .84rem; margin-bottom: 1.25rem; padding: .75rem 1rem; }
    .receipt-status-ok { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
    .receipt-status-error { background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; }
    .receipt-result-title { color: #0f2745; font-size: 1rem; font-weight: 700; margin-bottom: 1rem; }
    .receipt-total { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; color: #1e3a5f; margin-bottom: 1rem; padding: 1rem; }
    .receipt-total strong { display: block; font-size: 1.35rem; margin-top: .2rem; }
    .receipt-text { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; color: #1f2937; margin: 0; max-height: 420px; overflow: auto; padding: 1rem; white-space: pre-wrap; word-break: break-word; }
    .receipt-error { color: #b91c1c; font-size: .82rem; margin: .4rem 0 1rem; }
</style>
@endpush

<div class="receipt-ocr-wrap">
    <h1 class="receipt-ocr-heading">Receipt OCR Testing</h1>
    <p class="receipt-ocr-desc">Upload a receipt image to test text extraction. This tool is separate from recap processing and does not save the image or OCR result.</p>

    <div class="receipt-status {{ $tesseract['available'] ? 'receipt-status-ok' : 'receipt-status-error' }}">
        <i class="fas {{ $tesseract['available'] ? 'fa-check-circle' : 'fa-exclamation-triangle' }} me-1"></i>
        {{ $tesseract['message'] }}
    </div>
    <div class="receipt-ocr-card">
        <form method="POST" action="{{ route('admin.receipt-ocr.store') }}" enctype="multipart/form-data">
            @csrf
            <label class="receipt-ocr-label" for="receipt_image">Receipt image</label>
            <input class="receipt-ocr-input" id="receipt_image" name="receipt_image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
            <p class="receipt-ocr-help">Accepted formats: JPG, JPEG, PNG, and WebP. Maximum size: 10 MB.</p>
            @error('receipt_image')
                <div class="receipt-error">{{ $message }}</div>
            @enderror
            <button class="btn-ds btn-primary-ds" type="submit" @disabled(! $tesseract['available'])>
                <i class="fas fa-search"></i> Scan receipt
            </button>
        </form>
    </div>

    @isset($ocrText)
        <div class="receipt-ocr-card">
            <div class="receipt-result-title">OCR result for {{ $uploadedFileName }}</div>
            <div class="receipt-total">
                Likely receipt total
                @if ($receiptTotal !== null)
                    <strong>${{ $receiptTotal }}</strong>
                @else
                    <strong>Not detected</strong>
                    <span>No likely total was found. Review the extracted text below.</span>
                @endif
            </div>
            <pre class="receipt-text">{{ $ocrText }}</pre>
        </div>
    @endisset
</div>
@endsection
