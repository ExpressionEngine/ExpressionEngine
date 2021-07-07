<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class {{slug_uc}}_mcp
{
    public function index()
    {
        $html = '<p>Time to make magic</p>';

        return [
            'body'  => $html,
            'breadcrumb' => [
                ee('CP/URL')->make('addons/settings/{{slug}}')->compile() => lang('{{slug}}')
            ],
            'heading' => lang('{{slug}}_settings'),
        ];
    }
}
