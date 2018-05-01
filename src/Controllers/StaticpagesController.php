<?php

namespace TheWebmen\Staticpages\Controllers;

use SilverStripe\Assets\Filesystem;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

class StaticpagesController extends Controller
{

    private $_cachepath = 'staticpages';
    private $_urlsToExport = false;
    private $_paths = [];
    private $_currentQueryString = false;

    /**
     * Get the current query string as md5 hash
     * @return bool|string
     */
    public function currentQueryString()
    {
        if($this->_currentQueryString !== false){
            return $this->_currentQueryString;
        }
        $this->_currentQueryString = '';
        $staticpages_config = [];
        $staticpagesConfigFile = Director::baseFolder() . '/staticpages_config.php';
        if(file_exists($staticpagesConfigFile)){
            require $staticpagesConfigFile;
        }
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
                $this->_currentQueryString = md5(http_build_query($queryString));
            }
        }
        return $this->_currentQueryString;
    }

    /**
     * Generate the path from a url and fix the url for subsites
     * @param $url
     * @return string
     */
    public function transformURL($url)
    {
        if (array_key_exists($url, $this->_paths)) {
            return $this->_paths[$url];
        }
        $parts = parse_url($url);
        //Fix url for subsites
        $httpHost = $_SERVER['HTTP_HOST'];
        if(class_exists('SilverStripe\Subsites\Model\Subsite')){

            $httpHost = \SilverStripe\Subsites\Model\Subsite::currentSubsite()->domain();
        }
        $newURL = str_replace($parts['host'], $httpHost, $url);

        //Create path
        $path = Director::baseFolder() . '/' . $this->_cachepath . '/' . $httpHost . $parts['path'];
        $this->_paths[$url] = [
            'path' => $path,
            'url' => $newURL
        ];
        return $this->_paths[$url];
    }

    /**
     * Get a list of all urls to export
     * @return array|bool
     */
    public function getUrlsToExport()
    {
        if ($this->_urlsToExport) {
            return $this->_urlsToExport;
        }
        $urls = [];
        foreach (SiteTree::get() as $page) {
            if (method_exists($page, 'generatestatic') && !$page->generatestatic()) {
                continue;
            }
            $url = $page->AbsoluteLink();
            $urls[$url] = $url;
            if(method_exists($page, 'generatestatic_actions')){
                foreach($page->generatestatic_actions() as $action){
                    $url = $page->AbsoluteLink($action);
                    $urls[$url] = $url;
                }
            }
        }

        $this->extend('alterUrlsToExport', $urls);
        $this->_urlsToExport = $urls;
        return $this->_urlsToExport;
    }

    /**
     * Export a single page to the cache
     * @param $url The absolute page url
     * @param $withQuery
     */
    public function exportSingle($url, $withQuery = false)
    {
        //Paths
        $transformedURL = $this->transformURL($url);
        $path = $transformedURL['path'];
        $url = $transformedURL['url'];
        $extension = Director::is_ajax() ? '-ajax.html' : '.html';
        if($withQuery && $this->currentQueryString() != ''){
            $url = $url . '?' . $_SERVER['QUERY_STRING'];
            $contentfile = $path . '/' . $this->currentQueryString() . $extension;
        }else {
            $contentfile = $path . '/index' . $extension;
        }

        //Create path
        if (!is_dir($path)) {
            Filesystem::makeFolder($path);
        }

        //Reading mode
        Versioned::set_reading_mode(Versioned::LIVE);

        //Render page
        DataObject::flush_and_destroy_cache();
        $response = Director::test($url, [
            'IsRender' => true
        ]);

        //Write to file
        if ($fh = fopen($contentfile, 'w')) {
            fwrite($fh, $response->getBody());
            fclose($fh);
        }
    }

    /**
     * Check if a url has cache
     * @param $url
     * @param $withQuery
     * @return bool
     */
    public function urlHasCache($url, $withQuery = false)
    {
        $path = $this->transformURL($url)['path'];
        if($withQuery && $this->currentQueryString() != ''){
            $contentfile = $path . '/' . $this->currentQueryString() . '.html';
        }else{
            $contentfile = $path . '/index.html';
        }
        return file_exists($contentfile);
    }

    /**
     * Remove the cache for a url
     * @param $url Absolute page url
     */
    public function removeCacheForURL($url)
    {
        $path = $this->transformURL($url)['path'];
        if(is_dir($path)){
            Filesystem::removeFolder($path);
        }
    }

    /**
     * Do a clean export of all pages
     * @throws \Error
     */
    public function doExport()
    {
        //Install if needed
        if (!$this->isInstalled()) {
            $this->install();
        }

        //Remove old
        $this->removeAll();

        //Export all
        $urls = $this->getUrlsToExport();
        foreach ($urls as $url) {
            $this->exportSingle($url);
        }
    }

    /**
     * Check if the cache is installed
     */
    public function isInstalled()
    {
        $staticpath = Director::baseFolder() . '/' . $this->_cachepath;
        if (!is_dir($staticpath)) {
            return false;
        }
        $cacheIndexTarget = Director::baseFolder() . '/staticpages/cacheindex.php';
        if (!file_exists($cacheIndexTarget)) {
            return false;
        }
        return true;
    }

    /**
     * Do a install
     */
    public function install()
    {
        //Create dir
        $staticpath = Director::baseFolder() . '/' . $this->_cachepath;
        if (!is_dir($staticpath)) {
            $this->removeAll();
            @Filesystem::makeFolder($staticpath);
            if (!is_dir($staticpath)) {
                echo('Create the file ' . $staticpath . ' and make it writable');
                die;
            }
        }

        //Symlink index
        $cacheIndexTarget = Director::baseFolder() . '/staticpages/cacheindex.php';
        if (!file_exists($cacheIndexTarget)) {
            $cacheIndexLocation = Director::baseFolder() . '/vendor/thewebmen/silverstripe-staticpages/cacheindex.php';
            `ln -s $cacheIndexLocation; ln -s $cacheIndexTarget`;
            if (!file_exists($cacheIndexTarget)) {
                echo('Make a symlink at ' . $cacheIndexTarget . ' pointing to ' . $cacheIndexLocation);
                die;
            }
        }

        //Modify htaccess
        $htaccessFile = Director::baseFolder() . '/.htaccess';
        if (!is_writable($htaccessFile)) {
            echo('Make your htaccess file writeable or manually change "RewriteRule .* index.php" into "RewriteRule .* staticpages/cacheindex.php"');
            die;
        }
        $htaccessContent = file_get_contents($htaccessFile);
        $htaccessContent = str_replace('RewriteRule .* index.php', 'RewriteRule .* staticpages/cacheindex.php', $htaccessContent);
        @chmod($htaccessFile, 0777);
        @file_put_contents($htaccessFile, $htaccessContent);
    }

    /**
     * Remove all cache
     */
    public function removeAll($currentSubsiteOnly = false)
    {
        $subDir = '';
        if($currentSubsiteOnly){
            if(class_exists('SilverStripe\Subsites\Model\Subsite')){
                $httpHost = \SilverStripe\Subsites\Model\Subsite::currentSubsite()->domain();
                $subDir = '/' . $httpHost;
            }
        }
        $staticpath = Director::baseFolder() . '/' . $this->_cachepath . $subDir;
        if (is_dir($staticpath)) {
            $dirs = array_filter(glob($staticpath . '/*'), 'is_dir');
            foreach ($dirs as $dir) {
                Filesystem::removeFolder($dir);
            }
        }
    }

}
