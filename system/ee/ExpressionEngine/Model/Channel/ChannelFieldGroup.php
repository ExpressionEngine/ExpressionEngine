<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Channel;

use ExpressionEngine\Service\Model\Model;
use ExpressionEngine\Service\Model\Collection;

/**
 * Channel Field Group Model
 */
class ChannelFieldGroup extends Model
{
    protected static $_primary_key = 'group_id';
    protected static $_table_name = 'field_groups';

    protected static $_hook_id = 'channel_field_group';

    protected static $_relationships = array(
        'Site' => array(
            'type' => 'belongsTo'
        ),
        'Channels' => array(
            'weak' => true,
            'type' => 'hasAndBelongsToMany',
            'model' => 'Channel',
            'pivot' => array(
                'table' => 'channels_channel_field_groups'
            ),
        ),
        'ChannelFields' => array(
            'weak' => true,
            'type' => 'hasAndBelongsToMany',
            'model' => 'ChannelField',
            'pivot' => array(
                'table' => 'channel_field_groups_fields'
            )
        )
    );

    protected static $_validation_rules = array(
        'group_name' => 'required|xss|noHtml|unique|maxLength[50]',
        'short_name' => 'unique|maxLength[50]|alphaDash|validateNameIsNotReserved|validateUniqueAmongFields',
    );

    protected static $_events = array(
        'beforeValidate',
        'afterUpdate',
    );

    protected $group_id;
    protected $site_id;
    protected $group_name;
    protected $short_name;
    protected $group_description;

    /**
     * Convenience method to fix inflection
     */
    public function createChannelField($data)
    {
        return $this->createChannelFields($data);
    }

    /**
     * The group short name must not intersect with Field names
     */
    public function validateUniqueAmongFields($key, $value, array $params = array())
    {
        // Check to see if we can find a channel field that matches the short name
        $channelFields = $this->getModelFacade()
            ->get('ChannelField')
            ->filter('field_name', $value);

        // Make sure group short name is unique among channel fields
        foreach ($params as $field) {
            $channelFields->filter($field, $this->getProperty($field));
        }

        // If there are any matches, return the lang key of the error
        if ($channelFields->count() > 0) {
            return 'unique_among_channel_fields';
        }

        // check member fields
        $unique = $this->getModelFacade()
            ->get('MemberField')
            ->filter('m_field_name', $value);

        foreach ($params as $field) {
            $unique->filter('m_' . $field, $this->getProperty($field));
        }

        if ($unique->count() > 0) {
            return 'unique_among_member_fields'; // lang key
        }

        return true;
    }

    /**
     * Validate the field name to avoid variable name collisions
     */
    public function validateNameIsNotReserved($key, $value, $params, $rule)
    {
        if (in_array($value, ee()->cp->invalid_custom_field_names())) {
            return lang('reserved_word');
        }

        return true;
    }

    /**
     * short_name did not exist prior to EE 7.3.0
     * we need a setter to set it automatically
     * if if was omitted from model make() call
     */
    public function onBeforeValidate()
    {
        if (empty($this->getProperty('short_name')) && !empty($this->getProperty('group_name'))) {
            $this->setProperty('short_name', substr('field_group_' . preg_replace('/\s+/', '_', strtolower($this->getProperty('group_name'))), 0, 50));
        }
    }

    public function onAfterUpdate($previous)
    {
        foreach ($this->Channels as $channel) {
            foreach ($channel->ChannelLayouts as $layout) {
                $layout->synchronize();
            }
        }
    }

    public function getAllChannels()
    {
        $channels = [];

        foreach ($this->Channels as $channel) {
            $channels[$channel->getId()] = $channel;
        }

        return new Collection($channels);
    }

    public function getNameBadge($prefix = '')
    {
        if (ee()->session->userdata('member_id') == 0) {
            return '';
        }
        if (ee()->session->getMember()->PrimaryRole->RoleSettings->filter('site_id', ee()->config->item('site_id'))->first()->show_field_names == 'y') {
            return ee('View')->make('publish/partials/field_name_badge')->render(['name' => $prefix . $this->short_name]);
        }
        return '';
    }
}

// EOF
