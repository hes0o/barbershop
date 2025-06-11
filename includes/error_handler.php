<?php
class ErrorHandler {
    private static $errors = [];
    private static $debug = true; // Set to false in production

    public static function logError($message, $context = [], $file = null, $line = null) {
        $error = [
            'message' => $message,
            'context' => $context,
            'file' => $file,
            'line' => $line,
            'timestamp' => date('Y-m-d H:i:s'),
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        ];

        self::$errors[] = $error;
        
        // Log to PHP error log
        $logMessage = sprintf(
            "[%s] %s in %s:%d\nContext: %s\n",
            $error['timestamp'],
            $message,
            $file ?? 'unknown',
            $line ?? 0,
            json_encode($context)
        );
        error_log($logMessage);

        return $error;
    }

    public static function getErrors() {
        return self::$errors;
    }

    public static function clearErrors() {
        self::$errors = [];
    }

    public static function hasErrors() {
        return !empty(self::$errors);
    }

    public static function getLastError() {
        return end(self::$errors);
    }

    public static function formatErrorResponse($error) {
        if (!self::$debug) {
            // In production, return a user-friendly message
            return [
                'success' => false,
                'message' => 'An error occurred. Please try again later.'
            ];
        }

        // In debug mode, return detailed error information
        return [
            'success' => false,
            'message' => $error['message'],
            'debug' => [
                'file' => $error['file'],
                'line' => $error['line'],
                'context' => $error['context'],
                'timestamp' => $error['timestamp']
            ]
        ];
    }

    public static function handleException($exception) {
        return self::logError(
            $exception->getMessage(),
            ['trace' => $exception->getTraceAsString()],
            $exception->getFile(),
            $exception->getLine()
        );
    }
} 