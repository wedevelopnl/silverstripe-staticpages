# SilverStripe Static Pages

## Introduction

Generate static cache for pages

## Requirements

* SilverStripe CMS ^4.0

## Installation

```
composer require "thewebmen/silverstripe-staticpages"
```
Run the task: dev/tasks/install-staticpages

## How to use
By default the module creates static pages for all SiteTree pages, you can change this by adding the method "generatestatic" on a page object and return false if that pages does not need a static version.
Example:
```
public function generatestatic(){
    if($this->URLSegment == 'contact-us'){
        return false;
    }
    return true;
}
```
If you publish a page with static change then the system wil remove the cache for that page and all pages returned by the optional "urlsAffectedByThisPage" method, make sure that this method returns absolute urls.
Example:
```
public function urlsAffectedByThisPage(){
    $children = SiteTree::get()->filter('ParentID', $this->ID);
    $urls = [];
    foreach($children as $child){
    $urls[] = $child->AbsoluteURL();
    }
    return $urls;
}
```

## View the uncached version
Uncached versiones are served when viewing the website in stage mode are when you add ?skipcache=1 to the url

## Dynamic content
If your page contains dynamic content and/or forms then you need to ajax them

## Tasks
There are three tasks available:
- dev/tasks/install-staticpages (create the staticpages folder, create a symlink and modify the htaccess)
- dev/tasks/staticpages (generate all static pages, most of the times not needed)
- dev/tasks/flush-staticpages (remove all generated pages, useful after a template change)

## Flushing the cache
You can flush the cache just like the regular silverstripe cache by adding ?flush=1 or ?flush=all to the url, where all deletes all static cache and 1 deletes only the cache for the current page.
This works only for logged in users with admin rights.
