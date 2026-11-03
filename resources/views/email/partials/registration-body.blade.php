<p style="margin: 0 0 16px;">
    Welcome to our platform, we are thrilled to have you on board as a <strong>{{ $data['role'] }}</strong>.
</p>

<p style="margin: 0 0 8px;"><strong>Phone:</strong> {{ $data['phone'] }}</p>
<p style="margin: 0 0 8px;"><strong>Email:</strong> {{ $data['email'] }}</p>
<p style="margin: 0 0 8px;"><strong>Address:</strong> {{ $data['address'] }}</p>
<p style="margin: 0 0 16px;"><strong>Password:</strong> {{ $data['password'] }}</p>

@if ($data['role'] === 'Ambassador')
    <p style="margin: 0;">As an Ambassador, you will have access to exclusive benefits and events. We look forward to working with you!</p>
@else
    <p style="margin: 0;">Thank you for registering. We hope you enjoy using our platform and all the features we offer!</p>
@endif
