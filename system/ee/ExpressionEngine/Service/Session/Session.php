<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license
 */

namespace ExpressionEngine\Service\Session;

/**
 * Session service
 */
class Session
{
    protected $session;

    /**
     * Constructor
     *
     * @param Session|NULL $session Ideally a Session model object, but could be
     * NULL as visitors aren't required to have a session
     */
    public function __construct($session)
    {
        $this->session = $session;
    }

    /**
     * Manage sudo-like timeout for "trust but verify" actions
     */
    public function resetAuthTimeout()
    {
        $this->session->resetAuthTimeout();
    }
    public function isWithinAuthTimeout()
    {
        return (!empty($this->session) && $this->session->isWithinAuthTimeout());
    }

    public function proBannerSeen()
    {
        return (!empty($this->session) && $this->session->proBannerSeen());
    }

    public function setProBannerSeen()
    {
        if (!empty($this->session)) {
            $this->session->setProBannerSeen();
        }
    }
}

// EOF
