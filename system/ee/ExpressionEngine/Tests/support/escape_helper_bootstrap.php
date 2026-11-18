<?php

define('SYSPATH', dirname(__DIR__, 4) . '/');
define('BASEPATH', SYSPATH . 'ee/legacy/');
define('APPPATH', BASEPATH);
define('REQ', $argv[1]);

require_once BASEPATH . 'core/Loader.php';
require_once BASEPATH . 'libraries/Core.php';

class EscapeHelperBootstrapReady extends RuntimeException {}

/**
 * Supply the services needed before persistent resources are initialized.
 *
 * @param string|null $service The optional service name.
 * @return object|null
 */
function ee($service = null)
{
    return $service === null ? $GLOBALS['escape_helper_app'] : null;
}

/**
 * Use the standard helper prefix without reading an installed site's config.
 *
 * @param string $item The configuration key.
 * @return string|null
 */
function config_item($item)
{
    return $item === 'subclass_prefix' ? 'EE_' : null;
}

/**
 * Keep bootstrap logging out of the subprocess result.
 *
 * @param string $level The log severity.
 * @param string $message The log message.
 * @return void
 */
function log_message($level, $message)
{
}

/**
 * Start with no legacy service instances.
 *
 * @return array
 */
function is_loaded()
{
    return [];
}

$GLOBALS['escape_helper_app'] = (object) [
    'config' => new class {
        /**
         * Read the isolated bootstrap configuration.
         *
         * @param string $item The configuration key.
         * @return string|null
         */
        public function item($item)
        {
            return config_item($item);
        }
    },
    'input' => new class {
        /**
         * Run a regular request without AJAX-specific behavior.
         *
         * @return bool
         */
        public function is_ajax_request()
        {
            return false;
        }
    },
    'load' => new class extends EE_Loader {
        /**
         * Stop after real helper loading, before cache or database startup.
         *
         * @param string|array $library The driver being initialized.
         * @param array|null $params Driver constructor parameters.
         * @param string|null $object_name The requested instance name.
         * @return void
         * @throws EscapeHelperBootstrapReady
         */
        public function driver($library = '', $params = null, $object_name = null)
        {
            throw new EscapeHelperBootstrapReady();
        }
    },
];

$available_before = function_exists('ee_html_escape');

try {
    (new EE_Core())->bootstrap();
} catch (EscapeHelperBootstrapReady $exception) {
    echo json_encode([
        'available_before' => $available_before,
        'available_after' => function_exists('ee_html_escape'),
        'escaped' => ee_html_escape('<heading>'),
    ]);
    exit(0);
}

throw new LogicException('Core bootstrap did not reach driver initialization.');
