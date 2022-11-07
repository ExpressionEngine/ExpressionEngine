<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use ExpressionEngine\Service\Addon\Extension;

class {{slug_uc}}_ext extends Extension
{
    protected $addon_name = '{{slug}}';

    public $settings = [];
    public $version = "{{version}}";

    public function __construct($settings = [])
    {
        $this->settings = $settings;
    }

    public function activate_extension()
    {
        $data = [
{{hook_array}}
        ];

        foreach ($data as $hook) {
            ee()->db->insert('extensions', $hook);
        }
    }

    public function disable_extension()
    {
        ee()->db->where('class', __CLASS__);
        ee()->db->delete('extensions');

        return true;
    }

    public function update_extension($current = '')
    {
        return true;
    }
{{extension_settings}}
{{hook_methods}}
}
