<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to  {{ config('app.name') }}</title>
    @include('layouts.partials.head')
</head>
<style>
    @import url("https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap");
</style>

<body style="font-family: Arial, sans-serif;">
    <table style="border-collapse: collapse;width: 100%;max-width:600px;margin: 0 auto;font-family: 'Montserrat', sans-serif;">
        <thead>
            <tr>
                <th style="background: #2E517C;
                padding: 10px 40px;
                background-size: cover;
                background-position: center;">
                    <table style="width: 100%; height: inherit;">
                        <tr>
                            <td>
                            </td>
                        </tr>
                    </table>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="background: #FAFAFB;padding: 20px 40px;">
                    <p style="color: #333333; font-size: 16px;margin:0 0 5px;">Dear <strong>{{ $name }}</strong></p>
                    <br/>
                    @if($type === 'accepted')
                     <p style="color: #333333; font-size: 16px;margin:0 0 5px;">Your Account Deletion Request has been Approved</p>
                    @else 
                     <p style="color: #333333; font-size: 16px;margin:0 0 5px;">Your Account Deletion Request has been Disapproved</p>
                    @endif
                    
                    
                    <p style="color: #333333; font-size: 16px;">If you have any questions or need assistance, feel free to reach out to our support team.</p>
                    <div style="margin: 20px 0;width: 100%;height:1px;background: #E2E2EA;"></div>
                    <p style="text-align:center;margin:0;font-size: 14px;">Email sent by {{ config('app.name') }}</p>
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>