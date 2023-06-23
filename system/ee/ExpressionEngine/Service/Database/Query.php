<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Database;

require_once BASEPATH . "database/DB_driver.php";
require_once BASEPATH . "database/DB_active_rec.php";

if (! class_exists('CI_DB')) {
    class_alias('CI_DB_active_record', 'CI_DB');
}

require_once BASEPATH . "database/drivers/mysqli/mysqli_driver.php";

/**
 * ExpressionEngine Database Connection
 */
class Query extends \CI_DB_mysqli_driver
{
    protected $connection;
    public $save_queries;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        $params = $connection->getConfig();

        parent::__construct($params);
    }
}
