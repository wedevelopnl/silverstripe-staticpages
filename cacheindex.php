<?php

/**
 * Skip cache
 */
function skipCache(){
    require_once '../index.php';
    exit;
}

if(isset($_GET['flush']) || (isset($_GET['skipcache']) && $_GET['skipcache'] == '1')){
    skipCache();
}

if((isset($_GET['stage']) && $_GET['stage'] == 'Stage') || (isset($_COOKIE['bypassStaticCache']) && $_COOKIE['bypassStaticCache'] == '1')){
    skipCache();
}

/**
 * Vars
 */
$requestUri = strtok($_SERVER['REQUEST_URI'], '?');
$cacheFile = $_SERVER['HTTP_HOST'] . $requestUri;
$cacheFile = rtrim($cacheFile, '/') . '/index.html';

/**
 * Display cache
 */
if(file_exists($cacheFile)){

    echo file_get_contents($cacheFile);
}else{
    skipCache();
}
