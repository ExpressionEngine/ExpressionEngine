<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Service\Updater;

/**
 * Authorizes requests after control passes to the updater micro-app.
 */
class RequestAuthorization
{
    const LIFETIME = 7200;
    const RECOVERY_LIFETIME = 86400;
    const STATE_PREFIX = "<?php exit; ?>\n";

    /**
     * Path to the updater authorization state.
     *
     * @var string
     */
    private $statePath;

    private $lockPath;
    private $lock;

    /**
     * Create an updater request authorization service.
     *
     * @param string|null $statePath
     * @param string|null $lockPath
     * @return void
     */
    public function __construct($statePath = null, $lockPath = null)
    {
        $this->statePath = $statePath ?: SYSPATH . 'ee/updater/.authorization.php';
        $this->lockPath = $lockPath ?: SYSPATH . 'user/cache/.ee-updater.lock';
    }

    /**
     * Prepare authorization during the authenticated Control Panel request.
     *
     * @param bool $csrfDisabled Whether the authenticated application has disabled CSRF protection.
     * @return void
     * @throws UpdaterException
     */
    public function prepare($csrfDisabled = false)
    {
        if ($this->hasStarted()) {
            throw new UpdaterException('The update has already started. Use rollback to recover.', 409);
        }

        $csrf = $this->getCsrfToken();
        $ajaxBinding = $csrf === null && $csrfDisabled === true;
        if ($ajaxBinding) {
            $csrf = $this->getAjaxBinding();
        }
        if ($csrf === null) {
            throw new UpdaterException('Unable to prepare updater authorization.', 403);
        }

        $previous = $this->readState();
        if ($this->stateWasNotAcknowledged($previous, $csrf)) {
            throw new UpdaterException(
                'The updater authorization cookie was not returned. Allow cookies, then cancel and retry the update.'
            );
        }
        if (isset($previous['expires']) && $previous['expires'] > time()) {
            throw new UpdaterException('Another browser has already prepared this update.', 409);
        }

        $token = bin2hex(random_bytes(32));
        $now = time();

        $this->writeState(array(
            'token_hash' => hash('sha256', $token),
            'csrf_hash' => hash('sha256', $csrf),
            'ajax_binding' => $ajaxBinding,
            'expires' => $now + self::LIFETIME,
            'recovery_expires' => $now + self::RECOVERY_LIFETIME,
            'started' => false,
            'recovering' => false,
            'next_step' => 'updateFiles',
            'running_step' => null,
        ));

        if (! $this->sendCookie($this->getCookieName(), $token, array(
            // The server expires normal operations; retain the cookie for authenticated recovery.
            'expires' => 0,
            'path' => '/',
            'secure' => ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off',
            'httponly' => true,
            'samesite' => 'Strict',
        ))) {
            @unlink($this->statePath);

            throw new UpdaterException('Unable to set updater authorization cookie.');
        }
    }

    /**
     * Serialize web requests so cancellation cannot race with file replacement.
     */
    public function acquireLock()
    {
        // Keep this file outside both the updater and its disposable working directory.
        // Do not unlink it: concurrent requests must continue locking the same file.
        $this->lock = @fopen($this->lockPath, 'c');
        if ($this->lock === false || ! flock($this->lock, LOCK_EX | LOCK_NB)) {
            throw new UpdaterException('Another updater request is still running. Please retry.', 409);
        }
        @chmod($this->lockPath, 0600);
    }

    public function __destruct()
    {
        if (is_resource($this->lock)) {
            flock($this->lock, LOCK_UN);
            fclose($this->lock);
        }
    }

    /**
     * Treat unreadable or incomplete state as started; never restart on uncertainty.
     */
    public function hasStarted()
    {
        $state = $this->readState();

        return is_file($this->statePath) && ($state['started'] ?? null) !== false;
    }

    /**
     * The rollback button on existing clients must resume recovery without repeating completed steps.
     */
    public function getRecoveryStep()
    {
        $state = $this->readState();
        $step = $state['running_step'] ?? $state['next_step'] ?? null;
        if (($state['recovering'] ?? false) === true
            && in_array($step, ['rollback', 'restoreDatabase', 'selfDestruct[rollback]'], true)) {
            return $step;
        }

        return 'rollback';
    }

    /**
     * Check the expected continuation before bootstrapping update services.
     */
    public function assertStep($step)
    {
        $recovery = in_array($step, ['rollback', 'restoreDatabase', 'selfDestruct[rollback]'], true);
        if (! $this->isAuthorized($recovery)) {
            throw new UpdaterException('Unauthorized updater request.', 403);
        }

        $state = $this->readState();
        // Rollback is a one-way transition from an active or interrupted forward update.
        if ($step === 'rollback' && ($state['started'] ?? false) === true
            && ($state['recovering'] ?? null) === false && ($state['next_step'] ?? null) !== false) {
            return;
        }
        // The request lock excludes an active attempt; only unfinished recovery work may be retried.
        if ($recovery && ($state['recovering'] ?? false) === true && ($state['running_step'] ?? null) === $step) {
            return;
        }
        if (($state['next_step'] ?? null) !== $step || ($state['running_step'] ?? null) !== null
            || ($recovery && ($state['recovering'] ?? false) !== true)
            || (! $recovery && ($state['recovering'] ?? null) !== false)) {
            throw new UpdaterException('Unexpected or already attempted update step.', 409);
        }
    }

    /**
     * Record the attempted step under the request lock before any side effects.
     */
    public function beginStep($step)
    {
        $this->assertStep($step);
        $state = $this->readState();
        $state['started'] = true;
        $state['recovering'] = $state['recovering'] || $step === 'rollback';
        $state['next_step'] = null;
        $state['running_step'] = $step;
        $this->writeState($state);
    }

    /**
     * Store only the continuation returned by the runner and renew successful activity.
     */
    public function completeStep($step, $nextStep)
    {
        // Successful self-destruction removes the state along with the updater.
        if ($nextStep === false && in_array($step, ['selfDestruct', 'selfDestruct[rollback]'], true)
            && ! is_file($this->statePath)) {
            return;
        }

        $state = $this->readState();
        if (($state['running_step'] ?? null) !== $step || (! is_string($nextStep) && $nextStep !== false)) {
            throw new UpdaterException('Unable to save the next update step.', 409);
        }
        $state['next_step'] = $nextStep;
        $state['running_step'] = null;
        $now = time();
        $state['expires'] = $nextStep === false ? 0 : $now + self::LIFETIME;
        $state['recovery_expires'] = $nextStep === false ? 0 : $now + self::RECOVERY_LIFETIME;
        $this->writeState($state);
    }

    /**
     * Determine whether the initiating browser authorized this request.
     * Recovery of a started update has a longer, bounded inactivity window.
     *
     * @param bool $recovery
     * @return bool
     */
    public function isAuthorized($recovery = false)
    {
        $state = $this->readState();
        $token = $_COOKIE[$this->getCookieName()] ?? null;
        $csrf = ($state['ajax_binding'] ?? false) === true ? $this->getAjaxBinding() : $this->getCsrfToken();
        $deadline = $recovery && ($state['started'] ?? false) === true ? 'recovery_expires' : 'expires';

        return strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
            && is_string($token)
            && preg_match('/^[a-f0-9]{64}$/D', $token) === 1
            && $csrf !== null
            && isset($state[$deadline], $state['token_hash'], $state['csrf_hash'])
            && is_int($state[$deadline])
            && $state[$deadline] > time()
            && is_string($state['token_hash'])
            && is_string($state['csrf_hash'])
            && hash_equals($state['token_hash'], hash('sha256', $token))
            && hash_equals($state['csrf_hash'], hash('sha256', $csrf));
    }

    /**
     * Send the updater authorization cookie to the initiating browser.
     *
     * @param string $name
     * @param string $value
     * @param array $options
     * @return bool
     */
    protected function sendCookie($name, $value, array $options)
    {
        return call_user_func_array('setcookie', $this->cookieArguments($name, $value, $options, PHP_VERSION_ID));
    }

    protected function cookieArguments($name, $value, array $options, $phpVersion)
    {
        if ($phpVersion < 70300) {
            // PHP 7.2 supports SameSite through the path argument, as in legacy Input::set_cookie().
            return [
                $name,
                $value,
                $options['expires'],
                $options['path'] . '; SameSite=' . $options['samesite'],
                '',
                $options['secure'],
                $options['httponly']
            ];
        }

        return [$name, $value, $options];
    }

    /**
     * Return a cookie name scoped to this ExpressionEngine installation.
     *
     * @return string
     */
    protected function getCookieName()
    {
        $systemPath = realpath(SYSPATH) ?: SYSPATH;

        return 'ee_updater_' . substr(hash('sha256', $systemPath), 0, 12);
    }

    /**
     * Return the nonempty CSRF header sent by the updater client.
     *
     * @return string|null
     */
    private function getCsrfToken()
    {
        $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        return is_string($csrf) && $csrf !== '' ? $csrf : null;
    }

    /**
     * Existing jQuery clients send this custom header, which a cross-origin HTML form cannot supply.
     * The random capability cookie remains required; this header replaces only the absent CSRF binding.
     *
     * @return string|null
     */
    private function getAjaxBinding()
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? null) === 'XMLHttpRequest' ? 'XMLHttpRequest' : null;
    }

    /**
     * Determine whether a prior preparation request was missing its cookie.
     *
     * @param array $state
     * @param string $csrf
     * @return bool
     */
    private function stateWasNotAcknowledged(array $state, $csrf)
    {
        return isset($state['expires'], $state['csrf_hash'])
            && is_int($state['expires'])
            && $state['expires'] > time()
            && is_string($state['csrf_hash'])
            && hash_equals($state['csrf_hash'], hash('sha256', $csrf));
    }

    /**
     * Write authorization state atomically without exposing its contents.
     *
     * @param array $state
     * @return void
     * @throws UpdaterException
     */
    private function writeState(array $state)
    {
        $json = json_encode($state);
        $contents = $json === false ? false : self::STATE_PREFIX . $json;
        $temporaryPath = @tempnam(dirname($this->statePath), '.authorization-');

        if ($contents === false || $temporaryPath === false) {
            throw new UpdaterException('Unable to prepare updater authorization.');
        }

        try {
            @chmod($temporaryPath, 0600);
            $written = @file_put_contents($temporaryPath, $contents, LOCK_EX);

            if ($written !== strlen($contents) || ! @rename($temporaryPath, $this->statePath)) {
                throw new UpdaterException('Unable to prepare updater authorization.');
            }

            $temporaryPath = null;
            @chmod($this->statePath, 0600);
        } finally {
            if ($temporaryPath !== null && is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }
    }

    /**
     * Read valid authorization state from the protected state file.
     *
     * @return array
     */
    private function readState()
    {
        $contents = @file_get_contents($this->statePath);
        if (! is_string($contents) || strpos($contents, self::STATE_PREFIX) !== 0) {
            return array();
        }

        $state = json_decode(substr($contents, strlen(self::STATE_PREFIX)), true);

        return is_array($state) ? $state : array();
    }
}

// EOF
