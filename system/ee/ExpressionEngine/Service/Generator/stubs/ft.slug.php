<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class {{slug_uc}}_ft extends EE_Fieldtype
{
    public $info = array(
        'name'      => '{{name}}',
        'version'   => '{{version}}',
    );

    public function install()
    {
        return [];
    }

    public function display_global_settings()
    {
        $val = array_merge($this->settings, $_POST);

        $form = '';

        return $form;
    }

    public function save_global_settings()
    {
        return array_merge($this->settings, $_POST);
    }

    public function display_settings($data)
    {
    }

    public function save_settings($data)
    {
        return [];
    }

    public function display_field($data)
    {
        return form_input(array(
            'name'  => $this->field_name,
            'id'    => $this->field_id,
            'value' => $data
        ));
    }

    public function replace_tag($data, $params = array(), $tagdata = false)
    {
        return 'Magic!';
    }
}
