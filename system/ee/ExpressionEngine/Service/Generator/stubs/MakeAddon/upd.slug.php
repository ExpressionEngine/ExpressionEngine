<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use ExpressionEngine\Service\Addon\Installer;

class {{slug_uc}}_upd extends Installer
{
    public $has_cp_backend = '{{has_cp_backend}}';
    public $has_publish_fields = '{{has_publish_fields}}';

    public function install()
    {
        parent::install();
{{conditional_hooks}}
        return true;
    }

    public function update($current = '')
    {
        // Runs migrations
        parent::update($current);

        return true;
    }

    public function uninstall()
    {
        parent::uninstall();
{{conditional_hooks_uninstall}}
        return true;
    }
}
