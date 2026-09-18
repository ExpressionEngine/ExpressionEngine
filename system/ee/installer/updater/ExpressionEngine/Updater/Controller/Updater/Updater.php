<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Controller\Updater;

use ExpressionEngine\Updater\Service\Updater\ControlPanelAuthorization;
use ExpressionEngine\Updater\Service\Updater\RequestAuthorization;
use ExpressionEngine\Updater\Service\Updater\Runner;
use ExpressionEngine\Updater\Service\Updater\UpdaterException;

/**
 * The web boundary for the updater. Installer and CLI callers use the runner directly.
 */
class Updater
{
    protected $authorization;
    protected $controlPanel;

    public function __construct($authorization = null, $controlPanel = null)
    {
        $this->authorization = $authorization ?: new RequestAuthorization();
        $this->controlPanel = $controlPanel ?: new ControlPanelAuthorization();
    }

    /**
     * Check the request before loading the installation or any update services.
     */
    public function requiresControlPanel()
    {
        $step = $this->getStep();
        if (! $this->isAuthorizedForStep($step)) {
            if ($this->authorization->hasStarted() || ! in_array($step, ['updateFiles', 'rollback'], true)) {
                throw new UpdaterException('Unauthorized updater request.', 403);
            }

            return true;
        }

        if (! $this->authorization->hasStarted()) {
            if ($step === 'rollback') {
                return true;
            }
            if ($step !== 'updateFiles') {
                throw new UpdaterException('The update has not started.', 409);
            }
        }

        $this->authorization->assertStep($step);

        return false;
    }

    public function requiresFullBootstrap()
    {
        return $this->requiresControlPanel() || in_array(explode('[', $this->getStep(), 2)[0], [
            'addLegacyFiles', 'checkForDbUpdates', 'backupDatabase', 'updateDatabase',
            'restoreDatabase', 'selfDestruct',
        ], true);
    }

    /**
     * Authorize and execute the requested updater step.
     *
     * @return string
     * @throws UpdaterException
     */
    public function run()
    {
        if ($this->requiresControlPanel()) {
            $this->controlPanel->authorize();
        }

        $step = $this->getStep();
        if ($step === 'rollback' && ! $this->authorization->hasStarted()) {
            $this->cancelBeforeReplacement();

            return $this->response(false);
        }

        if (! $this->isAuthorizedForStep($step)) {
            // Trust the installed application's settings and session only after CP authorization above.
            $sessionId = ee()->session->validation === 's' ? ee()->session->userdata('session_id') : null;
            $this->authorization->prepare(bool_config_item('disable_csrf_protection'), $sessionId);

            // The existing client repeats this step with the prepared credentials.
            return $this->response('updateFiles', 'Updating files');
        }

        $this->authorization->beginStep($step);
        $runner = $this->makeRunner();
        $runner->runStep($step);
        $nextStep = $runner->getNextStep();
        $this->authorization->completeStep($step, $nextStep);

        return $this->response($nextStep, $runner->getLanguageForStep($nextStep));
    }

    /**
     * Preserve the existing continuation format, with a fixed set of web operations.
     */
    protected function getStep()
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            throw new UpdaterException('Unauthorized updater request.', 403);
        }

        $step = $_GET['step'] ?? null;
        if (! is_string($step)) {
            throw new UpdaterException('Invalid update step.', 400);
        }

        $plainSteps = [
            'updateFiles', 'addLegacyFiles', 'checkForDbUpdates', 'backupDatabase',
            'updateDatabase', 'updateAddons', 'rollback', 'restoreDatabase', 'selfDestruct',
        ];
        if (in_array($step, $plainSteps, true) || $step === 'selfDestruct[rollback]') {
            return $step === 'rollback' ? $this->authorization->getRecoveryStep() : $step;
        }

        // Continuations are produced by the runner; only their documented arguments are accepted.
        if (preg_match('/^backupDatabase\[([^\[\],\x00-\x20]+),([0-9]+)\]$/D', $step, $match)
            && filter_var($match[2], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) !== false) {
            return $step;
        }
        if (preg_match('/^updateDatabase\[runUpdateFile\[ud_[a-z0-9_]+\.php\]\]$/D', $step)) {
            return $step;
        }

        throw new UpdaterException('Invalid update step.', 400);
    }

    /**
     * Cancel a failed handoff without invoking a file or database rollback.
     */
    protected function cancelBeforeReplacement()
    {
        $config = ee('Config')->getFile();
        $previous = $config->get('is_system_on_before_updater');
        if ($previous !== null) {
            $restored = ee()->config->_update_config(
                ['is_system_on' => $previous],
                ['is_system_on_before_updater' => $previous]
            );
            if (! $restored) {
                throw new UpdaterException('Unable to restore the site configuration.');
            }
        }

        if (! ee('Filesystem')->deleteDir(SYSPATH . 'ee/updater')) {
            throw new UpdaterException('Unable to remove the updater directory.');
        }
    }

    protected function makeRunner()
    {
        return new Runner();
    }

    private function isAuthorizedForStep($step)
    {
        // Recovery remains available to the initiating browser after normal authorization expires.
        $recovery = in_array($step, ['rollback', 'restoreDatabase', 'selfDestruct[rollback]'], true);

        return $this->authorization->isAuthorized($recovery);
    }

    private function response($nextStep, $message = '')
    {
        return json_encode([
            'messageType' => 'success',
            'message' => $message,
            'nextStep' => $nextStep,
        ]);
    }
}
