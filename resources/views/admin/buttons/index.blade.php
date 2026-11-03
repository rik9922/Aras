@extends('layout.app', ['page_name' => 'Button Showcase'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/buttons.css') }}">
    <style>
        .ds-code {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 11px;
            color: #1C4975;
            background: #eff6ff;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
        }
        .ds-label {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .ds-section {
            background: #ffffff;
            border-radius: 12px;
            padding: 28px 32px;
            margin-bottom: 24px;
            border: 1px solid #e8ecf0;
        }
        .ds-section-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .ds-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: flex-end;
        }
        .ds-grid-wide {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .ds-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .ds-item .ds-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
    </style>
@endpush

@section('content')
@php
$colors = [
    ['Primary',   'primary'],
    ['Danger',    'danger'],
    ['Success',   'success'],
    ['Warning',   'warning'],
    ['Info',      'info'],
    ['Theme',     'theme'],
    ['Secondary', 'secondary'],
    ['Dark',      'dark'],
    ['Light',     'light'],
];
@endphp

<div class="container py-4" style="max-width:1160px;">

    {{-- Page header --}}
    <div class="mb-4">
        <h1 class="h4 mb-1 fw-bold" style="color:#1C4975;">Button Design System</h1>
        <p class="text-muted mb-0" style="font-size:13px;">
            Centralized library of all button components. Do <strong>not</strong> replace existing buttons — adopt manually where appropriate.
        </p>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- 1. FILLED BUTTONS                                         --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="ds-section">
        <div class="ds-section-title">Filled Buttons</div>
        <div class="ds-grid">
            @foreach($colors as [$name, $key])
            <div class="ds-item">
                <span class="ds-label">{{ $name }}</span>
                <span class="ds-code">.btn-{{ $key }}-ds</span>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-{{ $key }}-ds">{{ $name }}</button>
                    <button type="button" class="btn-ds btn-{{ $key }}-ds" disabled>Disabled</button>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- 2. OUTLINE BUTTONS                                        --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="ds-section">
        <div class="ds-section-title">Outline Buttons</div>
        <div class="ds-grid">
            @foreach($colors as [$name, $key])
            <div class="ds-item">
                <span class="ds-label">{{ $name }}</span>
                <span class="ds-code">.btn-{{ $key }}-outline</span>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-{{ $key }}-outline">{{ $name }}</button>
                    <button type="button" class="btn-ds btn-{{ $key }}-outline" disabled>Disabled</button>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- 3. ROUNDED BUTTONS (pill)                                 --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="ds-section">
        <div class="ds-section-title">Rounded (Pill) Buttons</div>
        <div class="ds-grid">
            @foreach($colors as [$name, $key])
            <div class="ds-item">
                <span class="ds-label">{{ $name }}</span>
                <span class="ds-code">.btn-{{ $key }}-rounded</span>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-{{ $key }}-rounded">{{ $name }}</button>
                    <button type="button" class="btn-ds btn-{{ $key }}-outline-rounded">Outline</button>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- 4. ICON LEFT                                              --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="ds-section">
        <div class="ds-section-title">Icon Left</div>
        <div class="ds-grid">
            <div class="ds-item">
                <span class="ds-label">Primary</span>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-primary-ds"><i class="fas fa-plus"></i> Add</button>
                    <button type="button" class="btn-ds btn-primary-ds"><i class="fas fa-save"></i> Save</button>
                    <button type="button" class="btn-ds btn-primary-ds"><i class="fas fa-pen"></i> Edit</button>
                    <button type="button" class="btn-ds btn-primary-outline"><i class="fas fa-eye"></i> View</button>
                    <button type="button" class="btn-ds btn-primary-ds"><i class="fas fa-download"></i> Download</button>
                    <button type="button" class="btn-ds btn-primary-ds"><i class="fas fa-upload"></i> Upload</button>
                </div>
            </div>
            <div class="ds-item">
                <span class="ds-label">Danger</span>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-danger-ds"><i class="fas fa-trash-alt"></i> Delete</button>
                    <button type="button" class="btn-ds btn-danger-ds"><i class="fas fa-minus-circle"></i> Remove</button>
                    <button type="button" class="btn-ds btn-danger-outline"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </div>
            <div class="ds-item">
                <span class="ds-label">Success</span>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-success-ds"><i class="fas fa-check"></i> Approve</button>
                    <button type="button" class="btn-ds btn-success-ds"><i class="fas fa-save"></i> Save</button>
                    <button type="button" class="btn-ds btn-success-outline"><i class="fas fa-check-circle"></i> Confirm</button>
                </div>
            </div>
            <div class="ds-item">
                <span class="ds-label">Warning</span>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-warning-ds"><i class="fas fa-archive"></i> Archive</button>
                    <button type="button" class="btn-ds btn-warning-ds"><i class="fas fa-pause"></i> Hold</button>
                    <button type="button" class="btn-ds btn-warning-outline"><i class="fas fa-clock"></i> Pending</button>
                </div>
            </div>
            <div class="ds-item">
                <span class="ds-label">Info</span>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-info-ds"><i class="fas fa-info-circle"></i> Details</button>
                    <button type="button" class="btn-ds btn-info-ds"><i class="fas fa-search"></i> Search</button>
                    <button type="button" class="btn-ds btn-info-outline"><i class="fas fa-filter"></i> Filter</button>
                </div>
            </div>
            <div class="ds-item">
                <span class="ds-label">Theme</span>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-theme-ds"><i class="fas fa-star"></i> Get Started</button>
                    <button type="button" class="btn-ds btn-theme-rounded"><i class="fas fa-heart"></i> Continue</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- 5. ICON RIGHT                                             --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="ds-section">
        <div class="ds-section-title">Icon Right</div>
        <div class="ds-grid">
            <div class="ds-item">
                <span class="ds-label">Primary</span>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-primary-ds">Next <i class="fas fa-arrow-right"></i></button>
                    <button type="button" class="btn-ds btn-primary-ds">Continue <i class="fas fa-chevron-right"></i></button>
                    <button type="button" class="btn-ds btn-primary-outline">Download <i class="fas fa-download"></i></button>
                </div>
            </div>
            <div class="ds-item">
                <span class="ds-label">Success</span>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-success-ds">Approve <i class="fas fa-check"></i></button>
                    <button type="button" class="btn-ds btn-success-rounded">Submit <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>
            <div class="ds-item">
                <span class="ds-label">Theme</span>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-theme-ds">Next <i class="fas fa-chevron-right"></i></button>
                    <button type="button" class="btn-ds btn-theme-rounded">Share <i class="fas fa-share"></i></button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- 6. ICON ONLY                                              --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="ds-section">
        <div class="ds-section-title">Icon Only <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:12px;color:#94a3b8;">— .btn-icon-only</span></div>
        <div class="ds-grid" style="align-items:center;">
            @foreach($colors as [$name, $key])
            <div class="ds-item">
                <span class="ds-label">{{ $name }}</span>
                <div class="ds-preview" style="gap:6px;">
                    <button type="button" class="btn-ds btn-{{ $key }}-ds btn-icon-only" title="Add"><i class="fas fa-plus"></i></button>
                    <button type="button" class="btn-ds btn-{{ $key }}-ds btn-icon-only" title="Edit"><i class="fas fa-pen"></i></button>
                    <button type="button" class="btn-ds btn-{{ $key }}-ds btn-icon-only" title="Delete"><i class="fas fa-trash-alt"></i></button>
                    <button type="button" class="btn-ds btn-{{ $key }}-ds btn-icon-only" title="View"><i class="fas fa-eye"></i></button>
                    <button type="button" class="btn-ds btn-{{ $key }}-outline btn-icon-only" title="Download"><i class="fas fa-download"></i></button>
                    <button type="button" class="btn-ds btn-{{ $key }}-outline btn-icon-only" title="Settings"><i class="fas fa-cog"></i></button>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- 7. LOADING BUTTONS                                        --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="ds-section">
        <div class="ds-section-title">Loading Buttons <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:12px;color:#94a3b8;">— .btn-loading + fa-spinner fa-spin</span></div>
        <div class="ds-grid">
            <div class="ds-item">
                <span class="ds-label">Primary</span>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-primary-ds btn-loading" disabled><i class="fas fa-spinner fa-spin"></i> Saving...</button>
                </div>
            </div>
            <div class="ds-item">
                <span class="ds-label">Danger</span>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-danger-ds btn-loading" disabled><i class="fas fa-spinner fa-spin"></i> Deleting...</button>
                </div>
            </div>
            <div class="ds-item">
                <span class="ds-label">Success</span>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-success-ds btn-loading" disabled><i class="fas fa-spinner fa-spin"></i> Approving...</button>
                </div>
            </div>
            <div class="ds-item">
                <span class="ds-label">Warning</span>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-warning-ds btn-loading" disabled><i class="fas fa-spinner fa-spin"></i> Loading...</button>
                </div>
            </div>
            <div class="ds-item">
                <span class="ds-label">Info</span>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-info-ds btn-loading" disabled><i class="fas fa-spinner fa-spin"></i> Please Wait...</button>
                </div>
            </div>
            <div class="ds-item">
                <span class="ds-label">Outline variant</span>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-primary-outline btn-loading" disabled><i class="fas fa-spinner fa-spin"></i> Processing...</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- 8. BUTTON SIZES                                           --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="ds-section">
        <div class="ds-section-title">Button Sizes</div>
        <div class="ds-grid" style="align-items:flex-end;">
            <div class="ds-item">
                <span class="ds-label">Small — .btn-sm</span>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-primary-ds btn-sm"><i class="fas fa-plus"></i> Add</button>
                    <button type="button" class="btn-ds btn-danger-outline btn-sm"><i class="fas fa-trash-alt"></i> Delete</button>
                    <button type="button" class="btn-ds btn-success-ds btn-sm btn-icon-only"><i class="fas fa-check"></i></button>
                </div>
            </div>
            <div class="ds-item">
                <span class="ds-label">Medium (default) — .btn-md</span>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-primary-ds btn-md"><i class="fas fa-plus"></i> Add</button>
                    <button type="button" class="btn-ds btn-danger-outline btn-md"><i class="fas fa-trash-alt"></i> Delete</button>
                    <button type="button" class="btn-ds btn-success-ds btn-md btn-icon-only"><i class="fas fa-check"></i></button>
                </div>
            </div>
            <div class="ds-item">
                <span class="ds-label">Large — .btn-lg</span>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-primary-ds btn-lg"><i class="fas fa-plus"></i> Add</button>
                    <button type="button" class="btn-ds btn-danger-outline btn-lg"><i class="fas fa-trash-alt"></i> Delete</button>
                    <button type="button" class="btn-ds btn-success-ds btn-lg btn-icon-only"><i class="fas fa-check"></i></button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- 9. BLOCK BUTTONS                                          --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="ds-section">
        <div class="ds-section-title">Block (Full Width) Buttons <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:12px;color:#94a3b8;">— .btn-block</span></div>
        <div class="row g-3">
            <div class="col-md-4">
                <button type="button" class="btn-ds btn-primary-ds btn-block"><i class="fas fa-check"></i> Submit</button>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn-ds btn-success-rounded btn-block"><i class="fas fa-arrow-right"></i> Continue</button>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn-ds btn-theme-ds btn-block"><i class="fas fa-credit-card"></i> Proceed to Payment</button>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn-ds btn-danger-outline btn-block"><i class="fas fa-times"></i> Cancel</button>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn-ds btn-primary-outline btn-block btn-loading" disabled><i class="fas fa-spinner fa-spin"></i> Processing...</button>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- 10. CIRCULAR ICON BUTTONS                                 --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="ds-section">
        <div class="ds-section-title">Circular Icon Buttons</div>
        <div class="ds-grid" style="align-items:center;">
            @foreach($colors as [$name, $key])
            <div class="ds-item">
                <span class="ds-label">{{ $name }}</span>
                <div class="ds-preview" style="gap:8px;align-items:center;">
                    <button type="button" class="btn-circle btn-circle-{{ $key }} btn-circle-sm" title="Small"><i class="fas fa-plus"></i></button>
                    <button type="button" class="btn-circle btn-circle-{{ $key }} btn-circle-md" title="Medium"><i class="fas fa-pen"></i></button>
                    <button type="button" class="btn-circle btn-circle-{{ $key }} btn-circle-lg" title="Large"><i class="fas fa-trash-alt"></i></button>
                    <button type="button" class="btn-circle btn-circle-{{ $key }} btn-circle-md" title="View"><i class="fas fa-eye"></i></button>
                    <button type="button" class="btn-circle btn-circle-{{ $key }} btn-circle-md" disabled title="Disabled"><i class="fas fa-lock"></i></button>
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-3" style="font-size:12px;color:#64748b;">
            Sizes: <code class="ds-code">.btn-circle-sm</code> 32px &nbsp;·&nbsp;
            <code class="ds-code">.btn-circle-md</code> 42px &nbsp;·&nbsp;
            <code class="ds-code">.btn-circle-lg</code> 54px
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- 11. SPLIT BUTTONS                                         --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="ds-section">
        <div class="ds-section-title">Split Buttons <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:12px;color:#94a3b8;">— UI only, no functionality</span></div>
        <div class="ds-grid">
            <div class="ds-item">
                <span class="ds-label">Primary</span>
                <div class="ds-preview">
                    <div class="btn-split-group">
                        <button type="button" class="btn-ds btn-primary-ds btn-split-main"><i class="fas fa-save"></i> Save</button>
                        <button type="button" class="btn-ds btn-primary-ds btn-split-arrow"><i class="fas fa-chevron-down"></i></button>
                    </div>
                </div>
            </div>
            <div class="ds-item">
                <span class="ds-label">Danger</span>
                <div class="ds-preview">
                    <div class="btn-split-group">
                        <button type="button" class="btn-ds btn-danger-ds btn-split-main"><i class="fas fa-trash-alt"></i> Delete</button>
                        <button type="button" class="btn-ds btn-danger-ds btn-split-arrow"><i class="fas fa-chevron-down"></i></button>
                    </div>
                </div>
            </div>
            <div class="ds-item">
                <span class="ds-label">Success</span>
                <div class="ds-preview">
                    <div class="btn-split-group">
                        <button type="button" class="btn-ds btn-success-ds btn-split-main"><i class="fas fa-check"></i> Approve</button>
                        <button type="button" class="btn-ds btn-success-ds btn-split-arrow"><i class="fas fa-chevron-down"></i></button>
                    </div>
                </div>
            </div>
            <div class="ds-item">
                <span class="ds-label">Outline Variant</span>
                <div class="ds-preview">
                    <div class="btn-split-group">
                        <button type="button" class="btn-ds btn-primary-outline btn-split-main" style="border-right-color:var(--btn-primary);"><i class="fas fa-download"></i> Export</button>
                        <button type="button" class="btn-ds btn-primary-outline btn-split-arrow"><i class="fas fa-chevron-down"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- 12. BADGE BUTTONS                                         --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="ds-section">
        <div class="ds-section-title">Badge Buttons <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:12px;color:#94a3b8;">— .btn-badge + .badge-count</span></div>
        <div class="ds-grid">
            <div class="ds-item">
                <span class="ds-label">Primary</span>
                <div class="ds-preview" style="padding-top:10px;">
                    <button type="button" class="btn-ds btn-primary-ds btn-badge">
                        <i class="fas fa-bell"></i> Notifications
                        <span class="badge-count">12</span>
                    </button>
                </div>
            </div>
            <div class="ds-item">
                <span class="ds-label">Info</span>
                <div class="ds-preview" style="padding-top:10px;">
                    <button type="button" class="btn-ds btn-info-ds btn-badge">
                        <i class="fas fa-envelope"></i> Messages
                        <span class="badge-count">4</span>
                    </button>
                </div>
            </div>
            <div class="ds-item">
                <span class="ds-label">Success</span>
                <div class="ds-preview" style="padding-top:10px;">
                    <button type="button" class="btn-ds btn-success-ds btn-badge">
                        <i class="fas fa-shopping-cart"></i> Orders
                        <span class="badge-count">18</span>
                    </button>
                </div>
            </div>
            <div class="ds-item">
                <span class="ds-label">Outline + badge</span>
                <div class="ds-preview" style="padding-top:10px;">
                    <button type="button" class="btn-ds btn-primary-outline btn-badge">
                        <i class="fas fa-star"></i> Reviews
                        <span class="badge-count">7</span>
                    </button>
                </div>
            </div>
            <div class="ds-item">
                <span class="ds-label">Icon-only + badge</span>
                <div class="ds-preview" style="padding-top:10px;gap:12px;">
                    <button type="button" class="btn-ds btn-primary-ds btn-icon-only btn-badge">
                        <i class="fas fa-bell"></i>
                        <span class="badge-count">3</span>
                    </button>
                    <button type="button" class="btn-ds btn-info-ds btn-icon-only btn-badge">
                        <i class="fas fa-envelope"></i>
                        <span class="badge-count">9</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- 13. DISABLED STATE                                        --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="ds-section">
        <div class="ds-section-title">Disabled State — All Variants</div>
        <div class="ds-grid">
            @foreach($colors as [$name, $key])
            <div class="ds-item">
                <span class="ds-label">{{ $name }}</span>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-{{ $key }}-ds" disabled>Filled</button>
                    <button type="button" class="btn-ds btn-{{ $key }}-outline" disabled>Outline</button>
                    <button type="button" class="btn-ds btn-{{ $key }}-rounded" disabled>Rounded</button>
                    <button type="button" class="btn-ds btn-{{ $key }}-ds btn-icon-only" disabled><i class="fas fa-lock"></i></button>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- 15. ADDITIONAL COLOR FAMILIES                             --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="ds-section">
        <div class="ds-section-title">Additional Color Families</div>
        <div class="ds-grid">

            {{-- Secondary --}}
            <div class="ds-item">
                <span class="ds-label">Secondary <code class="ds-code" style="margin-left:4px;">#6C757D</code></span>
                <p style="font-size:11px;color:#94a3b8;margin:0 0 6px;">General actions — Edit, View, Manage</p>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-secondary-ds">Secondary</button>
                    <button type="button" class="btn-ds btn-secondary-outline">Outline</button>
                    <button type="button" class="btn-ds btn-secondary-rounded">Rounded</button>
                    <button type="button" class="btn-ds btn-secondary-outline-rounded">Outline Pill</button>
                    <button type="button" class="btn-ds btn-secondary-ds" disabled>Disabled</button>
                </div>
            </div>

            {{-- Dark --}}
            <div class="ds-item">
                <span class="ds-label">Dark <code class="ds-code" style="margin-left:4px;">#212529</code></span>
                <p style="font-size:11px;color:#94a3b8;margin:0 0 6px;">Power actions — Advanced Settings, View Logs</p>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-dark-ds">Dark</button>
                    <button type="button" class="btn-ds btn-dark-outline">Outline</button>
                    <button type="button" class="btn-ds btn-dark-rounded">Rounded</button>
                    <button type="button" class="btn-ds btn-dark-outline-rounded">Outline Pill</button>
                    <button type="button" class="btn-ds btn-dark-ds" disabled>Disabled</button>
                </div>
            </div>

            {{-- Light --}}
            <div class="ds-item">
                <span class="ds-label">Light <code class="ds-code" style="margin-left:4px;">#F8F9FA</code></span>
                <p style="font-size:11px;color:#94a3b8;margin:0 0 6px;">Minimal actions — Cancel, Close, Dismiss</p>
                <div class="ds-preview">
                    <button type="button" class="btn-ds btn-light-ds">Light</button>
                    <button type="button" class="btn-ds btn-light-outline">Outline</button>
                    <button type="button" class="btn-ds btn-light-rounded">Rounded</button>
                    <button type="button" class="btn-ds btn-light-outline-rounded">Outline Pill</button>
                    <button type="button" class="btn-ds btn-light-ds" disabled>Disabled</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- 16. FEATURE ACTION BUTTONS                                --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="ds-section">
        <div class="ds-section-title">Feature Action Buttons</div>
        <p style="font-size:13px;color:#475569;margin-bottom:20px;">
            Semantic convenience classes. Each pre-wires a color — just add <code class="ds-code">.btn-ds</code> + the semantic class.
        </p>

        @php
        $featureGroups = [
            'Navigation' => [
                ['Back',    'btn-back',     'fa-arrow-left',    'btn-back',    'Navigate to previous page'],
                ['Next',    'btn-next',     'fa-arrow-right',   'btn-next',    'Proceed to next step'],
            ],
            'Search & Filter' => [
                ['Search',         'btn-search',   'fa-magnifying-glass', 'btn-search',  'Trigger a search'],
                ['Find',           'btn-search',   'fa-magnifying-glass', 'btn-search',  'Find items'],
                ['Filter',         'btn-filter',   'fa-filter',           'btn-filter',  'Open filter panel'],
                ['Apply Filter',   'btn-filter',   'fa-filter',           'btn-filter',  'Apply active filters'],
                ['Reset Filters',  'btn-reset',    'fa-rotate-left',      'btn-reset',   'Clear all active filters'],
                ['Refresh',        'btn-refresh',  'fa-rotate',           'btn-refresh', 'Reload/sync data'],
            ],
            'CRUD Actions' => [
                ['Save',    'btn-save',     'fa-floppy-disk',   'btn-save',    'Save changes'],
                ['Submit',  'btn-submit',   'fa-paper-plane',   'btn-submit',  'Submit form or data'],
                ['Approve', 'btn-approve',  'fa-circle-check',  'btn-approve', 'Approve a record'],
                ['Reject',  'btn-reject',   'fa-circle-xmark',  'btn-reject',  'Reject a record'],
                ['Delete',  'btn-delete',   'fa-trash',         'btn-delete',  'Delete a record'],
                ['View',    'btn-view',     'fa-eye',           'btn-view',    'View details'],
                ['Hide',    'btn-hide',     'fa-eye-slash',     'btn-hide',    'Hide or deactivate'],
            ],
            'Import / Export' => [
                ['Export CSV',     'btn-export',   'fa-file-export',  'btn-export',   'Export data as CSV'],
                ['Export Excel',   'btn-export',   'fa-file-export',  'btn-export',   'Export data as Excel'],
                ['Download Report','btn-download',  'fa-download',     'btn-download', 'Download generated report'],
                ['Import',         'btn-import',   'fa-file-import',  'btn-import',   'Import data from file'],
                ['Upload File',    'btn-upload',   'fa-upload',       'btn-upload',   'Upload a file'],
            ],
            'File & Print' => [
                ['Print',         'btn-print',  'fa-print',     'btn-print',  'Send to printer'],
                ['Print Invoice',  'btn-print',  'fa-print',     'btn-print',  'Print invoice document'],
                ['Copy',          'btn-copy',   'fa-copy',      'btn-copy',   'Copy to clipboard or duplicate'],
                ['Duplicate',     'btn-copy',   'fa-copy',      'btn-copy',   'Duplicate a record'],
                ['Share',         'btn-share',  'fa-share-nodes','btn-share',  'Share via link or invite'],
                ['Send Link',     'btn-share',  'fa-link',      'btn-share',  'Send a sharable link'],
            ],
            'Archive & Restore' => [
                ['Archive',       'btn-archive', 'fa-box-archive',  'btn-archive', 'Archive a record'],
                ['Hold',          'btn-archive', 'fa-pause',        'btn-archive', 'Place on hold'],
                ['Restore',       'btn-restore', 'fa-rotate-left',  'btn-restore', 'Restore from archive'],
            ],
            'System / Auth' => [
                ['Lock',          'btn-lock',     'fa-lock',         'btn-lock',     'Lock access or record'],
                ['Unlock',        'btn-unlock',   'fa-lock-open',    'btn-unlock',   'Unlock access or record'],
                ['Settings',      'btn-settings', 'fa-gear',         'btn-settings', 'Open configuration panel'],
                ['View Logs',     'btn-settings', 'fa-terminal',     'btn-settings', 'Open application logs'],
            ],
        ];
        @endphp

        @foreach($featureGroups as $groupName => $buttons)
        <div class="mb-4">
            <h6 style="font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">{{ $groupName }}</h6>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px;">
                @foreach($buttons as [$label, $semanticClass, $icon, $cssClass, $useCase])
                <div style="background:#f8fafc;border:1px solid #e8ecf0;border-radius:8px;padding:14px 16px;display:flex;flex-direction:column;gap:8px;">
                    <div>
                        <button type="button" class="btn-ds {{ $semanticClass }}">
                            <i class="fas {{ $icon }}"></i> {{ $label }}
                        </button>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:2px;">
                        <code class="ds-code">.{{ $cssClass }}</code>
                        <span style="font-size:11px;color:#94a3b8;">{{ $useCase }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- 14. CSS REFERENCE                                         --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="ds-section">
        <div class="ds-section-title">Quick Reference</div>
        <p style="font-size:13px;color:#475569;margin-bottom:12px;">
            Include the stylesheet: <code class="ds-code">&lt;link rel="stylesheet" href="{{ asset('assets/css/buttons.css') }}"&gt;</code>
        </p>
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;font-size:12px;font-family:monospace;line-height:2.2;overflow-x:auto;">
            <strong style="color:#1C4975;">Base class (required on all btn-ds buttons)</strong><br>
            .btn-ds<br><br>
            <strong style="color:#1C4975;">Color + variant</strong><br>
            .btn-{color}-ds &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;← filled<br>
            .btn-{color}-rounded &nbsp;&nbsp;&nbsp;&nbsp;← filled pill<br>
            .btn-{color}-outline &nbsp;&nbsp;&nbsp;&nbsp;← outline 8px radius<br>
            .btn-{color}-outline-rounded ← outline pill<br><br>
            <strong style="color:#1C4975;">Modifiers</strong><br>
            .btn-icon-only &nbsp;&nbsp;← 44×44 square, no padding<br>
            .btn-loading &nbsp;&nbsp;&nbsp;← disabled + spinner state<br>
            .btn-block &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;← full width<br>
            .btn-sm / .btn-md / .btn-lg ← sizes<br>
            .btn-badge &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;← relative position wrapper for .badge-count<br><br>
            <strong style="color:#1C4975;">Circular (standalone)</strong><br>
            .btn-circle .btn-circle-{color} .btn-circle-sm/md/lg<br><br>
            <strong style="color:#1C4975;">Split group</strong><br>
            .btn-split-group &gt; .btn-split-main + .btn-split-arrow<br><br>
            <strong style="color:#1C4975;">Colors available</strong><br>
            primary · danger · success · warning · info · theme<br>
            secondary · dark · light<br><br>
            <strong style="color:#1C4975;">Semantic classes (btn-ds + semantic class)</strong><br>
            .btn-back · .btn-next · .btn-search · .btn-filter · .btn-reset · .btn-refresh<br>
            .btn-save · .btn-submit · .btn-approve · .btn-reject · .btn-delete · .btn-view · .btn-hide<br>
            .btn-export · .btn-import · .btn-download · .btn-upload<br>
            .btn-print · .btn-copy · .btn-share<br>
            .btn-archive · .btn-restore · .btn-lock · .btn-unlock · .btn-settings
        </div>
    </div>

</div>
@endsection
