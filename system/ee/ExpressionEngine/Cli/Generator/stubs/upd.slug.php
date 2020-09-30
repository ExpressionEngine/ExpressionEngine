<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class {{slug_uc}}_upd {

    public $version = '{{version}}';

    public function install()
    {

        $data = array(
            'module_name'           => '{{slug_uc}}',
            'module_version'        => $this->version,
            'has_cp_backend'        => '{{has_cp_backend}}',
            'has_publish_fields'    => '{{has_publish_fields}}'
        );

        ee()->db->insert('modules', $data);

        {{conditional_hooks}}

        return true;

    }

    public function update($current = '')
    {

        return true;

    }

    public function uninstall()
    {

        ee()->db->where('class', '{{slug_uc}}');

        ee()->db->delete('modules');

        {{conditional_hooks_uninstall}}

        return true;

    }

}