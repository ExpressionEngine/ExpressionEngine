<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Status;

use ExpressionEngine\Service\Model\Model;
use Mexitek\PHPColors\Color;

/**
 * Status Model
 */
class Status extends Model
{
    protected static $_primary_key = 'status_id';
    protected static $_table_name = 'statuses';

    protected static $_hook_id = 'status';

    protected static $_typed_columns = array(
        'site_id' => 'int',
        'group_id' => 'int',
        'status_order' => 'int'
    );

    protected static $_relationships = array(
        'Channels' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'Channel',
            'pivot' => array(
                'table' => 'channels_statuses'
            ),
            'weak' => true,
        ),
        'ChannelEntries' => [
            'type' => 'hasMany',
            'model' => 'ChannelEntry',
            'weak' => true
        ],
        'Roles' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'Role',
            'pivot' => array(
                'table' => 'statuses_roles',
                'left' => 'status_id',
                'right' => 'role_id'
            )
        )
    );

    protected static $_validation_rules = array(
        'status' => 'required|unique|xss',
        'highlight' => 'required|hexColor'
    );

    protected static $_events = array(
        'beforeInsert',
        'afterInsert',
        'afterUpdate'
    );

    protected $status_id;
    protected $status;
    protected $status_order;
    protected $highlight;

    /**
     * Ensures the highlight field has a default value
     *
     * @param str $name The name of the property to fetch
     * @return str The value of the property
     */
    protected function get__highlight()
    {
        // Old data from before validation may be invalid
        $valid = (bool) preg_match('/^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', (string) $this->highlight);

        return $valid ? $this->highlight : '000000';
    }

    /**
     * New statuses get appended
     * roles assigned
     */
    public function onBeforeInsert()
    {
        $status_order = $this->getProperty('status_order');

        if (empty($status_order)) {
            $count = $this->getModelFacade()->get('Status')->count();
            $this->setProperty('status_order', $count + 1);
        }
    }

    /**
     * New status might have same name as the one that was deleted
     * and the entries were left orphan
     * In that case, we establish relationship
     */
    public function onAfterInsert()
    {
        //direct SQL, as we need it to be fast
        ee('db')->where('status', $this->getProperty('status'))->update('channel_titles', ['status_id' => $this->getId()]);
    }

    /**
     * Update the existing entries using this status
     *
     * @param array $previous
     * @return void
     */
    public function onAfterUpdate($previous)
    {
        if (isset($previous['status']) && $previous['status'] != $this->status) {
            //direct SQL, as we need it to be fast
            ee('db')->where('status', $previous['status'])->update('channel_titles', ['status' => $this->status]);
        }
    }

    /**
     * Returns the value and rendered label for option select input display
     *
     * @param  array $use_ids [default FALSE]
     * @return array option component array
     */
    public function getSelectOptionComponent($use_ids = false)
    {
        $status_option = [
            'value' => ($use_ids) ? $this->status_id : $this->status,
            'label' => $this->renderTag()
        ];

        return $status_option;
    }

    public function renderTag()
    {
        $status_name = ($this->status == 'closed' or $this->status == 'open') ? lang($this->status) : $this->status;

        $status_class = str_replace(' ', '_', strtolower((string) $this->status));

        $status_component_style = [];

        if (! in_array($this->status, array('open', 'closed')) && $this->highlight != '') {
            $highlight = new Color($this->highlight);

            $status_component_style = [
                'background-color' => 'var(--ee-bg-blank)',
                'border-color' => '#' . $this->highlight,
                'color' => '#' . $this->highlight,
            ];
        }

        $vars = [
            'label' => $status_name,
            'class' => $status_class,
            'styles' => $status_component_style
        ];

        return ee('View')->make('_shared/status-tag')->render($vars);
    }

    /**
     * Override of the parent validateUnique to alter the lang key if it's a failure.
     *
     * @param String $key    Property name
     * @param String $value  Property value
     * @param Array  $params Rule parameters
     * @return Mixed String if error, TRUE if success
     */
    public function validateUnique($key, $value, array $params = array())
    {
        $valid = parent::validateUnique($key, $value, $params);
        if ($valid !== true) {
            return 'duplicate_status_name';
        }

        return $valid;
    }
}

// EOF
