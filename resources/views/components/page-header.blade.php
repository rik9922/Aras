<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0">{{ $title }}</h4>

            @if ($subtitle)
                <small class="text-muted">{!! $subtitle !!}</small>
            @endif

            <div class="fixed-border"></div>
        </div>

        @if (trim($slot))
            <div>
                {{ $slot }}
            </div>
        @endif
    </div>
</div>
