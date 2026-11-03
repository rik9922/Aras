<p style="margin: 0 0 16px;">
    <strong>Congratulations!</strong> We're thrilled to inform you that you have been approved as an ambassador for
    {{ $event_name ?? '' }}. Your passion and dedication have impressed us, and we can't wait to work with you to make
    this event a great success.
</p>

<p style="margin: 0 0 16px;">Here are some important details to get started:</p>

<p style="margin: 0 0 8px;"><strong>Event Date:</strong> {{ $event_date ?? '' }}</p>
<p style="margin: 0;"><strong>Event Location:</strong> {{ $location ?? '' }}</p>
@if (!empty($app_download_url))
    <p style="margin: 16px 0 0;">Manage your assignments on the Aras Promotions app:</p>
    <p style="margin: 6px 0 0;"><a href="{{ $app_download_url }}">Download the Aras Promotions app</a></p>
@endif
