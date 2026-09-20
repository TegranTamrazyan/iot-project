<?php

namespace App\Controllers;

class ErrorController
{
    public function show(int $status = 404)
    { // Default error will be 404
        // Predefined function that sets the HTTP status code that the browser receives
        http_response_code($status);
        // Path to match error view file (Concatenation)
        $view = __DIR__ . "/../Views/errors/$status.php";

        // Fallback if error page file doesn't exist
        if (!file_exists($view)) {
            echo "Something Went Wrong!!!";
            return;
        }
        // Load file every time function runs
        // Using require rather than require_once ensures the error page is displayed even if it was included earlier in the request
        require $view;
    }
}
