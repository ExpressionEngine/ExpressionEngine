<?php

require_once APPPATH.'helpers/directory_helper.php';

define('PASSWORD_MAX_LENGTH', 72);

use PHPUnit\Framework\TestCase;

class LanguageKeysTest extends TestCase {

	private $language_path = '';

	public function setUp()
	{
		$this->language_path = BASEPATH.'language/english/';
		$this->language_files = directory_map(BASEPATH.'language/english/', 1);
	}

	/**
	 * Recurses over a set of language files provided by directory_map
	 * @param  array $files Array from directory_map()
	 * @param  string $path Path where files are located
	 * @param  Callable $callback Method to call with the list of files,
	 *  expectes a callable with ($filename, $language_array)
	 * @return void
	 */
	private function recurseFiles($files, $path, $callback)
	{
		$ignored_dirs = array('vendor', 'Tests', 'tests');
		$valid_extensions = array('php', 'html', 'js');
		foreach ($files as $dir => $filename)
		{
			if (is_array($filename) && (is_string($dir) && ! in_array($dir, $ignored_dirs)))
			{
				$this->recurseFiles($filename, $path.$dir.'/', $callback);
			}
			else if (is_string($filename) && in_array(pathinfo($filename, PATHINFO_EXTENSION), $valid_extensions))
			{
				$callback($path.$filename);
			}
		}
	}

	/**
	 * Get language keys given a filename
	 * @param  string $filename Path to a language file
	 * @return array Array of language keys found in file, duplicates included
	 */
	private function getLanguageKeysFromFile($filename)
	{
		$lang_file = file_get_contents($filename);
		$lang_file = preg_replace('/[\'"]{2,2}\s*=\>\s*[\'"]{2,2}/i', '', $lang_file);
		$lang_file = str_replace('$lang = array(', 'array(', $lang_file);

		if (strpos($lang_file, '=>') !== FALSE)
		{
			preg_match_all("/^[ \t]*['\"](.*?)['\"]\s*=>/im", $lang_file, $keys);
		}
		else
		{
			preg_match_all('/\[[\'"](.*)[\'"]\]/i', $lang_file, $keys);
		}

		return $keys[1];
	}

	/**
	 * Get all language keys in all language files
	 *
	 * @return array Array of all language keys as keys, with the files they
	 *  belong to in an array as the value
	 */
	private function getAllLanguageKeys()
	{
		$all_keys = array();
		$this->recurseFiles(
			$this->language_files,
			BASEPATH.'language/english/',
			function ($filename) use (&$all_keys) {
				$keys = $this->getLanguageKeysFromFile($filename);

				foreach ($keys as $key)
				{
					$all_keys[$key][] = $filename;
				}
			}
		);

		return $all_keys;
	}

	/**
	 * Test each language file to see if there are duplicate language keys
	 */
	public function testDuplicateLanguageKeys()
	{
		$this->markTestSkipped('Not implemented.');

		$this->recurseFiles(
			$this->language_files,
			BASEPATH.'language/english/',
			function ($filename) {
				$keys = $this->getLanguageKeysFromFile($filename);

				$failures = array();
				$keysCount = array_count_values($keys);
				foreach ($keysCount as $key => $count)
				{
					try
					{
						$message = "There are {$count} language keys for '{$key}'.";
						$this->assertEquals($count, 1, $message);
					}
					catch (PHPUnit_Framework_AssertionFailedError $e)
					{
						$failures[] = $message;
					}
				}

				if ( ! empty($failures))
				{
					echo "\n{$filename}:\n- ".implode("\n- ", $failures);
					$this->fail("{$filename} contains duplicate language keys.");
				}
			}
		);
	}

	/**
	 * Test each language file to see if there are duplicate values
	 */
	public function testDuplicateLanguageValues()
	{
		$this->markTestSkipped('Need to discuss implications of this one.');

		$this->recurseFiles(
			$this->language_files,
			BASEPATH.'language/english/',
			function ($filename) {
				$valuesCount = array_count_values($lang);
				foreach ($valuesCount as $value => $count)
				{
					$this->assertEquals(
						$count,
						1,
						"{$filename} contains duplicate language values for '{$value}'."
					);
				}
			}
		);
	}

	/**
	 * Test to ensure there are no duplicate language keys across all language
	 * files
	 */
	public function testDuplicateLanguageKeysAcrossFiles()
	{
		$this->markTestSkipped('Not implemented.');

		$allKeys = array();
		$this->recurseFiles(
			$this->language_files,
			BASEPATH.'language/english/',
			function ($filename) use (&$allKeys) {
				$keys = $this->getLanguageKeysFromFile($filename);

				foreach ($keys as $key)
				{
					$allKeys[$key][] = $filename;
				}
			}
		);

		$failures = array();
		foreach ($allKeys as $key => $files)
		{
			try
			{
				$list = implode(', ', $files);
				$message = "The language key '{$key}' was found in multiple files: {$list}.";
				$this->assertTrue((count($files) <= 1), $message);
			}
			catch (PHPUnit_Framework_AssertionFailedError $e)
			{
				$failures[] = $message;
			}
		}
		if ( ! empty($failures))
		{
			echo "\n".implode("\n\n", $failures);
			$this->fail("Duplicate language keys found across files.");
		}
	}

	/**
	 * Test to ensure there are no unused language keys
	 */
	public function testUnusedLanguageKeys()
	{
		$this->markTestSkipped('Not implemented.');

		$all_keys = $this->getAllLanguageKeys();
		$used_keys = array();
		$path = realpath(SYSPATH.'../').'/';

		$this->recurseFiles(
			directory_map($path),
			$path,
			function ($filename) use (&$used_keys) {
				$contents = file_get_contents($filename);

				// Find our language types
				$regexes = array(
					"/lang\(['\"](.*?)['\"][\),]/im",
					"/lang-\>line\([\"'](.*?)[\"'][\),]/im",
					"/\{lang:(.*?)\}/im",
					"/['\"](?:title|desc)['\"]\s+=>\s+['\"](.*?)['\"]/im"
				);

				foreach ($regexes as $regex)
				{
					if (preg_match_all($regex, $contents, $matches))
					{
						$used_keys = array_merge($used_keys, $matches[1]);
					}
				}
			}
		);

		$used_keys         = array_unique($used_keys);
		$unused_keys       = array_diff(array_unique(array_keys($all_keys)), $used_keys);
		$unused_keys_count = count($unused_keys);

		echo "\n- ".implode("\n- ", $unused_keys);
		$this->assertEmpty($unused_keys, "There are {$unused_keys_count} unused language keys.");
	}
}
