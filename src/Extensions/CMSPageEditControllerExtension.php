<?php

namespace TheWebmen\Staticpages\Extensions;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Extension;

class CMSPageEditControllerExtension extends Extension {

    private static $allowed_actions = [
        'RemovePageCache',
        'RemovePageAndChildPagesCache'
    ];

    public function RemovePageAndChildPagesCache(){

        $pageID = $this->owner->getRequest()->postVar('ID');
        $page = SiteTree::get()->byID($pageID);
        if(!$page || !$page->exists()){
            $this->owner->getResponse()->addHeader(
                'X-Status',
                'Something went wrong'
            );
            return $this->owner->getResponseNegotiator()->respond($this->owner->getRequest());
        }

        $page->RemoveStaticCacheRecursive();

        $this->owner->getResponse()->addHeader(
            'X-Status',
            'Cache removed!'
        );
        return $this->owner->getResponseNegotiator()->respond($this->owner->getRequest());
    }

    public function RemovePageCache(){
        $pageID = $this->owner->getRequest()->postVar('ID');
        $page = SiteTree::get()->byID($pageID);
        if(!$page || !$page->exists()){
            $this->owner->getResponse()->addHeader(
                'X-Status',
                'Something went wrong'
            );
            return $this->owner->getResponseNegotiator()->respond($this->owner->getRequest());
        }

        $page->RemoveStaticCache();

        $this->owner->getResponse()->addHeader(
            'X-Status',
            'Cache removed!'
        );
        return $this->owner->getResponseNegotiator()->respond($this->owner->getRequest());
    }

}
