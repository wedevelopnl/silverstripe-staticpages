<?php

/**
 * Skip cache
 */
function skipCache(){
    require_once '../index.php';
    exit;
}

//Flush and skip
if(isset($_GET['flush']) || (isset($_GET['skipcache']) && $_GET['skipcache'] == '1')){
    skipCache();
}

//Static mode
if((isset($_GET['stage']) && $_GET['stage'] == 'Stage') || (isset($_COOKIE['bypassStaticCache']) && $_COOKIE['bypassStaticCache'] == '1')){
    skipCache();
}

//Cache config
$staticpages_config = [];
if(file_exists('../staticpages_config.php')){
    require '../staticpages_config.php';
}

//URL
$requestUri = strtok($_SERVER['REQUEST_URI'], '?');
$cacheFileBase = $_SERVER['HTTP_HOST'] . $requestUri;
$cacheFile = rtrim($cacheFileBase, '/') . '/index.html';

//Query string
if(array_key_exists('query_params', $staticpages_config)){
    parse_str($_SERVER['QUERY_STRING'], $queryArray);
    $queryString = [];
    foreach($staticpages_config['query_params'] as $queryParam){
        if(array_key_exists($queryParam, $queryArray)){
            $queryString[$queryParam] = $queryArray[$queryParam];
        }
    }
    if(count($queryString)){
        ksort($queryString);
        $queryString = md5(http_build_query($queryString));
        $cacheFile = rtrim($cacheFileBase, '/') . '/' . $queryString . '.html';
    }
}

//Display cache
if(file_exists($cacheFile)){
    echo file_get_contents($cacheFile);
}else{
    skipCache();
}
