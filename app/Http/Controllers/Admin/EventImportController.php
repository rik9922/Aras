<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\EventsImport;
use App\Jobs\SendEventToBestJob;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class EventImportController extends Controller
{
    public function importExcel(Request $request)
    {
        $request->validate([
            'excel_file'   => 'required|file|mimes:csv,txt|max:51200',
            'send_to_best' => 'nullable|boolean',
        ]);

        $file     = $request->file('excel_file');
        $filename = $file->getClientOriginalName();

        Log::info('[EventImportController] Import started', [
            'filename'     => $filename,
            'size_bytes'   => $file->getSize(),
            'import_class' => EventsImport::class,
            'send_to_best' => $request->boolean('send_to_best'),
        ]);

        try {
            $import = new EventsImport();

            Excel::import($import, $file);

            $stats = [
                'total_processed'    => $import->totalProcessed,
                'imported'           => $import->imported,
                'skipped'            => $import->skipped,
                'failed'             => $import->failed,
                'manager_missing'    => $import->managerMissing,
                'client_missing'     => $import->clientMissing,
                'ref_not_matched'    => $import->refNotMatched,
                'missing_brand'      => $import->missingBrand,
                'missing_department' => $import->missingDepartment,
                'missing_distributor'=> $import->missingDistributor,
                'missing_event_type' => $import->missingEventType,
                'missing_market'     => $import->missingMarket,
                'missing_location'   => $import->missingLocation,
            ];

            Log::info('[EventImportController] Import completed', array_merge(
                ['filename' => $filename],
                $stats,
            ));

            // ── Optional: queue BEST sync for newly imported events ────────
            $dispatchedToBest = 0;
            if ($request->boolean('send_to_best') && $import->imported > 0) {
                $ids = Event::query()
                    ->where('booked_by', 'CSV Import')
                    ->where('integrate_with_best', 1)
                    ->whereNull('best_synced_at')
                    ->pluck('id');

                foreach ($ids->chunk(100) as $chunk) {
                    $jobs = $chunk->map(fn ($id) => new SendEventToBestJob((int) $id))->all();
                    Bus::chain($jobs)->dispatch();
                    $dispatchedToBest += count($jobs);
                }

                if ($dispatchedToBest > 0) {
                    Log::info('[EventImportController] BEST jobs queued', ['count' => $dispatchedToBest]);
                }
            }

            $message = implode(' | ', array_filter([
                'Import complete.',
                "Processed: {$import->totalProcessed}",
                "Imported: {$import->imported}",
                $import->skipped            > 0 ? "Duplicates skipped: {$import->skipped}"               : null,
                $import->failed             > 0 ? "Failed rows: {$import->failed}"                        : null,
                $import->managerMissing     > 0 ? "Manager missing/unmatched: {$import->managerMissing}" : null,
                $import->clientMissing      > 0 ? "Client not in CSV (saved as null): {$import->clientMissing}" : null,
                $import->missingBrand       > 0 ? "Missing brand: {$import->missingBrand}"                : null,
                $import->missingDepartment  > 0 ? "Missing department: {$import->missingDepartment}"      : null,
                $import->missingDistributor > 0 ? "Missing distributor: {$import->missingDistributor}"    : null,
                $import->missingEventType   > 0 ? "Missing event type: {$import->missingEventType}"       : null,
                $import->missingMarket      > 0 ? "Missing market: {$import->missingMarket}"              : null,
                $import->missingLocation    > 0 ? "Missing location: {$import->missingLocation}"          : null,
                $dispatchedToBest           > 0 ? "BEST queued: {$dispatchedToBest}"                      : null,
            ]));

            if ($request->ajax()) {
                return response()->json([
                    'success'             => true,
                    'message'             => $message,
                    'total_processed'     => $import->totalProcessed,
                    'imported'            => $import->imported,
                    'skipped'             => $import->skipped,
                    'failed'              => $import->failed,
                    'manager_missing'     => $import->managerMissing,
                    'client_missing'      => $import->clientMissing,
                    'ref_not_matched'     => $import->refNotMatched,
                    'missing_brand'       => $import->missingBrand,
                    'missing_department'  => $import->missingDepartment,
                    'missing_distributor' => $import->missingDistributor,
                    'missing_event_type'  => $import->missingEventType,
                    'missing_market'      => $import->missingMarket,
                    'missing_location'    => $import->missingLocation,
                    'best_queued'         => $dispatchedToBest,
                ]);
            }

            return redirect()->route('admin.events')->with('success', $message);

        } catch (\Throwable $e) {
            Log::error('[EventImportController] Import failed with exception', [
                'filename'        => $filename,
                'exception_class' => get_class($e),
                'message'         => $e->getMessage(),
                'file'            => $e->getFile(),
                'line'            => $e->getLine(),
            ]);

            $errorMessage = 'Import failed: ' . $e->getMessage();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 422);
            }

            return back()->with('error', $errorMessage);
        }
    }
}
