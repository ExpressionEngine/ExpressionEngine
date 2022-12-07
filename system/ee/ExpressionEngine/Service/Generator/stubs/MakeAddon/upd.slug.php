<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use ExpressionEngine\Service\Addon\Installer;

class {{slug_uc}}_upd extends Installer
{
    public $has_cp_backend = 'n';
    public $has_publish_fields = 'n';

    public function install()
    {
        parent::install();

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

        return true;
    }
}
