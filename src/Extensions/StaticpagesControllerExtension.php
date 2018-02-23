<?php

namespace TheWebmen\Staticpages\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Permission;
use TheWebmen\Staticpages\Controllers\StaticpagesController;

class StaticpagesControllerExtension extends DataExtension
{

    public function onAfterInit()
    {
        $flush = $this->owner->getRequest()->getVar('flush');
        if ($flush) {
            if (Permission::check('ADMIN')) {
                $controller = new StaticpagesController();
                if ($flush == 'all') {
                    $controller->removeAll();
                } else {
                    $controller->removeCacheForURL($this->owner->AbsoluteLink());
                }
            }
        } else {
            $noCache = $this->owner->URLSegment == 'Security';
            if (!$noCache && !$this->owner->getRequest()->postVar('IsRender')) {
                if (!method_exists($this->owner->dataRecord, 'generatestatic') || $this->owner->dataRecord->generatestatic()) {
                    $controller = new StaticpagesController();
                    $url = $this->owner->AbsoluteLink();
                    if (!$controller->urlHasCache($url)) {
                        $controller->exportSingle($url);
                    }
                }
            }
        }
    }

}
