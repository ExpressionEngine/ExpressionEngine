<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Generator_upd
{
    public $version = '1.0.0';

    public function install()
    {
        $data = array(
            'module_name' => 'Generator',
            'module_version' => $this->version,
            'has_cp_backend' => 'n',
            'has_publish_fields' => 'n'
        );

        ee()->db->insert('modules', $data);
    }

    public function update($current = '')
    {
        return true;
    }

    public function uninstall()
    {
        ee()->db->where('class', 'Generator');

        ee()->db->delete('modules');
    }
}
