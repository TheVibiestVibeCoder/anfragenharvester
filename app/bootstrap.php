<?php

require_once __DIR__ . '/../CacheManager.php';

function app_bootstrap($rootDir) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    ini_set('memory_limit', '256M');
    ini_set('log_errors', 1);
    ini_set('error_log', $rootDir . '/php_errors.log');

    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        $error = date('[Y-m-d H:i:s] ') . "Error [$errno]: $errstr in $errfile on line $errline\n";
        error_log($error);
        echo "<script>console.error(" . json_encode($error) . ");</script>";
        return false;
    });

    set_exception_handler(function($exception) {
        $error = date('[Y-m-d H:i:s] ') . 'Exception: ' . $exception->getMessage() .
            ' in ' . $exception->getFile() . ' on line ' . $exception->getLine() . "\n";
        error_log($error);
        echo "<script>console.error(" . json_encode($error) . ");</script>";
        echo "<div style='background: #ff0000; color: #fff; padding: 20px; margin: 20px;'>";
        echo '<h2>FEHLER!</h2>';
        echo '<pre>' . htmlspecialchars($error) . '</pre>';
        echo '</div>';
    });

    return new CacheManager($rootDir . '/cache', 900);
}
