{{ $headline }}

@foreach ($lines as $line)
{{ $line }}

@endforeach
@if ($actionUrl && $actionLabel)
{{ $actionLabel }}: {{ $actionUrl }}
@endif

— {{ config('app.name') }}
