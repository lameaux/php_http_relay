<?php

//set_time_limit(60);

// configuration
define('PROXY', 'internet2.eu.wallstreetsystems.com:8080');

$nodes = array('http://localhost:8000/http_relay.php');

if (empty($_GET['url'])) {
    die('OK');
}

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
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if (defined(PROXY)) {
        curl_setopt($ch, CURLOPT_PROXY, PROXY);
    }

    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    //curl_setopt($ch, CURLOPT_VERBOSE, 1); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
           
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt ($ch, CURLOPT_TIMEOUT, 30);    
    
    curl_setopt ($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    $response = curl_exec($ch);
    curl_close($ch);
    
    list($headers_raw, $body) = explode("\r\n\r\n", $response, 2);
    $headers = explode("\r\n", $headers_raw);
    foreach ($headers as $header) {
        header($header);        
    }   
    echo $body;
} 
