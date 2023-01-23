<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Service\Addon\Installer;

/**
 * Email Module update class
 */
class Email_upd extends Installer
{
    public $actions = [
        [
            'method' => 'send_email'
        ]
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Module Installer
     *
     * @access	public
     * @return	bool
     */
    public function install()
    {
        parent::install();
        $sql[] = "CREATE TABLE IF NOT EXISTS exp_email_tracker (
			email_id int(10) unsigned NOT NULL auto_increment,
			email_date int(10) unsigned default '0' NOT NULL,
			sender_ip varchar(45) NOT NULL,
			sender_email varchar(75) NOT NULL ,
			sender_username varchar(50) NOT NULL ,
			number_recipients int(4) unsigned default '1' NOT NULL,
			PRIMARY KEY `email_id` (`email_id`)
		) DEFAULT CHARACTER SET " . ee()->db->escape_str(ee()->db->char_set) . " COLLATE " . ee()->db->escape_str(ee()->db->dbcollat);

        foreach ($sql as $query) {
            ee()->db->query($query);
        }

        return true;
    }

    /**
     * Module Uninstaller
     *
     * @access	public
     * @return	bool
     */
    public function uninstall()
    {
        parent::uninstall();
        $sql[] = "DROP TABLE IF EXISTS exp_email_tracker";

        foreach ($sql as $query) {
            ee()->db->query($query);
        }

        return true;
    }

    /**
     * Module Updater
     *
     * @access	public
     * @return	bool
     */
    public function update($current = '')
    {
        return true;
    }
}
// END CLASS

// EOF
