<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Member\TemplateGenerators;

use ExpressionEngine\Service\TemplateGenerator\AbstractTemplateGenerator;

class Management extends AbstractTemplateGenerator
{
    protected $name = 'member_management_template_generator';

    protected $templates = [
        'index' => 'Members list page',
        'search' => 'Member search page',
        'registration' => 'New member registration',
        'login' => 'Login page',
        'logout' => 'Logout page',
        'forgot-password' => 'Forgot password page',
        'reset-password' => 'Reset password page',
        'email-password-reset' => 'Password reset email',
        'forgot-username' => 'Forgot username page',
        'email-forgot-username' => 'Forgot username email',
        'profile' => 'Public profile page',
        'edit-profile' => 'Edit profile page',
        'edit-avatar' => 'Edit avatar page',
        'roles' => 'List roles for current member',
        'role-groups' => 'List role groups for current member',
    ];

    protected $includes = [
        '_layout',
        'index' => ['templates' => 'search']
    ];

    protected $options = [
        'include_navigation' => [
            'desc' => 'include_navigation_desc',
            'type' => 'toggle',
            'required' => false,
        ],
    ];

    // protected $_validation_rules = [
    //     'channel' => 'required|validateChannelExists'
    // ];

    public function getVariables(): array
    {
        ee()->load->library('session'); //getAllCustomFields requires session

        $selectedTemplates = array_intersect_key($this->templates, array_flip($this->input->get('templates')));

        $vars = [
            'fields' => [],
            'publicTemplates' => array_intersect_key(
                $selectedTemplates,
                array_flip(['login', 'forgot-username', 'forgot-password', 'registration'])
            ),
            'privateTemplates' => array_diff_key(
                $selectedTemplates,
                array_flip(['login', 'forgot-username', 'forgot-password', 'registration'])
            ),
        ];

        // get the fields for members
        $fields = ee('Model')->get('MemberField')->all();
        foreach ($fields as $fieldInfo) {
            $fieldtypeGenerator = ee('TemplateGenerator')->getFieldtype($fieldInfo->m_field_type);

            // fieldtype is not installed, skip it
            if (!$fieldtypeGenerator) {
                continue;
            }
            // by default, we'll use generic field stub
            // but we'll let each field type to override it
            // by either providing stub property, or calling its own generator
            $field = [
                'field_type' => $fieldInfo->m_field_type,
                'field_name' => $fieldInfo->m_field_name,
                'field_label' => $fieldInfo->m_field_label,
                'show_profile' => $fieldInfo->m_field_public,
                'show_registration' => $fieldInfo->m_field_reg,
                'stub' => $fieldtypeGenerator['stub'],
                'docs_url' => $fieldtypeGenerator['docs_url'],
                'is_tag_pair' => $fieldtypeGenerator['is_tag_pair'],
            ];

            $field = array_merge($field, ee('TemplateGenerator')->getFieldVariables($fieldInfo));

            // if the field has its own generator, spin it
            // we'll not be using service (as it's singleton),
            // but spin and destroy new factory for each field
            // ... or something on that front
            $vars['fields'][$fieldInfo->m_field_name] = $field;
        }

        return $vars;
    }

}
