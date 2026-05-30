<?php
echo json_encode([
    'status' => 'OK',
    'method' => $_SERVER['REQUEST_METHOD'],
    'php_version' => phpversion(),
    'curl_available' => function_exists('curl_init')
]);
?>
