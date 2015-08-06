<?php

if (empty($_GET['url'])) {
    die('OK');
}

$headers_to_remove = array('transfer-encoding', 'via', 'x-powered-by');

// load configuration
if (file_exists('config.php')) {
    include 'config.php';
}

$phantomjs = 'phantomjs';
if (defined('PHANTOMJS')) {
    $phantomjs = PHANTOMJS;
}

if (defined('PROXY')) {
    $phantomjs .= ' --proxy=' . PROXY;
}

$phantomjs .= ' relay.js ' . $_GET['url'];

$result = shell_exec($phantomjs);
$result = trim($result);

if ($result == 'timeout') {
    badGatewayHeader();
    die();
}

$response = explode("\r\n", $result, 2);
$allheaders = json_decode($response[0], true);

statusHeader($allheaders['status'], $allheaders['statusText']);
/*
$httpheaders = $allheaders['headers'];

foreach ($httpheaders as $header) {
    $header['value'] = str_replace(array("\n","\r"), "", $header['value']);
    header(trim($header['name']) . ': ' . trim($header['value']));        
}
foreach ($headers_to_remove as $header) {
    header_remove($header);
} 
*/
// replace content type
header('Content-type: text/plain');

$body = $response[1];
echo $body;
die();



function badGatewayHeader() {
      header($_SERVER['SERVER_PROTOCOL'] . ' 502 Bad Gateway', true, 502);
}

function statusHeader($code, $text) {
      header($_SERVER['SERVER_PROTOCOL'] . ' ' . $code . ' ' . $text, true, $code);
}


