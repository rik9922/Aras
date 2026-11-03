<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
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
                                  <img src="https://aras.betaplanets.com/assets/images/logo.png" alt="" width="80px" height="80px">
                            </td>
                        </tr>
                    </table>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="background: #FAFAFB;padding: 20px 40px;">
                    <h2 class="card-title" style="color:#2E517C; font-size: 22px; font-weight: 600">Dear Admin</h2>
                    <p style="color: #333333; font-size: 16px;margin:0 0 5px;">I hope this message finds you well.</p>
                    <br/>
                    <p style="color: #333333; font-size: 16px;">I am writing to formally request the deletion of my account on your platform. Please consider this email as a request to permanently remove my account and all associated data from your system. Below are the details related to my account.</p>
                    <br/>
                    <p style="color: #333333; font-size: 16px;margin:0 0 5px;">Name <span style="color: #333333; font-size: 16px;margin:0 0 5px;"><strong>{{$name}}</strong></span></p>
                    <p style="color: #333333; font-size: 16px;margin:0 0 5px;">Email <span style="color: #333333; font-size: 16px;margin:0 0 5px;"><strong>{{$email}}</strong></span></p>
                    <p style="color: #333333; font-size: 16px;margin:0 0 5px;">Reason <span style="color: #333333; font-size: 16px;margin:0 0 5px;"><strong>{{$reason}}</strong></span></p>
                    <div style="margin: 20px 0;width: 100%;height:1px;background: #E2E2EA;"></div>
                    <p style="text-align:center;margin:0;font-size: 14px;">Email sent by {{ config('app.name') }}</p>
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>



