<p style="margin: 0 0 16px; font-weight: 600; color: #111827;">{{ $headline }}</p>

@foreach ($lines as $line)
    <p style="margin: 0 0 12px;">{!! nl2br(e($line)) !!}</p>
@endforeach
