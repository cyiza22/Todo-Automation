<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectLine }}</title>
</head>
<body>
    <div style="max-width: 640px; margin: 0 auto; padding: 24px; color: #202124; font-family: Arial, sans-serif; line-height: 1.5;">
        <h1 style="font-size: 24px; margin: 0 0 20px;">{{ $subjectLine }}</h1>

        @if (trim($summaryText) !== '')
            <div style="white-space: pre-line; margin-bottom: 20px;">{{ $summaryText }}</div>
        @endif

        @if (trim($emailContent) !== '')
            <div style="white-space: pre-line;">{{ $emailContent }}</div>
        @endif
    </div>
</body>
</html>
