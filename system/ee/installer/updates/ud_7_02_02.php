<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_2_2;

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
                'addMissingConsentRequests',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    /**
     * as part of EE2 -> EE 6.1 update some consent requests could be not installed
     * making sure those are in place
     */
    private function addMissingConsentRequests()
    {
        $addon = ee('Addon')->get('consent');
        $requests = $addon->get('consent.requests', []);

        foreach ($requests as $name => $values) {
            $name = 'ee:' . $name;
            $request = ee('Model')->get('ConsentRequest')
                ->filter('consent_name', $name)
                ->first();
            if (is_null($request)) {
                $addon->makeConsentRequest($name, $values);
            } elseif (! $request->consent_request_version_id) {
                $version = ee('Model')->make('ConsentRequestVersion');
                $version->request = $values['request'];
                $version->request_format = (isset($values['request_format'])) ? $values['request_format'] : 'none';
                $version->author_id = 0;
                $version->create_date = ee()->localize->now;
                $request->Versions->add($version);

                $version->save();

                $request->CurrentVersion = $version;
                $request->save();
            }
        }
    }
}

// EOF
