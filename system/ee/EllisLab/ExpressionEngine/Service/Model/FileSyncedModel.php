<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Model;

use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;

/**
 * File Synced Model
 *
 * A parent model that allows some of the model data to be stored and edited
 * on disk, for example to store templates as files.
 */
abstract class FileSyncedModel extends Model {

	protected $_skip_next_write = FALSE;

	protected static $_events = array(
		'afterLoad',
		'afterDelete',
		'afterSave',
		'afterUpdate'
	);

	/**
	 * Get the full server path to the file
	 *
	 * @return String Full file path
	 */
    abstract public function getFilePath();

	/**
	 * Get modification time as the database believes to be true.
	 *
	 * The value returned here is compared to the file mtime on load and
	 * the database value is updated from the file if the file mtime is newer.
	 *
	 * @return Int Last modificaiton time of the model
	 */
	 abstract public function getModificationTime();

	 /**
 	 * Set modification time in the database.
 	 *
 	 * This is called if the model has to automatically do a sync.
	 *
	 * @param Int $mtime The new last modified time
	 * @return void
 	 */
	abstract public function setModificationTime($mtime);

	/**
	 * Given an array of old model data, get that file path. Used to
	 * sync renames.
	 *
	 * @param Array $previous Assoc array of old model data
	 * @return String Full file path
	 */
	abstract protected function getPreviousFilePath($previous);

	/**
	 * Take the current model information and return a string of whatever
	 * it is that should be synced to the file.
	 *
	 * @return String File data
	 */
    abstract protected function serializeFileData();

	/**
	 * Given the file data, set any fields that need to be set.
	 *
	 * @param String $str The current file data
	 */
    abstract protected function unserializeFileData($str);

	/**
	 * After loading the row from the database, ensure that the file exists and
	 * that the two data points are up to date.
	 */
	public function onAfterLoad()
	{
		$fs = new Filesystem();
		$path = $this->getFilePath();

		if ( ! isset($path))
		{
			return;
		}

		if ( ! $fs->exists($path))
		{
			$this->writeToFile();
			return;
		}

		$mtime = $fs->mtime($path);

	    if ($mtime > $this->getModificationTime())
	    {
			$this->unserializeFileData($fs->read($path));
			$this->setModificationTime($mtime);

			$this->_skip_next_write = TRUE;
	        $this->save();
	    }
	}

	/**
	 * For all saves, write the template file. Unless specifically told not to.
	 *
	 * Technically we could make this afterInsert and do more checks
	 * in afterUpdate to make sure things actually changed, but this
	 * lets us be fieldname agnostic and gives devs a little more control.
	 */
	public function onAfterSave()
	{
		if ($this->_skip_next_write === TRUE)
		{
			$this->_skip_next_write = FALSE;
			return;
		}

		$this->writeToFile();
	}

	/**
	 * If the template is updated, we need to make sure things like
	 * renames or changes in template group are reflected in the
	 * filesystem. We do this by simply deleting the old file, since
	 * our afterSave event will always write a new one.
	 *
	 * @param Array Old values that were changed by this save
	 */
	public function onAfterUpdate($previous)
	{
		$fs = new Filesystem();
		$path = $this->getFilePath();
		$old_path = $this->getPreviousFilePath($previous);

		if ($path != $old_path && $fs->exists($old_path))
		{
			$fs->delete($old_path);
		}
	}

	/**
	 * If the template is deleted, remove the template file
	 */
	public function onAfterDelete()
	{
		$fs = new Filesystem();
		$path = $this->getFilePath();

		if (isset($path) && $fs->exists($path))
		{
			$fs->delete($path);
		}
	}

	/**
	 * Helper to write the template to the file
	 */
	protected function writeToFile()
	{
		$fs = new Filesystem();
		$path = $this->getFilePath();

		if (isset($path) && $fs->exists($fs->dirname($path)))
		{
			$fs->write($path, $this->serializeFileData(), TRUE);
		}
	}

}

// EOF
