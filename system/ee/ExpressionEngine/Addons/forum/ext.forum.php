<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Forum extension
 */
class Forum_ext
{
    public function __construct()
    {
        $this->version = ee('Addon')->get('forum')->getVersion();
    }

    /**
     * Activate extension
     */
    public function activate_extension()
    {
        $hooks = array(
            'member_anonymize' => 'anonymizeMember'
        );

        foreach ($hooks as $hook => $method) {
            ee('Model')->make('Extension', [
                'class' => __CLASS__,
                'method' => $method,
                'hook' => $hook,
                'settings' => [],
                'version' => $this->version,
                'enabled' => 'y'
            ])->save();
        }
    }

    /**
     * Clear out personally-idenfitiable member data we may have
     */
    public function anonymizeMember($member)
    {
        if ($posts = $member->getAssociation('forum:Posts')->get()) {
            $posts->mapProperty('ip_address', [ee('IpAddress'), 'anonymize']);
            $posts->save();
        }

        if ($searches = $member->getAssociation('forum:Search')->get()) {
            $searches->mapProperty('ip_address', [ee('IpAddress'), 'anonymize']);
            $searches->save();
        }

        if ($topics = $member->getAssociation('forum:Topic')->get()) {
            $topics->mapProperty('ip_address', [ee('IpAddress'), 'anonymize']);
            $topics->save();
        }
    }

    /**
     * Disable extension
     */
    public function disable_extension()
    {
        ee('Model')->get('Extension')
            ->filter('class', __CLASS__)
            ->delete();
    }

    /**
     * Update extension
     */
    public function update_extension($current = '')
    {
        if ($current == '' or $current == $this->version) {
            return false;
        }
    }
}

// EOF
