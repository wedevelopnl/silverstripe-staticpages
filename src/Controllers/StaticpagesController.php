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

    /**
     * Convert a url to a path
     * @param $url
     * @return string
     */
    public function urlToPath($url)
    {
        if (array_key_exists($url, $this->_paths)) {
            return $this->_paths[$url];
        }
        $parts = parse_url($url);
        $path = Director::baseFolder() . '/' . $this->_cachepath . '/' . $_SERVER['HTTP_HOST'] . $parts['path'];
//        $path = Director::baseFolder() . '/' . $this->_cachepath . '/' . $parts['host'] . $parts['path'];
        $this->_paths[$url] = $path;
        return $path;
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
        }

        $this->extend('alterUrlsToExport', $urls);
        $this->_urlsToExport = $urls;
        return $this->_urlsToExport;
    }

    /**
     * Export a single page to the cache
     * @param $url The absolute page url
     */
    public function exportSingle($url)
    {
        //Paths
        $path = $this->urlToPath($url);
        $contentfile = $path . '/index.html';

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
     * @return bool
     */
    public function urlHasCache($url)
    {
        $path = $this->urlToPath($url);
        $contentfile = $path . '/index.html';
        return file_exists($contentfile);
    }

    /**
     * Remove the cache for a url
     * @param $url Absolute page url
     */
    public function removeCacheForURL($url)
    {
        $path = $this->urlToPath($url);
        $contentfile = $path . '/index.html';
        if (file_exists($contentfile)) {
            unlink($contentfile);
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
        $cacheIndexTarget = Director::baseFolder() . 'staticpages/cacheindex.php';
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
                throw new \Error('Create the file ' . $staticpath . ' and make it writable');
            }
        }

        //Symlink index
        $cacheIndexTarget = Director::baseFolder() . 'staticpages/cacheindex.php';
        if (!file_exists($cacheIndexTarget)) {
            $cacheIndexLocation = Director::baseFolder() . '/vendor/thewebmen/silverstripe-staticpages/cacheindex.php';
            `ln -s $cacheIndexLocation; ln -s $cacheIndexTarget`;
            if (!file_exists($cacheIndexTarget)) {
                throw new \Error('Make a symlink at ' . $cacheIndexTarget . ' pointing to ' . $cacheIndexLocation);
            }
        }

        //Modify htaccess
        $htaccessFile = Director::baseFolder() . '/.htaccess';
        if (!is_writable($htaccessFile)) {
            throw new \Error('Make your htaccess file writeable or change the pointer to index.php into staticpages/cacheindex.php');
        }
        $htaccessContent = file_get_contents($htaccessFile);
        $htaccessContent = str_replace('RewriteRule .* index.php', 'RewriteRule .* staticpages/cacheindex.php', $htaccessContent);
        @chmod($htaccessFile, 0777);
        @file_put_contents($htaccessFile, $htaccessContent);
    }

    /**
     * Remove all cache
     */
    public function removeAll()
    {
        $staticpath = Director::baseFolder() . '/' . $this->_cachepath;
        if (is_dir($staticpath)) {
            $dirs = array_filter(glob($staticpath . '/*'), 'is_dir');
            foreach ($dirs as $dir) {
                Filesystem::removeFolder($dir);
            }
        }
    }

}
