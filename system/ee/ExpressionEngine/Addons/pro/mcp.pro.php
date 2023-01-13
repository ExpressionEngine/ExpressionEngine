<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

 /**
 * Pro Module control panel
 */
class Pro_mcp
{
    private $sidebar;
    private $hasValidLicense = false;
    /**
     * Constructor
     *
     * @access public
     */
    public function __construct()
    {
        ee()->load->library('file_field');
        ee()->lang->load('settings');
        ee()->lang->load('pro');
        $this->hasValidLicense = ee('pro:Access')->hasRequiredLicense(true);
    }

    public function index()
    {
        ee()->functions->redirect(ee('CP/URL')->make('settings/pro/general'));
    }

    /**
     * Controller method for the branding settings page
     *
     * @access public
     * @return void
     */
    public function branding()
    {
    }

    /**
     * Controller method for the general settings page
     *
     * @access public
     * @return void
     */
    public function general()
    {
        ee()->functions->redirect(ee('CP/URL')->make('settings/pro/general'));
    }

    //somewhat hacky redirect to EE settings controller
    //for jump menu
    public function cookies()
    {
        ee()->functions->redirect(ee('CP/URL')->make('settings/pro/cookies'));
    }
}
