@php
    /** @var \Throwable|\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface|null $exception */
    $message = null;
    if (isset($exception)) {
        try {
            $message = $exception->getMessage();
        } catch (\Throwable $e) {
            $message = null;
        }
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Server Error</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Inter, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; padding: 2rem; }
        .card { max-width: 720px; margin: 4rem auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 2rem; box-shadow: 0 1px 2px rgba(15,23,42,.05); }
        h1 { font-size: 1.5rem; margin: 0 0 .5rem; color: #0b3a5a; }
        .label { display: inline-block; background: #FFC40C; color: #0b3a5a; padding: .25rem .5rem; border-radius: .375rem; font-size: .75rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; }
        pre { background: #f1f5f9; padding: 1rem; border-radius: .5rem; overflow-x: auto; font-size: .8rem; color: #334155; white-space: pre-wrap; word-break: break-word; }
        a { color: #0b3a5a; font-weight: 700; }
    </style>
</head>
<body>
    <div class="card">
        <span class="label">500 · Server Error</span>
        <h1>Something went wrong.</h1>
        <p>Our team has been notified. Please try again in a moment or head back to the <a href="{{ url('/') }}">home page</a>.</p>
        @if ($message)
            <p style="margin-top:1.5rem;font-weight:700;color:#334155;">Details</p>
            <pre>{{ $message }}</pre>
        @endif
    </div>
</body>
</html>
