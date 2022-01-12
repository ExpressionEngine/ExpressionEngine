<?php

/**
 * Extension for the Spam Module to add a user-friendly application sub nav
 */
class Spam_ext
{
    public $settings = array();
    public $version;

    public function __construct()
    {
        $addon = ee('Addon')->get('spam');
        $this->version = $addon->getVersion();
    }

    /**
     * Add the Spam Menu
     *
     * @param object $menu ExpressionEngine\Service\CustomMenu\Menu
     */
    public function addSpamMenu($menu)
    {
        ee()->lang->load('spam');

        $trapped_spam = ee()->db->select('content_type, COUNT(trap_id) AS total_spam')
            ->group_by('content_type')
            ->get('spam_trap')
            ->result();

        $total_spam = 0;

        foreach ($trapped_spam as $trapped) {
            $total_spam += $trapped->total_spam;
        }

        $sub = $menu->addSubmenu(lang('spam_queue') . ' (' . $total_spam . ')');
        $sub->withFilterLink(lang('filter_by_type'), ee('CP/URL')->make('addons/settings/spam'));

        foreach ($trapped_spam as $trapped) {
            ee()->lang->load($trapped->content_type);

            $sub->addItem(
                lang($trapped->content_type) . ' (' . $trapped->total_spam . ')',
                ee('CP/URL')->make('addons/settings/spam', array('content_type' => $trapped->content_type))
            );
        }
    }

    /**
     * Activate Extension
     */
    public function activate_extension()
    {
        ee('Model')->make('Extension', [
            'class' => __CLASS__,
            'method' => 'addSpamMenu',
            'hook' => 'cp_custom_menu',
            'settings' => [],
            'version' => $this->version,
            'enabled' => 'y'
        ])->save();
    }

    /**
     * Disable Extension
     */
    public function disable_extension()
    {
        ee('Model')->get('Extension')
            ->filter('class', __CLASS__)
            ->delete();
    }
}
// END CLASS

// EOF
