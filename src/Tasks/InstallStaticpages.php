<?php

namespace TheWebmen\Staticpages\Tasks;

use SilverStripe\Dev\BuildTask;
use TheWebmen\Staticpages\Controllers\StaticpagesController;

class InstallStaticpages extends BuildTask {

    private static $segment = 'install-staticpages';
    protected $title = 'Install static pages';
    protected $description = 'Create the staticpages folder, create a symlink and modify the htaccess';

    public function run($request)
    {
        $controller = new StaticpagesController();
        $controller->install();
    }

}
