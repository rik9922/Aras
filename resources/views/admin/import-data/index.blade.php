@extends('layout.app', ['page_name' => 'Import Data'])

@section('content')

@push('styles')
<style>
    .import-page-wrap {
        max-width: 720px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .import-page-heading {
        font-size: 1.45rem;
        font-weight: 700;
        color: #0f2745;
        margin-bottom: .35rem;
    }

    .import-page-desc {
        font-size: .9rem;
        color: #6b7280;
        margin-bottom: 1.75rem;
    }

    /* ── Data-type selector card ───────────────────────────── */
    .import-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(0,0,0,.06);
        padding: 1.75rem;
        margin-bottom: 1.5rem;
    }

    .import-card-label {
        font-size: .78rem;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-bottom: .6rem;
    }

    .import-select {
        width: 100%;
        padding: .55rem .9rem;
        font-size: .92rem;
        color: #111827;
        background: #f9fafb;
        border: 1.5px solid #d1d5db;
        border-radius: 8px;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right .75rem center;
        padding-right: 2.5rem;
        transition: border-color .15s;
        cursor: pointer;
    }

    .import-select:focus {
        outline: none;
        border-color: #1c4975;
        box-shadow: 0 0 0 3px rgba(28,73,117,.12);
        background-color: #fff;
    }

    /* ── Import panel (Brands section) ─────────────────────── */
    .import-panel {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(0,0,0,.06);
        padding: 1.75rem;
    }

    .import-panel-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f2745;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .import-panel-title i {
        color: #1c4975;
        font-size: .95rem;
    }

    /* ── File upload area ───────────────────────────────────── */
    .upload-zone {
        border: 2px dashed #cbd5e1;
        border-radius: 10px;
        padding: 2.25rem 1.5rem;
        text-align: center;
        background: #f8fafc;
        transition: border-color .2s, background .2s;
        cursor: pointer;
        position: relative;
    }

    .upload-zone:hover,
    .upload-zone.drag-over {
        border-color: #1c4975;
        background: #eff6ff;
    }

    .upload-zone input[type="file"] {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .upload-icon {
        font-size: 2rem;
        color: #94a3b8;
        margin-bottom: .6rem;
    }

    .upload-zone:hover .upload-icon,
    .upload-zone.drag-over .upload-icon {
        color: #1c4975;
    }

    .upload-main-text {
        font-size: .9rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: .25rem;
    }

    .upload-sub-text {
        font-size: .8rem;
        color: #9ca3af;
    }

    /* ── Selected file name ─────────────────────────────────── */
    .file-selected-row {
        display: none;
        align-items: center;
        gap: .6rem;
        margin-top: 1rem;
        padding: .6rem .9rem;
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 8px;
        font-size: .85rem;
        color: #0369a1;
    }

    .file-selected-row i {
        font-size: .9rem;
        flex-shrink: 0;
    }

    .file-selected-name {
        font-weight: 600;
        word-break: break-all;
    }

    /* ── Coming-soon notice ─────────────────────────────────── */
    .coming-soon-panel {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(0,0,0,.06);
        padding: 2.5rem 1.75rem;
        text-align: center;
        color: #9ca3af;
    }

    .coming-soon-icon {
        font-size: 2.2rem;
        margin-bottom: .75rem;
        color: #d1d5db;
    }

    .coming-soon-label {
        font-size: 1rem;
        font-weight: 600;
        color: #6b7280;
        margin-bottom: .4rem;
    }

    .coming-soon-sub {
        font-size: .82rem;
        color: #9ca3af;
    }

    .import-submit-row {
        margin-top: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    /* ── Inline flash alerts ────────────────────────────────── */
    .import-alert {
        border-radius: 10px;
        padding: .85rem 1.1rem;
        font-size: .875rem;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: flex-start;
        gap: .6rem;
    }

    .import-alert i { flex-shrink: 0; margin-top: .1rem; }

    .import-alert-success {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
    }

    .import-alert-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    /* ── Loading spinner on button ──────────────────────────── */
    .btn-ds .spinner {
        display: none;
        width: 14px;
        height: 14px;
        border: 2px solid rgba(255,255,255,.4);
        border-top-color: #fff;
        border-radius: 50%;
        animation: btn-spin .65s linear infinite;
        flex-shrink: 0;
    }

    @keyframes btn-spin { to { transform: rotate(360deg); } }

    .btn-ds.loading .spinner    { display: block; }
    .btn-ds.loading .btn-label  { opacity: .7; }
    .btn-ds.loading              { cursor: not-allowed; pointer-events: none; }
</style>
@endpush

@php
    $activeType = session('active_type', '');
@endphp

<div class="import-page-wrap">

    {{-- Page header --}}
    <h1 class="import-page-heading">Import Data</h1>
    <p class="import-page-desc">Import and sync reference data from CSV files before importing event data.</p>

    {{-- ── Inline flash messages ── --}}
    @if (session('success'))
        <div class="import-alert import-alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="import-alert import-alert-error">
            <i class="fa-solid fa-circle-xmark"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Laravel validation errors --}}
    @if ($errors->any())
        <div class="import-alert import-alert-error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ── Data type selector ── --}}
    <div class="import-card">
        <div class="import-card-label">Select Data Type</div>
        <select class="import-select" id="dataTypeSelect">
            <option value="">— Choose a data type —</option>
            <option value="brands" @selected($activeType === 'brands')>Brands</option>
            <option value="locations" @selected($activeType === 'locations')>Locations</option>
            <option value="ambassadors"   disabled>Ambassadors (Coming Soon)</option>
            <option value="store-numbers" disabled>Store Numbers (Coming Soon)</option>
            <option value="distributors" @selected($activeType === 'distributors')>Distributors</option>
            <option value="markets" @selected($activeType === 'markets')>Markets</option>
            <option value="managers"      disabled>Managers (Coming Soon)</option>
        </select>
    </div>

    {{-- ── Brands import panel ── --}}
    <div id="panel-brands" class="import-panel"
         style="display: {{ $activeType === 'brands' ? 'block' : 'none' }};">

        <div class="import-panel-title">
            <i class="fa-solid fa-file-arrow-up"></i>
            Import Brands
        </div>

        <form id="brandsImportForm"
              method="POST"
              action="{{ route('admin.import-data.brands') }}"
              enctype="multipart/form-data">
            @csrf

            <div class="upload-zone" id="uploadZone">
                <input
                    type="file"
                    id="brandsCsvFile"
                    name="csv_file"
                    accept=".csv"
                >
                <div class="upload-icon">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                </div>
                <div class="upload-main-text">Click to select or drag &amp; drop a CSV file</div>
                <div class="upload-sub-text">Accepted format: <strong>.csv</strong></div>
            </div>

            <div class="file-selected-row" id="fileSelectedRow">
                <i class="fa-solid fa-file-csv"></i>
                <span class="file-selected-name" id="fileSelectedName"></span>
            </div>

            <div class="import-submit-row">
                <button type="submit" class="btn-ds btn-primary-ds" id="importBrandsBtn" disabled>
                    <span class="spinner"></span>
                    <i class="fa-solid fa-upload btn-label"></i>
                    <span class="btn-label">Import Brands</span>
                </button>
            </div>
        </form>
    </div>

    {{-- ── Markets import panel ── --}}
    <div id="panel-markets" class="import-panel"
         style="display: {{ $activeType === 'markets' ? 'block' : 'none' }};">

        <div class="import-panel-title">
            <i class="fa-solid fa-file-arrow-up"></i>
            Import Markets
        </div>

        <form id="marketsImportForm"
              method="POST"
              action="{{ route('admin.import-data.markets') }}"
              enctype="multipart/form-data">
            @csrf

            <div class="upload-zone" id="uploadZoneMarkets">
                <input
                    type="file"
                    id="marketsCsvFile"
                    name="csv_file"
                    accept=".csv"
                >
                <div class="upload-icon">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                </div>
                <div class="upload-main-text">Click to select or drag &amp; drop a CSV file</div>
                <div class="upload-sub-text">Accepted format: <strong>.csv</strong></div>
            </div>

            <div class="file-selected-row" id="fileSelectedRowMarkets">
                <i class="fa-solid fa-file-csv"></i>
                <span class="file-selected-name" id="fileSelectedNameMarkets"></span>
            </div>

            <div class="import-submit-row">
                <button type="submit" class="btn-ds btn-primary-ds" id="importMarketsBtn" disabled>
                    <span class="spinner"></span>
                    <i class="fa-solid fa-upload btn-label"></i>
                    <span class="btn-label">Import Markets</span>
                </button>
            </div>
        </form>
    </div>

    {{-- ── Locations import panel ── --}}
    <div id="panel-locations" class="import-panel"
         style="display: {{ $activeType === 'locations' ? 'block' : 'none' }};">

        <div class="import-panel-title">
            <i class="fa-solid fa-file-arrow-up"></i>
            Import Locations
        </div>

        <p style="font-size:.85rem;color:#6b7280;margin-bottom:1.25rem;">
            CSV must include a <strong>Location</strong> column header. Optional columns:
            <strong>Address</strong>, <strong>City</strong>, <strong>State</strong>,
            <strong>Zip</strong>, <strong>Store Number</strong>.
            Coordinates and timezone are resolved automatically via Google Maps.
        </p>

        <form id="locationsImportForm"
              method="POST"
              action="{{ route('admin.import-data.locations') }}"
              enctype="multipart/form-data">
            @csrf

            <div class="upload-zone" id="uploadZoneLocations">
                <input
                    type="file"
                    id="locationsCsvFile"
                    name="csv_file"
                    accept=".csv"
                >
                <div class="upload-icon">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                </div>
                <div class="upload-main-text">Click to select or drag &amp; drop a CSV file</div>
                <div class="upload-sub-text">Accepted format: <strong>.csv</strong></div>
            </div>

            <div class="file-selected-row" id="fileSelectedRowLocations">
                <i class="fa-solid fa-file-csv"></i>
                <span class="file-selected-name" id="fileSelectedNameLocations"></span>
            </div>

            <div class="import-submit-row">
                <button type="submit" class="btn-ds btn-primary-ds" id="importLocationsBtn" disabled>
                    <span class="spinner"></span>
                    <i class="fa-solid fa-upload btn-label"></i>
                    <span class="btn-label">Import Locations</span>
                </button>
            </div>
        </form>
    </div>

    {{-- ── Distributors import panel ── --}}
    <div id="panel-distributors" class="import-panel"
         style="display: {{ $activeType === 'distributors' ? 'block' : 'none' }};">

        <div class="import-panel-title">
            <i class="fa-solid fa-file-arrow-up"></i>
            Import Distributors
        </div>

        <form id="distributorsImportForm"
              method="POST"
              action="{{ route('admin.import-data.distributors') }}"
              enctype="multipart/form-data">
            @csrf

            <div class="upload-zone" id="uploadZoneDistributors">
                <input
                    type="file"
                    id="distributorsCsvFile"
                    name="csv_file"
                    accept=".csv"
                >
                <div class="upload-icon">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                </div>
                <div class="upload-main-text">Click to select or drag &amp; drop a CSV file</div>
                <div class="upload-sub-text">Accepted format: <strong>.csv</strong></div>
            </div>

            <div class="file-selected-row" id="fileSelectedRowDistributors">
                <i class="fa-solid fa-file-csv"></i>
                <span class="file-selected-name" id="fileSelectedNameDistributors"></span>
            </div>

            <div class="import-submit-row">
                <button type="submit" class="btn-ds btn-primary-ds" id="importDistributorsBtn" disabled>
                    <span class="spinner"></span>
                    <i class="fa-solid fa-upload btn-label"></i>
                    <span class="btn-label">Import Distributors</span>
                </button>
            </div>
        </form>
    </div>

    {{-- ── Coming-soon placeholder ── --}}
    <div id="panel-coming-soon" class="coming-soon-panel" style="display:none;">
        <div class="coming-soon-icon"><i class="fa-regular fa-clock"></i></div>
        <div class="coming-soon-label">Coming Soon</div>
        <div class="coming-soon-sub">
            This import type is not yet available. Only <strong>Brands</strong> imports are supported right now.
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
(function () {
    const select  = document.getElementById('dataTypeSelect');

    // All panels (add new ones here as import types are enabled)
    const panels = {
        brands:        document.getElementById('panel-brands'),
        distributors:  document.getElementById('panel-distributors'),
        locations:     document.getElementById('panel-locations'),
        markets:       document.getElementById('panel-markets'),
        'coming-soon': document.getElementById('panel-coming-soon'),
    };

    // Per-panel config: { zone, input, selectedRow, selectedName, btn, form }
    const panelConfig = {
        brands: {
            zone:         document.getElementById('uploadZone'),
            input:        document.getElementById('brandsCsvFile'),
            selectedRow:  document.getElementById('fileSelectedRow'),
            selectedName: document.getElementById('fileSelectedName'),
            btn:          document.getElementById('importBrandsBtn'),
            form:         document.getElementById('brandsImportForm'),
        },
        distributors: {
            zone:         document.getElementById('uploadZoneDistributors'),
            input:        document.getElementById('distributorsCsvFile'),
            selectedRow:  document.getElementById('fileSelectedRowDistributors'),
            selectedName: document.getElementById('fileSelectedNameDistributors'),
            btn:          document.getElementById('importDistributorsBtn'),
            form:         document.getElementById('distributorsImportForm'),
        },
        locations: {
            zone:         document.getElementById('uploadZoneLocations'),
            input:        document.getElementById('locationsCsvFile'),
            selectedRow:  document.getElementById('fileSelectedRowLocations'),
            selectedName: document.getElementById('fileSelectedNameLocations'),
            btn:          document.getElementById('importLocationsBtn'),
            form:         document.getElementById('locationsImportForm'),
        },
        markets: {
            zone:         document.getElementById('uploadZoneMarkets'),
            input:        document.getElementById('marketsCsvFile'),
            selectedRow:  document.getElementById('fileSelectedRowMarkets'),
            selectedName: document.getElementById('fileSelectedNameMarkets'),
            btn:          document.getElementById('importMarketsBtn'),
            form:         document.getElementById('marketsImportForm'),
        },
    };

    const ACTIVE_TYPES = new Set(['brands', 'distributors', 'locations', 'markets']);

    /* ── Show the correct panel ── */
    function applyPanel(val) {
        Object.entries(panels).forEach(([key, el]) => {
            if (!el) return;
            if (key === 'coming-soon') {
                el.style.display = (val && !ACTIVE_TYPES.has(val)) ? 'block' : 'none';
            } else {
                el.style.display = (val === key) ? 'block' : 'none';
            }
        });
    }

    applyPanel(select.value);

    /* ── Data-type switcher ── */
    select.addEventListener('change', function () {
        applyPanel(this.value);
        // Reset all panel file states on switch
        Object.values(panelConfig).forEach(cfg => {
            if (!cfg.input) return;
            cfg.input.value           = '';
            cfg.selectedRow.style.display = 'none';
            cfg.selectedName.textContent  = '';
            cfg.btn.disabled              = true;
            cfg.btn.classList.remove('loading');
        });
    });

    /* ── Wire up each active panel ── */
    Object.values(panelConfig).forEach(cfg => {
        if (!cfg.input) return;

        cfg.input.addEventListener('change', function () {
            handleFile(this.files[0] ?? null, cfg);
        });

        cfg.zone.addEventListener('dragover', function (e) {
            e.preventDefault();
            cfg.zone.classList.add('drag-over');
        });
        cfg.zone.addEventListener('dragleave', function () {
            cfg.zone.classList.remove('drag-over');
        });
        cfg.zone.addEventListener('drop', function (e) {
            e.preventDefault();
            cfg.zone.classList.remove('drag-over');
            const file = e.dataTransfer.files[0] ?? null;
            if (!file) return;
            if (!file.name.toLowerCase().endsWith('.csv')) {
                alert('Please select a valid .csv file.');
                return;
            }
            try {
                const dt = new DataTransfer();
                dt.items.add(file);
                cfg.input.files = dt.files;
            } catch (_) {}
            handleFile(file, cfg);
        });

        cfg.form.addEventListener('submit', function () {
            if (!cfg.btn.disabled) {
                cfg.btn.classList.add('loading');
                cfg.btn.disabled = true;
            }
        });
    });

    function handleFile(file, cfg) {
        if (!file) {
            cfg.selectedRow.style.display = 'none';
            cfg.btn.disabled = true;
            return;
        }
        if (!file.name.toLowerCase().endsWith('.csv')) {
            alert('Please select a valid .csv file.');
            cfg.input.value               = '';
            cfg.selectedRow.style.display = 'none';
            cfg.btn.disabled              = true;
            return;
        }
        cfg.selectedName.textContent   = file.name;
        cfg.selectedRow.style.display  = 'flex';
        cfg.btn.disabled               = false;
    }
})();
</script>
@endpush
