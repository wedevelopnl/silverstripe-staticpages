<?php

namespace TheWebmen\Staticpages\Tasks;

use SilverStripe\Dev\BuildTask;
use TheWebmen\Staticpages\Controllers\StaticpagesController;

class FlushStaticpages extends BuildTask {

    private static $segment = 'flush-staticpages';
    protected $title = 'Flush static pages';
    protected $description = 'Remove all static generated pages';

    public function run($request)
    {
        $controller = new StaticpagesController();
        $controller->removeAll();
    }

}
