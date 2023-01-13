<?php
/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Service\Cookie;

use ExpressionEngine\Service\Cookie as Core;

/**
 * Cookie Service
 */
class Cookie extends Core\Cookie
{
    /**
     * {exp:consent:cookies}
     */
    public function cookiesTag()
    {
        $cookie_prefix = (! ee()->config->item('cookie_prefix')) ? 'exp_' : ee()->config->item('cookie_prefix') . '_';
        $allCookies = ee('Model')->get('CookieSetting')->all();
        $filteredCookies = $allCookies;
        $typeParam = trim(ee()->TMPL->fetch_param('type'));
        if (!empty($typeParam)) {
            $include = true;
            if (stripos($typeParam, 'not ') === 0) {
                $include = false;
                $typeParam = trim(substr($typeParam, 4));
            }
            $types = explode('|', $typeParam);
            $types = array_intersect($types, ['necessary', 'functionality', 'performance', 'targeting']);
            if (!empty($types)) {
                $filteredCookies = [];
                foreach ($allCookies as $cookie) {
                    foreach ($types as $type) {
                        $fn = 'is' . ucfirst($type);
                        if (ee('CookieRegistry')->{$fn}($cookie->cookie_name) == $include) {
                            $filteredCookies[] = $cookie;
                        }
                    }
                }
                $allCookies = $filteredCookies;
            }
        }
        $providerParam = trim(ee()->TMPL->fetch_param('provider'));
        if (!empty($providerParam)) {
            $include = true;
            if (stripos($providerParam, 'not ') === 0) {
                $include = false;
                $providerParam = trim(substr($providerParam, 4));
            }
            $providers = explode('|', $providerParam);
            if (!empty($providers)) {
                $filteredCookies = [];
                foreach ($allCookies as $cookie) {
                    foreach ($providers as $provider) {
                        if (($cookie->cookie_provider == $provider) === $include) {
                            $filteredCookies[] = $cookie;
                        }
                    }
                }
                $allCookies = $filteredCookies;
            }
        }
        $vars = [];
        foreach ($allCookies as $cookie) {
            $vars[] = [
                'cookie_title' => $cookie->cookie_title,
                'cookie_name' => $cookie_prefix . $cookie->cookie_name,
                'cookie_description' => $cookie->cookie_description,
                'cookie_lifetime' => ($cookie->cookie_enforced_lifetime !== null) ? $cookie->cookie_enforced_lifetime : $cookie->cookie_lifetime,
                'cookie_provider' => $cookie->cookie_provider
            ];
        }
        if (empty($vars)) {
            return ee()->TMPL->no_results();
        }
        return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $vars);
    }
}
