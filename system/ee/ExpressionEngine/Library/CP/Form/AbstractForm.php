<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\Form;

use ExpressionEngine\Service\Validation\ValidationAware;
use ExpressionEngine\Service\Validation\Validator;
use ExpressionEngine\Service\Validation\Result as ValidateResult;

abstract class AbstractForm implements ValidationAware
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var bool
     */
    protected $base_url = false;

    /**
     * @var array
     */
    protected $rules = [];

    /**
     * @var \string[][]
     */
    protected $options = [
        'yes_no' => [
            '1' => 'Yes',
            '0' => 'No'
        ]
    ];

    /**
     * Should return an EE Shared form array
     * @return mixed
     */
    abstract public function generate(): array;

    /**
     * The form data to populate with
     * @param array $data
     * @return $this
     */
    public function setData(array $data): AbstractForm
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setBaseUrl(string $url): AbstractForm
    {
        $this->base_url = $url;

        return $this;
    }

    /**
     * Returns a piece of data based on $key
     * @param string $key
     * @param string $default
     * @return mixed|string
     */
    public function get(string $key = '', $default = '')
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    /**
     * Validates the submitted data
     * @param array $post_data
     * @return ValidateResult
     */
    public function validate(array $post_data = []): ValidateResult
    {
        //return $this->getValidator()->validate($post_data);
        $this->data = $post_data;
        return $this->getValidator()->validate($this);
    }

    /**
     * @return Validator
     */
    protected function getValidator(): Validator
    {
        $validator = ee('Validation')->make($this->rules);
        return $validator;
    }

    /**
     * @return array
     */
    public function getValidationData(): array
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return $this->rules;
    }

    /**
     * @param string $name
     * @param $value
     * @param $params
     * @param $object
     * @return bool|string
     */
    public function validateMemberExists(string $name, $value, $params, $object)
    {
        $member = ee('Model')
            ->get('Member')
            ->filter('member_id', $value)
            ->first();

        if ($member instanceof \ExpressionEngine\Model\Member\Member) {
            return true;
        }

        return 'error.invalid_member_id';
    }

    /**
     * @param string $name
     * @param $value
     * @param $params
     * @param $object
     * @return bool|string
     */
    public function validateMemberIsSuperAdmin(string $name, $value, $params, $object)
    {
        $member = ee('Model')
            ->get('Member')
            ->filter('member_id', $value)
            ->first();

        if ($member instanceof \ExpressionEngine\Model\Member\Member) {
            if ($member->isSuperAdmin()) {
                return true;
            }
        }

        return 'error.invalid_super_admin_id';
    }

    /**
     * @return array
     */
    protected function roleOptions(): array
    {
        $groups = [];
        $query = ee('Model')
            ->get('Role')
            ->order('name', 'asc')
            ->all();

        foreach ($query as $row) {
            $groups[$row->role_id] = $row->name;
        }

        return $groups;
    }
}
