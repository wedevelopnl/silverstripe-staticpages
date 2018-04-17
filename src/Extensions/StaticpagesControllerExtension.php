<?php
namespace TheWebmen\Staticpages\Extensions;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Permission;
use TheWebmen\Staticpages\Controllers\StaticpagesController;
class StaticpagesControllerExtension extends DataExtension
{
    public function onAfterInit()
    {
        if (!$this->owner->getRequest()->postVar('IsRender')) {
            $flush = $this->owner->getRequest()->getVar('flush');
            if ($flush) {
                if (Director::isDev() || Permission::check('ADMIN')) {
                    $controller = new StaticpagesController();
                    if ($flush == 'all') {
                        $controller->removeAll();
                    } else {
                        $controller->removeCacheForURL($this->owner->AbsoluteLink());
                    }
                }
            } else {
                $noCache = $this->owner->URLSegment == 'Security';
                if (!$noCache) {
                    if (!method_exists($this->owner->dataRecord, 'generatestatic') || $this->owner->dataRecord->generatestatic()) {
                        $controller = new StaticpagesController();
                        $url = rtrim(Director::absoluteBaseURL() . $this->owner->getRequest()->getURL(), '/') . '/';
                        $isHomepage = $url == Director::absoluteBaseURL() . 'home/';
                        $renderCache = true;

                        if($isHomepage){
                            $url = $this->owner->AbsoluteLink();
                        }

                        if(!$isHomepage && $url != $this->owner->AbsoluteLink()){
                            if(method_exists($this->owner, 'generatestatic_actions') ){
                                $cachedActions = $this->owner->generatestatic_actions();
                                if(!in_array($this->owner->getRequest()->param('Action'), $cachedActions)){
                                    $renderCache = false;
                                }
                            }else{
                                $renderCache = false;
                            }
                        }

                        if ($renderCache && !$controller->urlHasCache($url, true)) {
                            $controller->exportSingle($url, true);
                        }
                    }
                }
            }
        }
    }
}
