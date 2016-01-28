<?php

namespace EllisLab\ExpressionEngine\Service\Database;

require_once BASEPATH."database/DB_driver.php";
require_once BASEPATH."database/DB_active_rec.php";

if ( ! class_exists('CI_DB'))
{
	class_alias('CI_DB_active_record', 'CI_DB');
}

require_once BASEPATH."database/drivers/mysqli/mysqli_driver.php";

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Database Connection
 *
 * @package		ExpressionEngine
 * @subpackage	Database\Connection
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Query extends \CI_DB_mysqli_driver {

	protected $connection;

	public function __construct(Connection $connection)
	{
		$this->connection = $connection;

		$params = $connection->getConfig();

		parent::__construct($params);
	}

}