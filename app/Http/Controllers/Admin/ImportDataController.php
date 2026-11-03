<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\BrandsImport;
use App\Imports\DistributorsImport;
use App\Imports\LocationsImport;
use App\Imports\MarketsImport;
use App\Services\EventLocationService;
use App\Services\GoogleGeocodingService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ImportDataController extends Controller
{
    /** UTF-8 BOM prepended by Excel when saving "CSV UTF-8". */
    private const UTF8_BOM = "\xEF\xBB\xBF";

    // =========================================================================
    // Pages
    // =========================================================================

    public function index()
    {
        return view('admin.import-data.index');
    }

    // =========================================================================
    // Import endpoints
    // =========================================================================

    public function importBrands(Request $request)
    {
        $tag = 'BrandsImport';

        // Validate, store to disk, read headers
        $result = $this->prepareImport($request, $tag);
        if (! is_array($result)) {
            // prepareImport returned a redirect on failure
            return $result;
        }

        ['stored_path' => $storedPath] = $result;

        // Verify required column
        $normalisedHeaders = $result['normalised_headers'];
        $hasColumn = in_array('brand', $normalisedHeaders, true)
                  || in_array('brands', $normalisedHeaders, true);

        if (! $hasColumn) {
            $this->cleanupStoredFile($storedPath, $tag);
            Log::warning("[{$tag}] STAGE 5 - Brand column not found", [
                'found_headers' => $normalisedHeaders,
            ]);
            return back()
                ->with('error',
                    'The required "Brand" column was not found in the CSV. '
                    . 'The first row must contain the header "Brand". '
                    . 'Detected headers: ' . implode(', ', array_filter($normalisedHeaders)))
                ->with('active_type', 'brands');
        }

        // Run import
        try {
            Log::info("[{$tag}] STAGE 6 - Starting Maatwebsite import", [
                'import_class' => BrandsImport::class,
                'stored_path'  => $storedPath,
            ]);

            $import = new BrandsImport();
            Excel::import($import, $storedPath, null, \Maatwebsite\Excel\Excel::CSV);

            $imported = $import->imported;
            $skipped  = $import->skipped;

            Log::info("[{$tag}] STAGE 6 - Import complete", [
                'imported' => $imported,
                'skipped'  => $skipped,
            ]);

            $message = $imported === 0 && $skipped === 0
                ? 'The CSV was processed but contained no brand records to import.'
                : "{$imported} brand(s) imported successfully."
                  . ($skipped > 0 ? " {$skipped} duplicate(s) skipped." : '');

            return back()->with('success', $message)->with('active_type', 'brands');

        } catch (\Throwable $e) {
            Log::error("[{$tag}] STAGE 6 - Exception during import", [
                'exception_class' => get_class($e),
                'message'         => $e->getMessage(),
                'code'            => $e->getCode(),
                'file'            => $e->getFile(),
                'line'            => $e->getLine(),
                'trace'           => $e->getTraceAsString(),
            ]);
            return back()
                ->with('error', 'Import failed: ' . $e->getMessage())
                ->with('active_type', 'brands');
        } finally {
            $this->cleanupStoredFile($storedPath, $tag);
        }
    }

    public function importMarkets(Request $request)
    {
        $tag = 'MarketsImport';

        $result = $this->prepareImport($request, $tag, 'markets');
        if (! is_array($result)) {
            return $result;
        }

        ['stored_path' => $storedPath] = $result;

        $normalisedHeaders = $result['normalised_headers'];
        if (! in_array('market', $normalisedHeaders, true)) {
            $this->cleanupStoredFile($storedPath, $tag);
            Log::warning("[{$tag}] STAGE 5 - Market column not found", [
                'found_headers' => $normalisedHeaders,
            ]);
            return back()
                ->with('error',
                    'The required "Market" column was not found in the CSV. '
                    . 'The first row must contain the header "Market". '
                    . 'Detected headers: ' . implode(', ', array_filter($normalisedHeaders)))
                ->with('active_type', 'markets');
        }

        try {
            Log::info("[{$tag}] STAGE 6 - Starting Maatwebsite import", [
                'import_class' => MarketsImport::class,
                'stored_path'  => $storedPath,
            ]);

            $import = new MarketsImport();
            Excel::import($import, $storedPath, null, \Maatwebsite\Excel\Excel::CSV);

            $imported     = $import->imported;
            $skipped      = $import->skipped;
            $empty        = $import->empty;
            $insertFailed = $import->insertFailed;

            Log::info("[{$tag}] STAGE 6 - Import complete", [
                'imported'      => $imported,
                'skipped'       => $skipped,
                'empty'         => $empty,
                'insert_failed' => $insertFailed,
            ]);

            if ($imported === 0 && $skipped === 0) {
                $message = 'The CSV was processed but contained no market records to import.';
            } else {
                $parts = ["{$imported} market(s) imported successfully."];
                if ($skipped > 0)       { $parts[] = "{$skipped} duplicate(s) skipped."; }
                if ($empty > 0)         { $parts[] = "{$empty} empty row(s) ignored."; }
                if ($insertFailed > 0)  { $parts[] = "{$insertFailed} row(s) failed to save — see server log."; }
                $message = implode(' ', $parts);
            }

            return back()->with('success', $message)->with('active_type', 'markets');

        } catch (\Throwable $e) {
            Log::error("[{$tag}] STAGE 6 - Exception during import", [
                'exception_class' => get_class($e),
                'message'         => $e->getMessage(),
                'code'            => $e->getCode(),
                'file'            => $e->getFile(),
                'line'            => $e->getLine(),
                'trace'           => $e->getTraceAsString(),
            ]);
            return back()
                ->with('error', 'Import failed: ' . $e->getMessage())
                ->with('active_type', 'markets');
        } finally {
            $this->cleanupStoredFile($storedPath, $tag);
        }
    }

    public function importLocations(Request $request)
    {
        $tag = 'LocationsImport';

        $result = $this->prepareImport($request, $tag, 'locations');
        if (! is_array($result)) {
            return $result;
        }

        ['stored_path' => $storedPath] = $result;

        $normalisedHeaders = $result['normalised_headers'];
        $hasColumn = in_array('location', $normalisedHeaders, true);

        if (! $hasColumn) {
            $this->cleanupStoredFile($storedPath, $tag);
            Log::warning("[{$tag}] STAGE 5 - Location column not found", [
                'found_headers' => $normalisedHeaders,
            ]);
            return back()
                ->with('error',
                    'The required "Location" column was not found in the CSV. '
                    . 'The first row must contain the header "Location". '
                    . 'Detected headers: ' . implode(', ', array_filter($normalisedHeaders)))
                ->with('active_type', 'locations');
        }

        try {
            Log::info("[{$tag}] STAGE 6 - Starting Maatwebsite import", [
                'import_class' => LocationsImport::class,
                'stored_path'  => $storedPath,
            ]);

            $import = new LocationsImport(
                app(GoogleGeocodingService::class),
                app(EventLocationService::class),
            );
            Excel::import($import, $storedPath, null, \Maatwebsite\Excel\Excel::CSV);

            $imported     = $import->imported;
            $skipped      = $import->skipped;
            $empty        = $import->empty;
            $geoFailed    = $import->geoFailed;
            $insertFailed = $import->insertFailed;

            Log::info("[{$tag}] STAGE 6 - Import complete", [
                'imported'      => $imported,
                'skipped'       => $skipped,
                'empty'         => $empty,
                'geo_failed'    => $geoFailed,
                'insert_failed' => $insertFailed,
            ]);

            if ($imported === 0 && $skipped === 0) {
                $message = 'The CSV was processed but contained no location records to import.';
            } else {
                $parts = ["{$imported} location(s) imported successfully."];
                if ($skipped > 0)      { $parts[] = "{$skipped} duplicate(s) skipped."; }
                if ($empty > 0)        { $parts[] = "{$empty} empty row(s) ignored."; }
                if ($geoFailed > 0)    { $parts[] = "{$geoFailed} geocode lookup(s) failed (imported without coordinates)."; }
                if ($insertFailed > 0) { $parts[] = "{$insertFailed} row(s) failed to save — see server log."; }
                $message = implode(' ', $parts);
            }

            return back()->with('success', $message)->with('active_type', 'locations');

        } catch (\Throwable $e) {
            Log::error("[{$tag}] STAGE 6 - Exception during import", [
                'exception_class' => get_class($e),
                'message'         => $e->getMessage(),
                'code'            => $e->getCode(),
                'file'            => $e->getFile(),
                'line'            => $e->getLine(),
                'trace'           => $e->getTraceAsString(),
            ]);
            return back()
                ->with('error', 'Import failed: ' . $e->getMessage())
                ->with('active_type', 'locations');
        } finally {
            $this->cleanupStoredFile($storedPath, $tag);
        }
    }

    public function importDistributors(Request $request)
    {
        $tag = 'DistributorsImport';

        $result = $this->prepareImport($request, $tag, 'distributors');
        if (! is_array($result)) {
            return $result;
        }

        ['stored_path' => $storedPath] = $result;

        $normalisedHeaders = $result['normalised_headers'];
        $hasColumn = in_array('distributor', $normalisedHeaders, true);

        if (! $hasColumn) {
            $this->cleanupStoredFile($storedPath, $tag);
            Log::warning("[{$tag}] STAGE 5 - Distributor column not found", [
                'found_headers' => $normalisedHeaders,
            ]);
            return back()
                ->with('error',
                    'The required "Distributor" column was not found in the CSV. '
                    . 'The first row must contain the header "Distributor". '
                    . 'Detected headers: ' . implode(', ', array_filter($normalisedHeaders)))
                ->with('active_type', 'distributors');
        }

        try {
            Log::info("[{$tag}] STAGE 6 - Starting Maatwebsite import", [
                'import_class' => DistributorsImport::class,
                'stored_path'  => $storedPath,
            ]);

            $import = new DistributorsImport();
            Excel::import($import, $storedPath, null, \Maatwebsite\Excel\Excel::CSV);

            $imported = $import->imported;
            $skipped  = $import->skipped;
            $empty    = $import->empty;

            Log::info("[{$tag}] STAGE 6 - Import complete", [
                'imported' => $imported,
                'skipped'  => $skipped,
                'empty'    => $empty,
            ]);

            if ($imported === 0 && $skipped === 0) {
                $message = 'The CSV was processed but contained no distributor records to import.';
            } else {
                $message = "{$imported} distributor(s) imported successfully.";
                if ($skipped > 0) {
                    $message .= " {$skipped} duplicate(s) skipped.";
                }
                if ($empty > 0) {
                    $message .= " {$empty} empty row(s) ignored.";
                }
            }

            return back()->with('success', $message)->with('active_type', 'distributors');

        } catch (\Throwable $e) {
            Log::error("[{$tag}] STAGE 6 - Exception during import", [
                'exception_class' => get_class($e),
                'message'         => $e->getMessage(),
                'code'            => $e->getCode(),
                'file'            => $e->getFile(),
                'line'            => $e->getLine(),
                'trace'           => $e->getTraceAsString(),
            ]);
            return back()
                ->with('error', 'Import failed: ' . $e->getMessage())
                ->with('active_type', 'distributors');
        } finally {
            $this->cleanupStoredFile($storedPath, $tag);
        }
    }

    // =========================================================================
    // Shared private helpers
    // =========================================================================

    /**
     * Runs all shared stages for a CSV import:
     *   STAGE 1 — log request
     *   STAGE 2 — PHP upload error check
     *   STAGE 3 — Laravel validation
     *   STAGE 4 — store file to a guaranteed-writable local path
     *   STAGE 5 — read + normalise CSV header row
     *
     * Returns an array on success:
     *   ['stored_path' => string, 'normalised_headers' => string[]]
     *
     * Returns a RedirectResponse on any failure (the caller must return it).
     *
     * @param  string  $tag         Log prefix, e.g. 'BrandsImport'
     * @param  string  $activeType  session('active_type') value on error redirect
     */
    private function prepareImport(Request $request, string $tag, string $activeType = 'brands'): array|\Illuminate\Http\RedirectResponse
    {
        // ── STAGE 1 ───────────────────────────────────────────────────────
        Log::info("[{$tag}] STAGE 1 - Request received", [
            'method'        => $request->method(),
            'url'           => $request->fullUrl(),
            'route'         => $request->route()?->getName(),
            'user_id'       => auth()->id(),
            'has_csv_field' => $request->hasFile('csv_file'),
            'all_file_keys' => array_keys($request->allFiles()),
        ]);

        // ── STAGE 2 — PHP-level upload error check ────────────────────────
        // Must run BEFORE the Laravel validator.
        // If getError() !== UPLOAD_ERR_OK → Laravel's default message would be
        // the generic "The csv file failed to upload." with zero context.
        // We intercept here and return a specific, actionable message.
        $rawFile = $request->file('csv_file');

        if ($rawFile instanceof UploadedFile) {
            $phpError = $rawFile->getError();
            $isValid  = $rawFile->isValid();
            $tmpPath  = $rawFile->getPathname();

            // getMimeType() calls finfo on the tmp path.
            // On this server tmp path can be "" → finfo throws → guard it.
            $detectedMime = null;
            try {
                $detectedMime = $rawFile->getMimeType();
            } catch (\Throwable $ignored) {}

            Log::info("[{$tag}] STAGE 2 - File object received", [
                'original_name'    => $rawFile->getClientOriginalName(),
                'extension'        => $rawFile->getClientOriginalExtension(),
                'client_mime_type' => $rawFile->getClientMimeType(),
                'detected_mime'    => $detectedMime,
                'size_bytes'       => $rawFile->getSize(),
                'tmp_path'         => $tmpPath,
                'tmp_exists'       => $tmpPath !== '' && file_exists($tmpPath),
                'php_upload_error' => $phpError,
                'is_valid'         => $isValid,
            ]);

            if ($phpError !== UPLOAD_ERR_OK) {
                $phpErrorMessages = [
                    UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize in php.ini.',
                    UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE directive.',
                    UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded. Please try again.',
                    UPLOAD_ERR_NO_FILE    => 'No file was attached to the request.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error: missing temporary upload directory.',
                    UPLOAD_ERR_CANT_WRITE => 'Server configuration error: failed to write the upload to disk.',
                    UPLOAD_ERR_EXTENSION  => 'A PHP extension blocked the upload.',
                ];
                $msg = $phpErrorMessages[$phpError] ?? "Unknown PHP upload error (code {$phpError}).";
                Log::error("[{$tag}] STAGE 2 - PHP upload error", [
                    'error_code' => $phpError, 'error_message' => $msg,
                ]);
                return back()->with('error', "Upload failed: {$msg}")->with('active_type', $activeType);
            }

            if (! $isValid) {
                Log::error("[{$tag}] STAGE 2 - isValid() false despite UPLOAD_ERR_OK", [
                    'tmp_path' => $tmpPath,
                    'note'     => 'Possible open_basedir restriction or tmp file race condition.',
                ]);
                return back()
                    ->with('error', 'The uploaded file could not be accessed by the server. '
                        . 'This is usually an open_basedir or temp directory configuration issue.')
                    ->with('active_type', $activeType);
            }
        } else {
            Log::warning("[{$tag}] STAGE 2 - No UploadedFile in request", [
                'request_keys' => array_keys($request->all()),
            ]);
        }

        // ── STAGE 3 — Laravel validation ──────────────────────────────────
        Log::info("[{$tag}] STAGE 3 - Running validation");

        $validator = Validator::make($request->all(), [
            'csv_file' => [
                'required', 'file', 'max:51200',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! ($value instanceof UploadedFile)) {
                        return;
                    }
                    if (strtolower($value->getClientOriginalExtension()) !== 'csv') {
                        $fail('Only .csv files are accepted. Please upload a file with a .csv extension.');
                    }
                },
            ],
        ], [
            'csv_file.required' => 'Please select a CSV file before importing.',
            'csv_file.file'     => 'The uploaded file is not valid.',
            'csv_file.uploaded' => 'The file could not be uploaded. Check the server error log for details.',
            'csv_file.max'      => 'The CSV file must not exceed 50 MB.',
        ]);

        if ($validator->fails()) {
            Log::warning("[{$tag}] STAGE 3 - Validation failed", [
                'errors' => $validator->errors()->toArray(),
            ]);
            return back()->withErrors($validator)->withInput()->with('active_type', $activeType);
        }

        Log::info("[{$tag}] STAGE 3 - Validation passed");

        // ── STAGE 4 — Store to a guaranteed-writable local path ───────────
        //
        // Passing UploadedFile directly to Excel::import() calls getPathname()
        // internally. On this server getPathname() returns "" (empty string),
        // causing Maatwebsite to throw:
        //   FileNotFoundException: 'The "" file does not exist or is not readable.'
        //
        // Fix: persist via storeAs() → pass the absolute string path instead.
        $file       = $request->file('csv_file');
        $storedPath = null;

        Log::info("[{$tag}] STAGE 4 - Storing file to local disk", [
            'original_name' => $file->getClientOriginalName(),
            'tmp_path'      => $file->getPathname(),
        ]);

        try {
            $slug = strtolower(str_replace('Import', '', $tag)); // 'brands' / 'distributors'
            $storedRelative = $file->storeAs(
                'imports',
                "{$slug}_import_" . time() . '_' . auth()->id() . '.csv',
                ['disk' => 'local']
            );

            if ($storedRelative === false || $storedRelative === '') {
                Log::error("[{$tag}] STAGE 4 - storeAs() returned false/empty");
                return back()
                    ->with('error', 'The server could not save the uploaded file. Please try again.')
                    ->with('active_type', $activeType);
            }

            $storedPath = Storage::disk('local')->path($storedRelative);

            Log::info("[{$tag}] STAGE 4 - File stored successfully", [
                'stored_relative' => $storedRelative,
                'stored_absolute' => $storedPath,
                'stored_exists'   => file_exists($storedPath),
                'stored_readable' => is_readable($storedPath),
                'stored_size'     => file_exists($storedPath) ? filesize($storedPath) : 'N/A',
            ]);

            if (! file_exists($storedPath) || ! is_readable($storedPath)) {
                Log::error("[{$tag}] STAGE 4 - Stored file not accessible", [
                    'stored_path' => $storedPath,
                    'disk_root'   => Storage::disk('local')->path(''),
                ]);
                return back()
                    ->with('error', 'The server stored the file but could not locate it for processing. Please try again.')
                    ->with('active_type', $activeType);
            }

        } catch (\Throwable $e) {
            Log::error("[{$tag}] STAGE 4 - Failed to store file", [
                'exception_class' => get_class($e),
                'message'         => $e->getMessage(),
                'file'            => $e->getFile(),
                'line'            => $e->getLine(),
            ]);
            return back()
                ->with('error', 'The server could not save the uploaded file: ' . $e->getMessage())
                ->with('active_type', $activeType);
        }

        // ── STAGE 5 — Read and normalise CSV header row ───────────────────
        Log::info("[{$tag}] STAGE 5 - Reading CSV headers from stored file");

        $handle = fopen($storedPath, 'r');
        $rawRow = ($handle !== false) ? fgetcsv($handle) : false;
        if ($handle !== false) {
            fclose($handle);
        }

        Log::info("[{$tag}] STAGE 5 - Raw first row", [
            'raw_row'     => $rawRow,
            'raw_row_hex' => is_array($rawRow)
                ? array_map(fn ($c) => bin2hex((string) $c), $rawRow)
                : 'NOT_AN_ARRAY',
        ]);

        if (! is_array($rawRow) || empty($rawRow)) {
            $this->cleanupStoredFile($storedPath, $tag);
            Log::error("[{$tag}] STAGE 5 - CSV appears empty or unreadable");
            return back()
                ->with('error', 'The CSV file appears to be empty or unreadable.')
                ->with('active_type', $activeType);
        }

        // Strip UTF-8 BOM from the first cell
        if (str_starts_with((string) ($rawRow[0] ?? ''), self::UTF8_BOM)) {
            $rawRow[0] = substr($rawRow[0], strlen(self::UTF8_BOM));
            Log::info("[{$tag}] STAGE 5 - UTF-8 BOM stripped from first header cell");
        }

        $normalisedHeaders = array_map(
            fn (string $h): string => strtolower(trim($h)),
            array_map('strval', $rawRow)
        );

        Log::info("[{$tag}] STAGE 5 - Headers normalised", [
            'normalised_headers' => $normalisedHeaders,
        ]);

        return [
            'stored_path'        => $storedPath,
            'normalised_headers' => $normalisedHeaders,
        ];
    }

    /**
     * Silently delete the temp file stored during prepareImport().
     */
    private function cleanupStoredFile(?string $path, string $tag): void
    {
        if ($path && file_exists($path)) {
            @unlink($path);
            Log::info("[{$tag}] Temp file cleaned up", ['path' => $path]);
        }
    }
}
