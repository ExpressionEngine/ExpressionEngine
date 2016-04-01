<?php

require_once APPPATH.'helpers/directory_helper.php';

define('PASSWORD_MAX_LENGTH', 72);

class LanguageKeysTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->files = directory_map(BASEPATH.'language/english/', 1);
	}

	private function recurseLanguageFiles($files, $path, $callback)
	{
		foreach ($files as $dir => $filename)
		{
			if (is_array($filename))
			{
				$this->recurseLanguageFiles($filename, $path.$dir.'/', $callback);
			}
			else if (strpos($filename, '.php') !== FALSE)
			{
				$lang = array();
				require $path.$filename;

				if (isset($lang['']))
				{
					unset($lang['']);
				}

				$callback($path.$filename, $lang);
			}
		}
	}

	/**
	 * Test each language file to see if there are duplicate language keys
	 */
	public function testDuplicateLanguageKeys()
	{
		$this->recurseLanguageFiles(
			$this->files,
			BASEPATH.'language/english/',
			function ($filename, $lang) {
				$lang_file = file_get_contents($filename);
				$lang_file = preg_replace('/[\'"]{2,2}\s*=\>\s*[\'"]{2,2}/i', '', $lang_file);
				$lang_file = str_replace('$lang = array(', 'array(', $lang_file);

				if (strpos('=>', $lang_file) !== FALSE)
				{
					preg_match_all("/^[ \t]*['\"](.*?)['\"]\s*=>/im", $lang_file, $keys);
				}
				else
				{
					preg_match_all('/\[[\'"](.*)[\'"]\]/i', $lang_file, $keys);
				}

				$failures = array();
				$keysCount = array_count_values($keys[1]);
				foreach ($keysCount as $key => $count)
				{
					try
					{
						$this->assertEquals(
							$count,
							1,
							"{$filename} contains duplicate language keys for '{$key}'."
						);
					}
					catch (PHPUnit_Framework_AssertionFailedError $e)
					{
						$failures[] = $e->getMessage();
					}
				}

				if ( ! empty($failures))
				{
					echo implode("\n\n- ", $failures);
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

		$this->recurseLanguageFiles(
			$this->files,
			BASEPATH.'language/english/',
			function ($filename, $lang) {
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
}
