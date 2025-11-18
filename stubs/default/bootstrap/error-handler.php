<?php

if (!function_exists('handleError')) {
    function handleError($errno, $errstr, $errfile, $errline): bool
    {
    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - {$errstr}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a1a;
            color: #e0e0e0;
            padding: 20px;
            line-height: 1.6;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .error-header {
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            color: white;
            padding: 30px;
            border-radius: 8px 8px 0 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }
        .error-header h1 {
            font-size: 24px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .error-type {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }
        .error-body {
            background: #2a2a2a;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }
        .error-location {
            background: #1f1f1f;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #ff4b2b;
        }
        .error-location p {
            margin: 5px 0;
            font-size: 14px;
        }
        .error-location strong {
            color: #ff6b6b;
            display: inline-block;
            width: 60px;
        }
        .stack-trace {
            background: #1f1f1f;
            padding: 20px;
            border-radius: 6px;
            border-left: 4px solid #4b4bff;
            max-height: 500px;
            overflow-y: auto;
        }
        .stack-trace h2 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #4b9aff;
        }
        .stack-trace pre {
            font-size: 13px;
            line-height: 1.5;
            color: #c9c9c9;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .stack-trace::-webkit-scrollbar { width: 8px; }
        .stack-trace::-webkit-scrollbar-track { background: #2a2a2a; }
        .stack-trace::-webkit-scrollbar-thumb {
            background: #4b4bff;
            border-radius: 4px;
        }
        .footer {
            margin-top: 20px;
            padding: 15px;
            background: #2a2a2a;
            border-radius: 6px;
            text-align: center;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-header">
            <div class="error-type">PHP Error</div>
            <h1>{$errstr}</h1>
        </div>
        <div class="error-body">
            <div class="error-location">
                <p><strong>File:</strong> {$errfile}</p>
                <p><strong>Line:</strong> {$errline}</p>
                <p><strong>Type:</strong> {$errno}</p>
            </div>
            <div class="stack-trace">
                <h2>Stack Trace</h2>
                <pre>HTML;

    $trace = debug_backtrace();
    $html .= htmlspecialchars(print_r($trace, true));

    $html .= <<<HTML
</pre>
            </div>
        </div>
        <div class="footer">
            This detailed error page is only shown in development mode (APP_DEBUG=true)
        </div>
    </div>
</body>
</html>
HTML;

    echo $html;
    exit(1);
    }
}

if (!function_exists('handleException')) {
    function handleException($exception): void
    {
    $errstr = $exception->getMessage();
    $errfile = $exception->getFile();
    $errline = $exception->getLine();
    $errno = $exception->getCode();

    handleError($errno ?: E_ERROR, $errstr, $errfile, $errline);
    }
}

if (env('APP_DEBUG', false)) {
    set_error_handler('handleError');
    set_exception_handler('handleException');
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
