<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Consent;

use ExpressionEngine\Service\Model\Model;

/**
 * Consent Request Version Model
 */
class ConsentRequestVersion extends Model
{
    protected static $_primary_key = 'consent_request_version_id';
    protected static $_table_name = 'consent_request_versions';

    protected static $_typed_columns = [
        'consent_request_version_id' => 'int',
        'consent_request_id' => 'int',
        'create_date' => 'timestamp',
        'author_id' => 'int',
    ];

    protected static $_relationships = [
        'ConsentRequest' => [
            'type' => 'belongsTo',
        ],
        'CurrentVersion' => [
            'type' => 'belongsTo',
            'model' => 'ConsentRequest',
            'to_key' => 'consent_request_version_id'
        ],
        'Consents' => [
            'type' => 'hasMany',
            'model' => 'Consent',
        ],
        'Author' => [
            'type' => 'belongsTo',
            'model' => 'Member',
            'from_key' => 'author_id',
            'weak' => true
        ],
        'Logs' => [
            'type' => 'hasMany',
            'model' => 'ConsentAuditLog'
        ],
        'Cookies' => [
            'type' => 'hasAndBelongsToMany',
            'model' => 'CookieSetting',
            'pivot' => array(
                'table' => 'consent_request_version_cookies'
            )
        ],
    ];

    protected static $_validation_rules = [
        'create_date' => 'required',
        'author_id' => 'required',
    ];

    protected static $_events = [
        'afterInsert',
    ];

    // Properties
    protected $consent_request_version_id;
    protected $consent_request_id;
    protected $request;
    protected $request_format;
    protected $create_date;
    protected $author_id;

    public function render()
    {
        ee()->load->library('typography');
        ee()->typography->initialize(array(
            'bbencode_links' => false,
            'parse_images' => false,
            'parse_smileys' => false
        ));

        return ee()->typography->parse_type($this->request, array(
            'text_format' => $this->request_format,
            'html_format' => 'all',
            'auto_links' => 'n',
            'allow_img_url' => 'y'
        ));
    }

    //when created, associate with all existing cookies for given consent
    public function onAfterInsert()
    {
        if (strpos($this->ConsentRequest->consent_name, 'ee:cookies_') === 0) {
            $consentType = substr($this->ConsentRequest->consent_name, 11);
            $method = 'is' . ucfirst($consentType);
            $cookieSettings = ee('Model')->get('CookieSetting')->all();
            $cookieIds = [];
            foreach ($cookieSettings as $cookie) {
                if (ee('CookieRegistry')->{$method}($cookie->cookie_name)) {
                    if ($cookie->cookie_provider == 'ee' || (ee('Addon')->get($cookie->cookie_provider) !== null && ! ee('Addon')->get($cookie->cookie_provider)->isInstalled())) {
                        $cookieIds[] = $cookie->cookie_id;
                    }
                }
            }
            if (!empty($cookieIds)) {
                $this->Cookies = ee('Model')->get('CookieSetting')->filter('cookie_id', 'IN', $cookieIds)->all();
                $this->save();
            }
        }
    }
}

// EOF
