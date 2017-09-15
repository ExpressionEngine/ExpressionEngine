<?php

namespace EllisLab\Addons\FluidBlock\Service;

use EllisLab\ExpressionEngine\Model\Content\FieldFacade;

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

/**
 * A tag for a fieldtype within a Fluid Block
 */
class Tag {

	/**
	 * @var string $tagdata The contents of the tag
	 */
	protected $tagdata;

	/**
	 * @var bool $has_pair A flag noting whether or not this tag contains a tag pair
	 */
	protected $has_pair = FALSE;

	/**
	 * @var obj $function_delegate Dependency Injected reference to `ee()->functions`
	 */
	protected $function_delegate;

	/**
	 * @var obj $$channel_fields_delegate Dependency Injected reference to `ee()->api_channel_fields`
	 */
	protected $channel_fields_delegate;

	/**
	 * Constructor
	 *
	 * @param string $tagdata The contents of the tag
	 * @param obj $function_delegate Dependency Injected reference to `ee()->functions`
	 * @param obj $$channel_fields_delegate Dependency Injected reference to `ee()->api_channel_fields`
	 * @return void
	 */
	public function __construct($tagdata, $function_delegate, $channel_fields_delegate)
	{
		$this->tagdata = $tagdata;

		$this->function_delegate = $function_delegate;
		$this->channel_fields_delegate = $channel_fields_delegate;

		if (strpos($this->tagdata, LD."/content".RD) !== FALSE)
		{
			$this->has_pair = TRUE;
		}
	}

	/**
	 * Does this tag contain at least one tag pair?
	 *
	 * @return bool TRUE if it does, FALSE if not.
	 */
	public function hasPair()
	{
		return $this->has_pair;
	}

	/**
	 * Retrieves all the single tags in this tag data.
	 *
	 * @return array An array of all the single tags but without the braces
	 *  (i.e. ["content", "content:foo bar='baz'"])
	 */
	public function getSingleTags($tagdata = '')
	{
		$tagdata = ($tagdata) ?: $this->getTagdata();
		$vars = $this->function_delegate->assign_variables($tagdata, '/');
		return array_keys($vars['var_single']);
	}

	/**
	 * Parses the tag data first replacing all the tag pairs then the single
	 * values.
	 *
	 * @param FieldFacade $field The fieldtype instance we are processing
	 * @return string The fully parsed tag
	 */
	public function parse(FieldFacade $field)
	{
		$tagdata = $this->getTagdata();

		if ($field->getType() == 'relationship')
		{
			ee()->load->library('relationships_parser');

			$relationship_parser = ee()->relationships_parser->create(
				array($field->getName() => $field->getId()),
				array($field->getContentId()),
				$tagdata,
				array(),
				NULL,
				$field->getItem('block_data_id')
			);

			$channel = ee()->session->cache('mod_channel', 'active');
			$tagdata = $relationship_parser->parse($field->getContentId(), $tagdata, $channel);

			return $field->replaceTag($tagdata);
		}

		if ($this->hasPair())
		{
			$tagdata = $this->parsePairs($field);
		}

		return $this->parseSingle($field, $tagdata);
	}

	/**
	 * Parses and replaces the tag pairs
	 *
	 * @param FieldFacade $field The fieldtype instance we are processing
	 * @return string The tagdata with the pairs replaced
	 */
	protected function parsePairs(FieldFacade $field)
	{
		$tagdata = $this->getTagdata();

		$pairs = $this->channel_fields_delegate->get_pair_field($tagdata, 'content');

		foreach ($pairs as $chk_data)
		{
			list($modifier, $content, $params, $chunk) = $chk_data;

			if ($field->getType() == 'grid')
			{
				ee()->load->library('grid_parser');
				ee()->grid_parser->grid_field_names[$field->getId()] = $field->getName();
				$tpl_chunk = $field->replaceTag($content);
			}
			else
			{
				$tpl_chunk = $this->parseSingle($field, $content);
			}

			$tagdata = str_replace($chunk, $tpl_chunk, $tagdata);
		}

		return $tagdata;
	}

	/**
	 * Parses out the single tags and replaces them.
	 *
	 * @param FieldFacade $field The fieldtype instance we are processing
	 * @return string The tagdata with the tag replaced
	 */
	protected function parseSingle(FieldFacade $field, $tagdata)
	{
		foreach ($this->getSingleTags($tagdata) as $tag)
		{
			$field_output = $this->replaceSingle($field, $tag);
			$tag = LD.$tag.RD;
			$tagdata = str_replace($tag, $field_output, $tagdata);
		}

		return $tagdata;
	}

	/**
	 * Replaces a tag
	 *
	 * @param FieldFacade $field The fieldtype instance we are processing
	 * @return string The tagdata with the tag replaced
	 */
	protected function replaceSingle(FieldFacade $field, $tag)
	{
		$tag_info = $this->channel_fields_delegate->get_single_field($tag);
		return $field->replaceTag($tag, $tag_info['params'], $tag_info['modifier']);
	}

	/**
	 * Returns the data for this tag.
	 *
	 * @return string The data for the tag.
	 */
	public function getTagdata()
	{
		return $this->tagdata;
	}

}

// EOF
