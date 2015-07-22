<?php

if (empty($_GET['url'])) {
    die('OK');
}

$nodes = array();
// load configuration
if (file_exists('config.php')) {
    include 'config.php';
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
    if (defined('PROXY')) {
        curl_setopt($ch, CURLOPT_PROXY, PROXY);
    }

    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0); // safemode or openbasedir
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    //curl_setopt($ch, CURLOPT_VERBOSE, 1); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
           
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt ($ch, CURLOPT_TIMEOUT, 30);    
    
    curl_setopt ($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch);
    curl_close($ch);
    
    if ($response === FALSE) {
        badGateway();
    }

    list($headers_raw, $body) = explode("\r\n\r\n", $response, 2);


    if($status['http_code'] != 200){
      if($status['http_code'] == 301 || $status['http_code'] == 302) {
        $matches = array();
        preg_match("/(Location:|URI:)[^(\n)]*/", $headers_raw, $matches);
        $url = trim(str_replace($matches[1],"",$matches[0]));
        $url_parsed = parse_url($url);
        if (!$url_parsed || !strstr($url_parsed['scheme'], 'http')) {
          badGateway();  
        }
        proxy_url($url);
        die();
      }
    }

    $headers = explode("\r\n", $headers_raw);    
    foreach ($headers as $header) {
        header($header);        
    }
    
    $headers_to_remove = array('transfer-encoding', 'via', 'x-powered-by');
    foreach ($headers_to_remove as $header) {
      header_remove($header);
    }
    
    echo $body;
    die();
} 


function badGateway() {
  header($_SERVER['SERVER_PROTOCOL'] . ' 502 Bad Gateway', true, 502);
  die();
}