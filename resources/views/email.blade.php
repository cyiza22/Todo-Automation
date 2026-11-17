<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectLine }}</title>
</head>
<body>
    <h1>{{ $subjectLine }}</h1>
    <div>{!! nl2br(e($summaryText)) !!}</div>
</body>
</html>