<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Consent\Service\Variables;

use ExpressionEngine\Service\Template\Variables;

/**
 * Consent Request Variables
 */
class Consent extends Variables
{
    /**
     * @var array Consent data from ee('Consent')->getConsentDataFor()
     */
    private $consent;

    /**
     * @var array Single variables that are present in the template
     */
    private $template_vars = [];

    /**
     * Constructor
     *
     * @param array $consent Consent data from ee('Consent')->getConsentDataFor()
     */
    public function __construct(array $consent, $template_vars = [])
    {
        $this->consent = $consent;
        $this->template_vars = $template_vars;

        parent::__construct();
    }

    /**
     * getTemplateVariables
     *
     * @return array fully prepped variables to be parsed
     */
    public function getTemplateVariables()
    {
        if (! empty($this->variables)) {
            return $this->variables;
        }

        ee()->load->library('typography');
        ee()->typography->initialize([
            'parse_images' => true,
            'allow_headings' => true,
            'word_censor' => bool_config_item('comment_word_censoring'),
        ]);

        $typography_prefs = [
            'text_format' => $this->consent['request_format'],
            'html_format' => 'all',
            'auto_links' => false,
            'allow_img_url' => false,
        ];

        $this->variables = [
            'consent_creation_date' => $this->date($this->consent['create_date']),
            'consent_double_opt_in' => $this->consent['double_opt_in'],
            'consent_expiration_date' => $this->consent['expiration_date'],
            'consent_given_via' => $this->consent['consent_given_via'],
            'consent_granted' => $this->consent['has_granted'],
            'consent_id' => $this->consent['consent_request_id'],
            'consent_request' => $this->typography($this->consent['request'], $typography_prefs),
            'consent_response_date' => $this->consent['response_date'],
            'consent_retention_period' => $this->consent['retention_period'],
            'consent_short_name' => $this->consent['consent_name'],
            'consent_title' => $this->consent['title'],
            'consent_user_created' => $this->consent['user_created'],
            'consent_version_id' => $this->consent['consent_request_version_id'],
        ];

        $this->addActionUrls();

        return $this->variables;
    }

    /**
     * Add Action URLs
     *
     * 	{consent_grant_url return='foo/bar'}
     * 	{consent_withdraw_url return='foo/bar'}
     *
     * Uses the return= parameter, or the current URI if not supplied
     */
    private function addActionUrls()
    {
        $params = ['crid' => $this->consent['consent_request_id']];

        foreach ($this->template_vars as $name => $vars) {
            unset($params['return']);

            if (strncmp($name, 'consent_grant_url', 17) === 0 or strncmp($name, 'consent_withdraw_url', 20) === 0) {
                $variable = ee('Variables/Parser')->parseVariableProperties($name);

                if (isset($variable['params']['return'])) {
                    $params['return'] = ee('Encrypt')->encode($variable['params']['return']);
                } else {
                    $params['return'] = ee('Encrypt')->encode(ee()->uri->uri_string);
                }

                if ($variable['field_name'] == 'consent_grant_url') {
                    $this->variables[$name] = $this->action('Consent', 'grantConsent', $params);
                } else {
                    $this->variables[$name] = $this->action('Consent', 'withdrawConsent', $params);
                }
            }
        }
    }
}
// END CLASS

// EOF
