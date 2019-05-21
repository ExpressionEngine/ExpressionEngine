<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Template\Variables;

/**
 * :modifier variable replacement methods
 *
 * All methods receive:
 * 		mixed ($data) - whatever content is returned by the field
 * 		array ($params) - an array of optional options!
 * 		string ($tagdata) - optional tagdata, used by pair variables
 */
trait ModifiableTrait {

	/**
	 * :attr_safe modifier
	 */
	public function replace_attr_safe($data, $params = array(), $tagdata = FALSE)
	{
		return (string) ee('Format')->make('Text', $data)->attributeSafe($params);
	}

	/**
	 * :censor modifier
	 */
	public function replace_censor($data, $params = array(), $tagdata = FALSE)
	{
		return (string) ee('Format')->make('Text', $data)->censor();
	}

	/**
	 * :currency modifier
	 */
	public function replace_currency($data, $params = array(), $tagdata = FALSE)
	{
		return (string) ee('Format')->make('Number', $data)->currency($params);
	}

	/**
	 * :decrypt modifier
	 */
	public function replace_decrypt($data, $params = array(), $tagdata = FALSE)
	{
		return (string) ee('Format')->make('Text', $data)->decrypt($params);
	}

	/**
	 * :encrypt modifier
	 */
	public function replace_encrypt($data, $params = array(), $tagdata = FALSE)
	{
		return (string) ee('Format')->make('Text', $data)->encrypt($params);
	}

	/**
	 * :form_prep modifier
	 */
	public function replace_form_prep($data, $params = array(), $tagdata = FALSE)
	{
		return (string) ee('Format')->make('Text', $data)->formPrep()->encodeEETags($params);
	}

	/**
	 * :json modifier
	 */
	public function replace_json($data, $params = array(), $tagdata = FALSE)
	{
		return (string) ee('Format')->make('Text', $data)->json($params);
	}

	/**
	 * :length modifier
	 */
	public function replace_length($data, $params = array(), $tagdata = FALSE)
	{
		return (string) ee('Format')->make('Text', $data)->length();
	}

	/**
	 * :limit modifier
	 */
	public function replace_limit($data, $params = array(), $tagdata = FALSE)
	{
		if ( ! isset($params['preserve_words']))
		{
			$params['preserve_words'] = TRUE;
		}

		return (string) ee('Format')->make('Text', $data)->limitChars($params);
	}

	/**
	 * :number_format modifier
	 */
	public function replace_number_format($data, $params = array(), $tagdata = FALSE)
	{
		return (string) ee('Format')->make('Number', $data)->number_format($params);
	}

	/**
	 * :ordinal modifier
	 */
	public function replace_ordinal($data, $params = array(), $tagdata = FALSE)
	{
		return (string) ee('Format')->make('Number', $data)->ordinal($params);
	}

	/**
	 * :raw_content modifier
	 */
	public function replace_raw_content($data, $params = array(), $tagdata = FALSE)
	{
		return (string) ee('Format')->make('Text', $data)->encodeEETags($params);
	}

	/**
	 * :replace modifier
	 */
	public function replace_replace($data, $params = array(), $tagdata = FALSE)
	{
		return (string) ee('Format')->make('Text', $data)->replace($params);
	}

	/**
	 * :rot13 modifier (for Seth)
	 */
	public function replace_rot13($data, $params = array(), $tagdata = FALSE)
	{
		return str_rot13($data);
	}

	/**
	 * :spellout modifier
	 */
	public function replace_spellout($data, $params = array(), $tagdata = FALSE)
	{
		return (string) ee('Format')->make('Number', $data)->spellout($params);
	}

	/**
	 * :url modifier
	 */
	public function replace_url($data, $params = array(), $tagdata = FALSE)
	{
		return (string) ee('Format')->make('Text', $data)->url();
	}

	/**
	 * :url_decode modifier
	 */
	public function replace_url_decode($data, $params = array(), $tagdata = FALSE)
	{
		return (string) ee('Format')->make('Text', $data)->urlDecode($params);
	}

	/**
	 * :url_encode modifier
	 */
	public function replace_url_encode($data, $params = array(), $tagdata = FALSE)
	{
		return (string) ee('Format')->make('Text', $data)->urlEncode($params);
	}

	/**
	 * :url_slug modifier
	 */
	public function replace_url_slug($data, $params = array(), $tagdata = FALSE)
	{
		return (string) ee('Format')->make('Text', $data)->urlSlug($params);
	}
}
// END TRAIT

// EOF
