<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_2_7_2;

/**
 * Update
 */
class Updater
{
    public $version_suffix = '';

    /**
     * Do Update
     *
     * @return TRUE
     */
    public function do_update()
    {
        ee()->load->dbforge();

        $steps = new \ProgressIterator(
            array(
                '_clean_quick_tabs',
            )
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    /**
     * Clean up the quick tab links so they no longer have index.php and session
     * ID in them
     *
     * NOTE: This is duplicated from the 2.7.0 updater because it originally
     * only checked for index.php, but the user could be using admin.php or
     * anything-else-in-the-world.php
     *
     * @return void
     */
    protected function _clean_quick_tabs()
    {
        $members = ee()->db->select('member_id, quick_tabs')
            ->where('quick_tabs IS NOT NULL')
            ->like('quick_tabs', '.php')
            ->get('members')
            ->result_array();

        if (! empty($members)) {
            foreach ($members as $index => $member) {
                $members[$index]['quick_tabs'] = $this->_clean_quick_tab_links($member['quick_tabs']);
            }

            ee()->db->update_batch('members', $members, 'member_id');
        }
    }

    /**
     * Remove the index.php and Session ID from quick tabs
     * @param  string $string Quick Tab string
     * @return string         Cleaned up quick tab string
     */
    private function _clean_quick_tab_links($string)
    {
        // Each string is comprised of multiple links broken up by newlines
        $lines = explode("\n", $string);

        foreach ($lines as $index => $line) {
            // Each link is three parts, the first being the name (which is
            // where we're concerned about XSS cleaning), the link, the order
            $links = explode('|', $line);
            $links[1] = substr($links[1], stripos($links[1], 'C='));
            $lines[$index] = implode('|', $links);
        }

        return implode("\n", $lines);
    }
}
/* END CLASS */

// EOF
