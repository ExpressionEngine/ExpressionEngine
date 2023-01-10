<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_4_3_3;

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
                'channelXMLsync',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    /**
    * The channel XML language setting could have an incorrect value
    */
    private function channelXMLsync()
    {
        ee()->load->model('admin_model');
        $valid = ee()->admin_model->get_xml_encodings();

        // Get the current channel_lang setting for each channel
        $channels = ee('Model')->get('Channel')->fields('channel_id', 'channel_lang')->all()->getDictionary('channel_id', 'channel_lang');

        foreach ($channels as $channel_id => $lang) {
            if (! isset($valid[$lang])) {
                // Is the xml language setting the full word
                $xml_lang = array_search(strtolower($lang), array_map('strtolower', $valid));

                if ($xml_lang !== false) {
                    // If there's a valid xml_language, update the channel
                    ee()->db->update('channels', array('channel_lang' => $xml_lang), array('channel_id' => $channel_id));
                }
            }
        }
    }
}

// EOF
