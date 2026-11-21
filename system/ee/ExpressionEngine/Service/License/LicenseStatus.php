<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\License;

/**
 * License Status Service
 *
 * Keeps the most recent answer from the license service for this site and its
 * add-ons. The answer normally arrives from the control panel in the browser;
 * when that has not worked for a while the site asks the license service
 * directly. Both only happen while the control panel is in use.
 *
 * Only a definitive answer replaces what is stored. Connection problems and
 * unreadable responses leave the last known status in place. It stays in force
 * until checks have been failing for MAX_AGE, counted from the first time the
 * site's own check failed to the latest. That check only runs after the
 * browser has had its turn, so a status cannot lapse unless both have tried,
 * and it does not lapse while nobody is logged in to try again.
 *
 * A stored answer belongs to the license key it was given for. The site's
 * address is not part of that: one key may serve several addresses.
 *
 * Responses may be signed. A signed response carries two extra string fields
 * next to the regular ones:
 *
 *   payload    base64 of a JSON object holding `license`, `issued_at` (Unix
 *              time), `messageType` and `addons` (a list of `slug`, `status`
 *              and `update`)
 *   signature  base64 RSA signature (SHA-384) of the `payload` string exactly
 *              as it was sent
 */
class LicenseStatus
{
    const CACHE_KEY = '/addons-status';
    const CHECK_URL = 'https://updates.expressionengine.com/check';

    // A check is overdue once the last successful one is this old
    const BACKUP_AFTER = 86400;

    // How long the site's own check has to keep failing before the last known
    // status lapses, and before members who manage licenses are told
    const MAX_AGE = 1209600;
    const NOTICE_AGE = 604800;

    // How long the browser gets to make an overdue check before the site asks
    // the license service itself
    const BROWSER_GRACE = 600;

    // Wait between attempts made by the site itself, and how long each may take
    const RETRY_INTERVAL = 3600;
    const CONNECT_TIMEOUT = 3;
    const REQUEST_TIMEOUT = 5;

    const MAX_ADDONS = 1000;

    // The statuses that lapse when checks keep failing. One that already says
    // there is a problem with the license is kept, so it can still be shown.
    const LAPSING = ['valid', 'update_available', 'trial'];

    protected $cache;
    protected $encrypt;
    protected $crypt_key;
    protected $site;
    protected $public_keys = [];
    protected $usable_keys = null;
    protected $curl;
    protected $clock;

    protected $loaded = false;
    protected $record = null;
    protected $error = null;
    protected $license_changed = false;

    /**
     * @param object $cache File cache driver
     * @param object $encrypt Encrypt service
     * @param string $crypt_key Key the stored status is encrypted with
     * @param array $site The site's `license`, `site_id`, `site_url` and `app_version`
     * @param object $curl cURL request factory, if requests can be made
     * @param array|null $public_keys Public keys that may sign a response, defaults to the bundled keys
     * @param callable $clock Returns the current Unix time
     */
    public function __construct($cache, $encrypt, $crypt_key, array $site, $curl = null, ?array $public_keys = null, $clock = null)
    {
        $this->cache = $cache;
        $this->encrypt = $encrypt;
        $this->crypt_key = $crypt_key;
        $this->site = $site;
        $this->curl = $curl;
        $this->clock = $clock;

        if ($public_keys === null) {
            $public_keys = $this->bundledPublicKeys();
        }

        foreach ($public_keys as $key) {
            if (is_string($key) && trim($key) !== '') {
                $this->public_keys[] = $key;
            }
        }
    }

    /**
     * The public keys shipped with ExpressionEngine: the one in use, followed
     * by those held in reserve
     *
     * @return array
     */
    private function bundledPublicKeys()
    {
        $keys = [];

        foreach (['ExpressionEngineCheck.pub', 'ExpressionEngineCheck1.pub', 'ExpressionEngineCheck2.pub'] as $file) {
            if (is_file(SYSPATH . 'ee/ExpressionEngine/Keys/' . $file)) {
                $keys[] = 'file://' . SYSPATH . 'ee/ExpressionEngine/Keys/' . $file;
            }
        }

        return $keys;
    }

    /**
     * The license status of an add-on
     *
     * @param string $shortname Add-on short name
     * @return string|bool Status, or FALSE when no status is stored
     */
    public function statusFor($shortname)
    {
        $record = $this->answer();

        if ($record === null) {
            return false;
        }

        $status = 'na';

        if (isset($record['addons'][$shortname]['status'])) {
            $status = $this->cleanStatus($record['addons'][$shortname]['status']);

            if ($status === 'valid' && empty($record['validLicense'])) {
                $status = isset($record['licenseStatus']) ? $this->cleanStatus($record['licenseStatus']) : '';
                $status = ($status !== '') ? $status : 'invalid';
            }
        }

        if ($status === '') {
            $status = 'na';
        }

        if (in_array($status, self::LAPSING, true) && $this->overdueFor($record) > self::MAX_AGE) {
            return 'unverified';
        }

        return $status;
    }

    /**
     * Language key describing why the stored status could not be used
     *
     * @return string|null
     */
    public function getError()
    {
        $this->load();

        return $this->error;
    }

    /**
     * Seconds since a status was last stored
     *
     * @return int|bool FALSE when no status is stored, or a fresh check has been asked for
     */
    public function secondsSinceUpdate()
    {
        $record = $this->answer();

        if ($record === null || ! empty($record['check_again'])) {
            return false;
        }

        // Statuses stored by earlier versions only have the time of the check
        return max(0, $this->now() - (int) (empty($record['saved_at']) ? $record['checked_at'] : $record['saved_at']));
    }

    /**
     * Asks for a fresh check from the next control panel page, for when
     * something the license is checked against may have changed. The stored
     * status stays in force until the answer arrives.
     *
     * @return bool TRUE when there is a stored status and it was marked
     */
    public function checkAgain()
    {
        $record = $this->answer();

        if ($record === null) {
            return false;
        }

        $record['check_again'] = true;

        return $this->write($record);
    }

    /**
     * Whether checks have been failing long enough to tell someone
     *
     * @return bool
     */
    public function verificationOverdue()
    {
        $record = $this->answer();

        return $record !== null && $this->overdueFor($record) > self::NOTICE_AGE;
    }

    /**
     * Stores a response from the license service
     *
     * @param array $response Response fields
     * @param string|null $asked_for License key the check was made for, when whoever passes on the answer says
     * @return bool TRUE when the response was accepted and stored
     */
    public function store($response, $asked_for = null)
    {
        if (! is_array($response)) {
            return false;
        }

        // A site without a key gives its pages FALSE, which they send back as text
        if ($asked_for === 'false') {
            $asked_for = '';
        }

        // The key may have changed while the check was on its way
        if (is_string($asked_for) && $this->licenseFingerprint(['license' => $asked_for]) !== $this->licenseFingerprint($this->site)) {
            return false;
        }

        $checked_at = $this->now();

        if ($this->isSigned($response) && $this->canVerify()) {
            $response = $this->openSigned($response['payload'], $response['signature']);

            if ($response === null) {
                return false;
            }

            $checked_at = min($checked_at, (int) $response['issued_at']);
        }

        $message = isset($response['messageType']) ? $this->cleanStatus($response['messageType']) : '';

        // The license service always says how the check went and lists the
        // add-ons it was asked about
        if ($message === '' || ! isset($response['addons']) || ! is_array($response['addons'])) {
            return false;
        }

        $addons = [];

        foreach (array_slice($response['addons'], 0, self::MAX_ADDONS) as $addon) {
            $slug = (is_array($addon) && isset($addon['slug'])) ? $this->cleanSlug($addon['slug']) : '';

            if ($slug === '') {
                continue;
            }

            $addons[$slug] = [
                'slug' => $slug,
                'status' => isset($addon['status']) ? $this->cleanStatus($addon['status']) : '',
                'update' => isset($addon['update']) ? $this->cleanVersion($addon['update']) : '',
            ];
        }

        $record = [
            'appVer' => $this->cleanVersion($this->site('app_version')),
            'license' => $this->cleanLicense($this->site('license')),
            'validLicense' => ($message === 'success'),
            'licenseStatus' => $message,
            'site_id' => (int) $this->site('site_id'),
            'site_url' => (string) $this->site('site_url'),
            'addons' => $addons,
            'checked_at' => $checked_at,
            'saved_at' => $this->now(),
        ];

        // An answer that was already old when it arrived is not a fresh check
        if ($this->age($record) >= self::BACKUP_AFTER) {
            $record['overdue_since'] = $checked_at;
            $record['failed_at'] = $this->now();
        }

        return $this->write($record);
    }

    /**
     * Whether the site should ask the license service itself
     *
     * Call this when a control panel page is served to someone who is logged
     * in. The site asks once three waits are over: an answer is good for a
     * day, the browser gets the first try at a check that is due, and the
     * site's own attempts are spaced apart. Straight after the license key
     * has changed there is nothing to wait for.
     *
     * @return bool
     */
    public function backupCheckDue()
    {
        $record = $this->load();

        if ($this->license_changed) {
            return true;
        }

        $record = ($record === null) ? $this->blank() : $record;
        $times = $record + ['checked_at' => 0, 'attempted_at' => 0];

        if ($this->now() < (int) $times['checked_at'] + self::BACKUP_AFTER) {
            return false;
        }

        // The browser's turn starts with the first page that finds a check is
        // due. That is kept with the status, so it cannot be put off without
        // giving up the status as well.
        if (empty($record['missed_at'])) {
            $record['missed_at'] = $this->now();

            if (! $this->write($record)) {
                return false;
            }
        }

        return $this->now() >= max(
            (int) $record['missed_at'] + self::BROWSER_GRACE,
            (int) $times['attempted_at'] + self::RETRY_INTERVAL
        );
    }

    /**
     * Read the installed module list used by the browser and server checks.
     *
     * @return array The modules indexed by short name.
     */
    public function installedModules()
    {
        $modules = ee()->db->select('module_name,module_version')->get('modules');
        $addons = [];

        foreach ($modules->result() as $module) {
            $slug = strtolower($module->module_name);
            $addons[$slug] = [
                'slug' => $slug,
                'name' => ucwords(str_replace('_', ' ', $module->module_name)),
                'version' => $module->module_version,
            ];
        }

        return $addons;
    }

    /**
     * Send the site's installed modules and environment to the license service.
     *
     * @param array|null $addons The installed modules if the caller already loaded them.
     * @return bool Whether the reply was stored.
     */
    public function runSiteCheck(?array $addons = null)
    {
        return $this->runBackupCheck([
            'appVer' => $this->site('app_version'),
            'license' => (string) $this->site('license'),
            'addons' => $addons ?? $this->installedModules(),
            'meta' => [[
                'site_name' => ee()->config->item('site_name'),
                'site_id' => $this->site('site_id'),
                'site_url' => $this->site('site_url'),
                'php_version' => PHP_VERSION,
                'database_engine' => ee()->db->engine(),
                'database_version' => ee()->db->version_number(),
                'usesPro' => ee('pro:Access')->requiresValidLicense() ? 'true' : 'false',
                'source' => 'server',
            ]],
        ]);
    }

    /**
     * Asks the license service directly and stores its answer
     *
     * @param array $request The fields the control panel would send
     * @return bool TRUE when an answer was received and stored
     */
    public function runBackupCheck(array $request)
    {
        // Note the attempt first, so one that hangs is not repeated on the
        // next page. With nowhere to note it, the site does not ask.
        $this->loaded = false;
        $record = $this->load();
        $record = ($record === null) ? $this->blank() : $record;
        $record['attempted_at'] = $this->now();

        if (! $this->write($record)) {
            return false;
        }

        $response = $this->askLicenseService($request);

        // The browser may have got an answer in while the site waited. That
        // one stands, whatever came of the site's own attempt.
        $this->loaded = false;
        $record = $this->answer();

        if ($record !== null && $this->age($record) < self::BACKUP_AFTER) {
            return false;
        }

        if (is_array($response) && $this->store($response)) {
            return true;
        }

        // The browser has had its turn and now the site has failed too. The
        // last known status lapses once that has gone on for long enough,
        // counted between the first of these failures and the latest.
        if ($record !== null) {
            $record['overdue_since'] = empty($record['overdue_since']) ? $this->now() : $record['overdue_since'];
            $record['failed_at'] = $this->now();
            $this->write($record);
        }

        return false;
    }

    /**
     * @param array $request
     * @return array|null The decoded answer, or NULL when none could be had
     */
    protected function askLicenseService(array $request)
    {
        if ($this->curl === null) {
            return null;
        }

        try {
            $post = $this->curl->post(self::CHECK_URL, $request);
            $post->config[CURLOPT_CONNECTTIMEOUT] = self::CONNECT_TIMEOUT;
            $post->config[CURLOPT_TIMEOUT] = self::REQUEST_TIMEOUT;

            $body = $post->exec();

            if ((string) $post->getHeader('http_code') !== '200') {
                return null;
            }
        } catch (\Throwable $e) {
            return null;
        }

        $response = json_decode((string) $body, true);

        return is_array($response) ? $response : null;
    }

    /**
     * The stored answer from the license service, if there is one
     *
     * @return array|null
     */
    protected function answer()
    {
        $record = $this->load();

        return isset($record['licenseStatus']) ? $record : null;
    }

    /**
     * What is stored before any answer has arrived: only the times of the
     * site's attempts to get one
     *
     * @return array
     */
    protected function blank()
    {
        return ['license' => $this->cleanLicense($this->site('license'))];
    }

    /**
     * Reads and checks what is stored for this license, once per request
     *
     * @return array|null
     */
    protected function load()
    {
        if ($this->loaded) {
            return $this->record;
        }

        $this->loaded = true;
        $this->record = null;
        $this->error = null;
        $this->license_changed = false;

        $cached = $this->cache->get(self::CACHE_KEY);

        if (empty($cached) || ! is_string($cached)) {
            return null;
        }

        if (! $this->cache->is_writable(self::CACHE_KEY)) {
            $this->error = 'license_error_file_not_writable';

            return null;
        }

        $parts = explode('||s=', $cached);

        if (count($parts) !== 2 || $parts[0] === '' || ! hash_equals(hash('sha256', $parts[0]), $parts[1])) {
            $this->error = 'license_error_file_broken';

            return null;
        }

        $json = $this->encrypt->decode($parts[0], $this->crypt_key);
        $data = empty($json) ? null : json_decode($json, true);

        if (! is_array($data) || ! isset($data['sha']) || ! is_string($data['sha'])) {
            $this->error = 'license_error_file_broken';

            return null;
        }

        $sha = $data['sha'];
        unset($data['sha']);

        if (! hash_equals(hash('sha256', json_encode($data)), $sha)) {
            $this->error = 'license_error_file_broken';

            return null;
        }

        // An answer given for another license key says nothing about this one
        if ($this->licenseFingerprint($data) !== $this->licenseFingerprint($this->site)) {
            $this->license_changed = true;

            return null;
        }

        // Statuses stored by earlier versions carry no time of their own
        if (isset($data['licenseStatus']) && empty($data['checked_at'])) {
            $meta = $this->cache->get_metadata(self::CACHE_KEY);
            $data['checked_at'] = empty($meta['mtime']) ? $this->now() : (int) $meta['mtime'];
        }

        $this->record = $data;

        return $this->record;
    }

    /**
     * Identify the license key in configuration or in a stored answer.
     *
     * @param array $context The configured site or a stored answer.
     * @return string A fingerprint of the license key.
     */
    private function licenseFingerprint(array $context)
    {
        return hash('sha256', strtolower($this->cleanLicense(isset($context['license']) ? $context['license'] : '')));
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function write(array $data)
    {
        unset($data['sha']);
        $data['sha'] = hash('sha256', json_encode($data));

        $encrypted = $this->encrypt->encode(json_encode($data), $this->crypt_key);
        $saved = $this->cache->save(self::CACHE_KEY, $encrypted . '||s=' . hash('sha256', $encrypted), 0);

        $this->loaded = false;

        return (bool) $saved;
    }

    /**
     * @param array $record
     * @return int Seconds since the last successful check
     */
    protected function age(array $record)
    {
        return $this->now() - (int) $record['checked_at'];
    }

    /**
     * @param array $record
     * @return int Seconds between the first time the site's own check failed and the latest
     */
    protected function overdueFor(array $record)
    {
        if (empty($record['overdue_since']) || empty($record['failed_at'])) {
            return 0;
        }

        return max(0, (int) $record['failed_at'] - (int) $record['overdue_since']);
    }

    /**
     * @return int
     */
    protected function now()
    {
        return $this->clock ? (int) call_user_func($this->clock) : time();
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function site($key)
    {
        return isset($this->site[$key]) ? $this->site[$key] : '';
    }

    /**
     * @param array $response
     * @return bool
     */
    protected function isSigned(array $response)
    {
        // Fields left empty are the same as fields left out
        return ! empty($response['payload'])
            && ! empty($response['signature'])
            && is_string($response['payload'])
            && is_string($response['signature']);
    }

    /**
     * @return bool
     */
    protected function canVerify()
    {
        return function_exists('openssl_verify') && ! empty($this->usableKeys());
    }

    /**
     * The public keys that can be read. One that cannot, such as a damaged
     * file, is passed over; a site left with none behaves as if it had none.
     *
     * @return array
     */
    protected function usableKeys()
    {
        if ($this->usable_keys === null) {
            $this->usable_keys = [];

            foreach ($this->public_keys as $pem) {
                $key = openssl_pkey_get_public($pem);

                if ($key !== false) {
                    $this->usable_keys[] = $key;
                }
            }
        }

        return $this->usable_keys;
    }

    /**
     * Verifies a signed payload and returns its contents
     *
     * @param string $payload
     * @param string $signature
     * @return array|null NULL unless the payload is authentic and meant for this site
     */
    protected function openSigned($payload, $signature)
    {
        $signature = base64_decode($signature, true);

        if ($signature === false || ! $this->signedByKnownKey($payload, $signature)) {
            return null;
        }

        $data = json_decode((string) base64_decode($payload, true), true);

        if (! is_array($data) || ! isset($data['license'], $data['issued_at']) || ! is_scalar($data['license']) || ! is_numeric($data['issued_at'])) {
            return null;
        }

        $expected = strtolower($this->cleanLicense($this->site('license')));
        $actual = strtolower($this->cleanLicense($data['license']));

        return hash_equals($expected, $actual) ? $data : null;
    }

    /**
     * @param string $payload
     * @param string $signature Raw signature
     * @return bool
     */
    protected function signedByKnownKey($payload, $signature)
    {
        foreach ($this->usableKeys() as $key) {
            if (openssl_verify($payload, $signature, $key, OPENSSL_ALGO_SHA384) === 1) {
                return true;
            }
        }

        return false;
    }

    protected function cleanStatus($value)
    {
        return is_scalar($value) ? substr(preg_replace('/[^a-z0-9_]/', '', strtolower((string) $value)), 0, 40) : '';
    }

    protected function cleanSlug($value)
    {
        return is_scalar($value) ? substr(preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value)), 0, 100) : '';
    }

    protected function cleanVersion($value)
    {
        return is_scalar($value) ? substr(preg_replace('/[^\da-z\.\-_]/i', '', (string) $value), 0, 40) : '';
    }

    protected function cleanLicense($value)
    {
        return is_scalar($value) ? preg_replace('/[^a-z0-9]/i', '', (string) $value) : '';
    }
}

// EOF
