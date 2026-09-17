<?php

namespace ExpressionEngine\Tests\Installer\Updater\Fixtures\Legacy\Controller\Updater;

// EE 4.3.6/5.x source updater constructor, using their legacy Super Admin check.
// The shared parent hierarchy and request/response API are exercised separately.
class Updater extends \CP_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (ee()->session->userdata('group_id') != 1 || ee('Request')->method() != 'POST') {
            throw new \RuntimeException('unauthorized_access', 403);
        }
    }
}

namespace ExpressionEngine\Tests\Installer\Updater\Fixtures\Legacy\Core;

class Request extends \ExpressionEngine\Core\Request
{
}

class Response extends \ExpressionEngine\Core\Response
{
}
