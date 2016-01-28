<?php

namespace EllisLab\Addons\Spam\Service;

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
 * ExpressionEngine Spam Module
 *
 * @package		ExpressionEngine
 * @subpackage	Extensions
 * @category	Extensions
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class Update {

	public function download()
	{
		$location = 'https://ellislab.com/asset/file/spam.zip';
		$compressed = ee('Curl')->get($location)->exec();

		// Write the training data to a tmp file and return the file name

		$handle = fopen($this->path() . "spam.zip", "w");
		fwrite($handle, $compressed);
		fclose($handle);
		$zip = new \ZipArchive;

		if ($zip->open($this->path() . "spam.zip") === TRUE)
		{
			$zip->extractTo($this->path());
			$zip->close();
			unlink($this->path() . "spam.zip");
		}
		else
		{
			return FALSE;
		}

		return TRUE;
	}

	public function prepare()
	{
		$path = $this->path() . "training/prepare.sql";
		$prep = file_get_contents($path);
		ee()->db->query($prep);

	}

	public function updateParameters($limit = 500)
	{
		$path = $this->path() . "training/parameters.sql";
		$lines = array_filter(file($path));
		$parameters = implode(',', array_slice($lines, 0, $limit));
		$remaining = implode("", array_slice($lines, $limit));

		$sql = "INSERT INTO exp_spam_parameters VALUES $parameters";

		ee()->db->query($sql);

		if (empty($remaining))
		{
			return FALSE;
		}

		file_put_contents($path, $remaining);

		return TRUE;
	}

	public function updateVocabulary($limit = 500)
	{
		$path = $this->path() . "training/vocabulary.sql";
		$lines = array_filter(file($path));
		$vocabulary = implode(',', array_slice($lines, 0, $limit));
		$remaining = implode("", array_slice($lines, $limit));

		$sql = "INSERT INTO exp_spam_vocabulary VALUES $vocabulary";

		ee()->db->query($sql);

		if (empty($remaining))
		{
			return FALSE;
		}

		file_put_contents($path, $remaining);

		return TRUE;
	}

	private function path()
	{
		$cache_path = ee()->config->item('cache_path');

		if (empty($cache_path))
		{
			$cache_path = SYSPATH.'user'.DIRECTORY_SEPARATOR.'cache/';
		}

		$cache_path .= 'spam/';

		if ( ! is_dir($cache_path))
		{
			mkdir($cache_path, DIR_WRITE_MODE);
			@chmod($cache_path, DIR_WRITE_MODE);
		}

		return $cache_path;
	}

}

// EOF
