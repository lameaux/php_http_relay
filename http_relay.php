<?php

if (empty($_GET['url'])) {
    die('OK');
}

$nodes = array();
$headers_to_remove = array('transfer-encoding', 'via', 'x-powered-by');

// load configuration
if (file_exists('config.php')) {
    include 'config.php';
}

$headers_raw = '';
$headers_sent = false;
$redirect = false;

$url = $_GET['url'];
$step = max(0, isset($_GET['step']) ? $_GET['step'] : 0 );

if ($step == 0 || empty($nodes)) {
    proxy_url($url);
} else {
  $node_url = $nodes[mt_rand(0, count($nodes)-1)];
  proxy_node_url($node_url, $url, $step-1);  
}

function proxy_node_url($node_url, $url, $step) {
    proxy_url($node_url . "?url=" . urlencode($url) . '&step=' . $step );
}

function proxy_url($url) {

    global $headers_raw;
    global $headers_sent;
    global $redirect;

    // reset    
    $headers_raw = '';
    $headers_sent = false;
    $redirect = false;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    
    if (defined('PROXY')) {
        curl_setopt($ch, CURLOPT_PROXY, PROXY);
    }

    // no headers in body
    curl_setopt($ch, CURLOPT_HEADER, 0);
    
    // disable redirects: safemode or openbasedir
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    // return to string 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // curl debug
    //curl_setopt($ch, CURLOPT_VERBOSE, 1); 
    // ssl        
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    // timeouts           
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt ($ch, CURLOPT_TIMEOUT, 30);    
    // user agent
    curl_setopt ($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

    // stream handlers
    curl_setopt($ch, CURLOPT_HEADERFUNCTION , 'curlWriteHeaders');
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, 'curlWriteBody'); // should be the last option

    $response = curl_exec($ch);
    $status = curl_getinfo($ch);
    curl_close($ch);

    if ($response === FALSE && !$redirect) {
        badGatewayHeader();
        die();        
    }

    if (!$headers_sent) {
        if ($status['http_code'] == 301 || $status['http_code'] == 302) {
            $redirect = getRedirectFromHeaders();
        } else {
            sendHeaders();
            die();
        }                
    }

    if ($redirect) {
        proxy_url($redirect);
    }

} 

function curlWriteBody($ch,$str) {
    global $redirect;
    global $headers_sent; 
    if (!$headers_sent) {
        $status = curl_getinfo($ch);
        if ($status['http_code'] == 301 || $status['http_code'] == 302) {
            $redirect = getRedirectFromHeaders();
            return -1;    
        }
        sendHeaders();    
    }
    echo $str;
    return strlen($str);
}

function curlWriteHeaders($ch,$str) {
    global $headers_raw;
    $headers_raw .= $str;
    return strlen($str);
}

function badGatewayHeader() {
      header($_SERVER['SERVER_PROTOCOL'] . ' 502 Bad Gateway', true, 502);
}

function getRedirectFromHeaders() {
    global $headers_raw;
    $matches = array();
    preg_match("/(Location:|URI:)[^(\n)]*/", $headers_raw, $matches);
    $url = trim(str_replace($matches[1],"",$matches[0]));
    $url_parsed = parse_url($url);
    if ($url_parsed && strstr($url_parsed['scheme'], 'http')) {
        return $url;  
    }
    return false;
}

function sendHeaders() {
    global $headers_to_remove;
    global $headers_sent;
    global $headers_raw;
    
    $headers = explode("\r\n", $headers_raw);    
    foreach ($headers as $header) {
        header($header);        
    }
    foreach ($headers_to_remove as $header) {
        header_remove($header);
    }        
    $headers_sent = true;
}