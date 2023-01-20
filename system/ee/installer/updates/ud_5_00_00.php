<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_5_0_0;

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
        $steps = new \ProgressIterator(
            [
                'optInToAnalytics',
                'optInToNews'
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    /**
     * Analytics-collecting is now opt-in for new installs, but continue
     * collecting on existing installs
     */
    private function optInToAnalytics()
    {
        ee()->config->_update_config(['share_analytics' => 'y']);
    }

    /**
     * Showing EE news on the CP homepage is now opt-in for new installs, but
     * continue showing on existing installs
     */
    private function optInToNews()
    {
        ee()->config->_update_config(['show_ee_news' => 'y']);
    }
}

// EOF
