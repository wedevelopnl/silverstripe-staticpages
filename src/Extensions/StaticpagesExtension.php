<?php

namespace TheWebmen\Staticpages\Extensions;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\ORM\DataExtension;
use TheWebmen\Staticpages\Controllers\StaticpagesController;

class StaticpagesExtension extends DataExtension {

    public function RemoveStaticCacheRecursive(){
        $this->RemoveStaticCache();
        $children = SiteTree::get()->filter('ParentID', $this->owner->ID);
        foreach($children as $child){
            $child->RemoveStaticCacheRecursive();
        }
    }

    public function RemoveStaticCache(){
        $url = $this->owner->AbsoluteLink();
        $controller = new StaticpagesController();
        $controller->removeCacheForURL($url);
        if (method_exists($this->owner, 'generatestatic_actions')){
            $cachedActions = $this->owner->generatestatic_actions();
            foreach($cachedActions as $action){
                $controller->removeCacheForURL($this->owner->AbsoluteLink($action));
            }
        }
        if (method_exists($this->owner, 'urlsAffectedByThisPage') && $urls = $this->owner->urlsAffectedByThisPage()) {
            foreach($urls as $url){
                $controller->removeCacheForURL($url);
            }
        }
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();
        $this->RemoveStaticCache();
    }

    public function updateCMSActions(FieldList $actions)
    {
        $actions->addFieldToTab('ActionMenus.MoreOptions', FormAction::create('RemovePageCache', 'Remove page cache')->removeExtraClass('btn-primary')->addExtraClass('btn-secondary'));
        $actions->addFieldToTab('ActionMenus.MoreOptions', FormAction::create('RemovePageAndChildPagesCache', 'Remove page and child pages cache')->removeExtraClass('btn-primary')->addExtraClass('btn-secondary'));
    }

}
