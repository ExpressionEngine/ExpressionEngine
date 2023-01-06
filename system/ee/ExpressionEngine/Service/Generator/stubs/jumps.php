<?php
/**
 * Jump Menu 
 *
 * This file must be in your /system/user/addons/{{addon}} directory of your ExpressionEngine installation
 *
 * @package             {{Addon}}
 * @author              
 * @copyright           
 * @link                
 */
use ExpressionEngine\Service\JumpMenu\AbstractJumpMenu;

 class {{Addon}}_jump extends AbstractJumpMenu
 {

    /**
     * Define the add-ons jumps in array below.
     * See Docs for array reference
     * 
     * https://docs.expressionengine.com/latest/development/jump-menu.html
     */

  protected static $items = [
        'index' => array(
            'icon' => 'fa-file',
            'command' => '{{Addon}} index',
            'command_title' => '{{Addon}} index',
            'dynamic' => false,
            'requires_keyword' => false,
            'target' => ''
        ),
        'settings' => array(
            'icon' => 'fa-cog',
            'command' => '{{Addon}} settings',
            'command_title' => '<b>{{Addon}} settings</b>',
            'dynamic' => false,
            'requires_keyword' => false,
            'target' => 'settings'
        )
    ];
}
