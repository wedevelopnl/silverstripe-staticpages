<?php

namespace TheWebmen\Staticpages\Tasks;

use SilverStripe\Dev\BuildTask;
use TheWebmen\Staticpages\Controllers\StaticpagesController;

class StaticpagesGenerator extends BuildTask {

    private static $segment = 'staticpages';
    protected $title = 'Static pages generator';
    protected $description = 'Generate all static pages';

    public function run($request)
    {
        $controller = new StaticpagesController();
        $controller->doExport();
    }

}
