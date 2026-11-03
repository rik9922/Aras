<p>Your recap details and photos are included below.</p>
<table role="presentation" style="width:100%;border-collapse:collapse;font-size:14px;">
@foreach ($rows as $row)
    @if ($row['Value'] === '')
        <tr><th colspan="2" style="text-align:left;padding:16px 8px 8px;background:#f3f4f6;">{{ $row['Field'] }}</th></tr>
    @else
        <tr><th style="text-align:left;vertical-align:top;padding:8px;border-bottom:1px solid #e5e7eb;">{{ $row['Field'] }}</th><td style="padding:8px;border-bottom:1px solid #e5e7eb;white-space:pre-line;">{{ $row['Value'] }}</td></tr>
    @endif
@endforeach
</table>
@foreach ($photos as $photo)
    <p><strong>{{ $photo['label'] }}</strong></p>
    <a href="{{ $photo['url'] }}"><img src="{{ $photo['src'] }}" alt="{{ $photo['label'] }}" style="max-width:100%;height:auto;" width="520"></a>
@endforeach
