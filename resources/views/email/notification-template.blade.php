<!DOCTYPE html>
<html lang="en">
 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Aras promotion</title>
</head>
 
<body style="font-family: Arial, sans-serif; background-color: #f0ece1; height: 100%; margin: 0;">
    <div style="display: flex; flex-direction: column; height: 100%; justify-content: space-between;">
        <div style="width: 100%;">
            <div style="flex: 1; margin: 20px auto; width: 100%; max-width: 800px; background-color: white; overflow: hidden;">
                <div style="background: #2E517C; text-align: center; padding: 10px 0;">
                    <img src="https://aras.betaplanets.com/assets/images/logo.png" alt="Logo" height="80px" style="object-fit: contain;">
                </div>
                <div style="padding: 1rem;">
                    <div>
                        <p>{!! $data['content'] !!}</p>
                        <p>Best regards,<br>
                            <b>The Aras Promotion Team</b>
                        </p>
                    </div>
                </div>
                <div style="text-align: center; padding: 20px; background-color: #f8f9fa;"></div>
            </div>
        </div>
    </div>
</body>
 
</html>