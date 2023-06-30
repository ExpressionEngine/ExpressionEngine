<?php

use ExpressionEngine\Addons\Pro\Service\Prolet\AbstractProlet;
use ExpressionEngine\Addons\Pro\Service\Prolet\ProletInterface;
use ExpressionEngine\Addons\Pro\Service\Prolet\InitializableProletInterface; //we want this one because we are making an Initializable Prolets

use ExpressionEngine\Library\CP\Table;

class Pro_variables_pro extends AbstractProlet implements InitializableProletInterface
{
    protected $name = 'Pro Variables';

    protected $buttons = []; // No buttons will be shown
    protected $vars;

    public function index()
    {
        $this->vars = &ee()->pro_variables_variable_model;
        $vars = $this->vars->get_meta();
        $local = ee()->input->get('local');
        //table for only local vars passed from parse tag
        echo("<H1>Variables On This Page</H1>");
        ee()->load->library('table');
        ee()->table->set_heading('Variable Name', 'Variable Label', 'Variable Type', 'Early');
        foreach ($local as $var) {
            $id = $var['variable_id'];
            $base = ee()->config->item('base_url');
            $rest = 'admin.php?/cp/addons/settings/pro_variables/edit_var/' . $id;
            $base = $base . $rest;
            $link = '<a href=' . $base . '>' . $var['variable_name'] . ' </a>';
            ee()->table->add_row($link, $var['variable_label'], $var['variable_type'], $var['early_parsing']);
        }
        echo ee()->table->generate();
        //Build table showing all vars
        echo("<H1>All Variables</H1>");
        ee()->load->library('table');
        ee()->table->set_heading('Variable Name', 'Variable Label', 'Variable Type', 'Early');
        foreach ($vars as $var) {
            $id = $var['variable_id'];
            $base = ee()->config->item('base_url');
            $rest = 'admin.php?/cp/addons/settings/pro_variables/edit_var/' . $id;
            $base = $base . $rest;
            $link = '<a href=' . $base . '>' . $var['variable_name'] . ' </a>';
            ee()->table->add_row($link, $var['variable_label'], $var['variable_type'], $var['early_parsing']);
        }
        echo ee()->table->generate();

        return '';
    }
}
