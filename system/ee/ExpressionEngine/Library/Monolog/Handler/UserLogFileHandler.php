<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2024, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Monolog\Handler;

use ExpressionEngine\Dependency\Monolog;

/**
 * Handler to write into system/user/logs folder
 */
class UserLogFileHandler extends Monolog\Handler\RotatingFileHandler
{
    /**
     * {@inheritDoc}
     */
    protected function write(array $record) : void
    {
        // on the first record written, if the log is new, we should rotate (once per day)
        if (null === $this->mustRotate) {
            $this->mustRotate = null === $this->url || !\file_exists($this->url);
        }
        if ($this->nextRotation <= $record['datetime']) {
            $this->mustRotate = \true;
            $this->close();
        }
        if ($this->mustRotate === true) {
            $record['formatted'] = "<?php\n\n" . (string) $record['formatted'];
        }
        parent::write($record);
    }
}