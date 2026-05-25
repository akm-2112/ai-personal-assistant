<!DOCTYPE html>
<html>
<head>
    <title>Knowledge Files</title>
</head>
<body>
    <h1>Knowledge Files</h1>
    @foreach ($files as $file)
        <p>{{ $file->title }} ({{ $file->category->value ?? $file->category }})</p>
    @endforeach
</body>
</html>
