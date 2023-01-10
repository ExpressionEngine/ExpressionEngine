<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Model\Channel;

use ExpressionEngine\Model\Channel as Core;

/**
 * Channel Entry overloaded
 *
 */
class ChannelEntry extends Core\ChannelEntry
{
    public function set(array $data = array())
    {
        if (ee('Request')->get('field_id')) {
            $field_id = ee('Request')->get('field_id');
            foreach ($data as $key => $value) {
                if ($field_id != $key && strrpos($key, '_' . $field_id) !== (strlen($key) - strlen('_' . $field_id))) {
                    unset($data[$key]);
                }
            }
            if ($field_id == 'title' && !isset($data['url_title'])) {
                $data['url_title'] = $this->getProperty('url_title');
            }
        }

        return parent::set($data);
    }

    /*public function validate()
    {
        if (ee('Request')->get('field_id') == '') {
            return parent::validate();
        }


        return $result;
    }*/
}

// EOF
