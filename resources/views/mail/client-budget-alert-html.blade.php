<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #222;">
    <h1 style="font-size: 1.25rem;">{{ $headline }}</h1>
    @foreach ($lines as $line)
        <p>{!! nl2br(e($line)) !!}</p>
    @endforeach
    @if ($actionUrl && $actionLabel)
        <p><a href="{{ $actionUrl }}" style="display: inline-block; padding: 0.5rem 1rem; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px;">{{ $actionLabel }}</a></p>
    @endif
    <p style="color: #666; font-size: 0.875rem;">— {{ config('app.name') }}</p>
</body>
</html>
