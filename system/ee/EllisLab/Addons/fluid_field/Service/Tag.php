<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Addons\FluidField\Service;

use EllisLab\ExpressionEngine\Model\Content\FieldFacade;

/**
 * A tag for a fieldtype within a Fluid Field
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
	 * @var obj $channel_fields_delegate Dependency Injected reference to `ee()->api_channel_fields`
	 */
	protected $channel_fields_delegate;

	/**
	 * @var obj $variable_parser_delegate Dependency Injected reference to `ee:Variables/Parser`
	 */
	protected $variable_parser_delegate;

	/**
	 * Constructor
	 *
	 * @param string $tagdata The contents of the tag
	 * @param obj $function_delegate Dependency Injected reference to `ee()->functions`
	 * @param obj $channel_fields_delegate Dependency Injected reference to `ee()->api_channel_fields`
	 * @param obj $variable_parser_delegate Dependency Injected reference to `ee:Variables/Parser`
	 * @return void
	 */
	public function __construct($tagdata, $function_delegate, $channel_fields_delegate, $variable_parser_delegate)
	{
		$this->tagdata = $tagdata;

		$this->function_delegate = $function_delegate;
		$this->channel_fields_delegate = $channel_fields_delegate;
		$this->variable_parser_delegate = $variable_parser_delegate;

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
		$vars = $this->variable_parser_delegate->extractVariables($tagdata, 'content');
		return array_keys($vars['var_single']);
	}

	/**
	 * Parses the tag data first replacing all the tag pairs then the single
	 * values.
	 *
	 * @param FieldFacade $field The fieldtype instance we are processing
	 * @return string The fully parsed tag
	 */
	public function parse(FieldFacade $field, array $meta = [])
	{
		$tagdata = $this->replaceMetaTags($meta);

		if ($field->getType() == 'relationship')
		{
			ee()->load->library('relationships_parser');

			$channel = ee()->session->cache('mod_channel', 'active');

			$rel_fields = array();

			foreach ($channel->rfields as $site_id => $rfields)
			{
				$rel_fields[$site_id] = array();
				foreach ($rfields as $rel_name => $rel_id)
				{
					if ($rel_id == $field->getId())
					{
						$rel_name = $field->getName();
					}

					$rel_fields[$site_id][$rel_name] = $rel_id;
				}
			}

			$relationship_parser = ee()->relationships_parser->create(
				$rel_fields,
				array($field->getContentId()),
				$tagdata,
				array(),
				NULL,
				$field->getItem('fluid_field_data_id')
			);

			if ( ! is_null($relationship_parser))
			{
				$tagdata = $relationship_parser->parse($field->getContentId(), $tagdata, $channel);
			}

			return $field->replaceTag($tagdata);
		}

		if ($this->hasPair())
		{
			$tagdata = $this->parsePairs($field, $tagdata);
		}

		$tagdata = $this->parseConditionals($field, $tagdata, $meta);
		return $this->parseSingle($field, $tagdata);
	}

	/**
	 * Parses and replaces the tag pairs
	 *
	 * @param FieldFacade $field The fieldtype instance we are processing
	 * @param string $tagdata The tagdata to parse
	 * @return string The tagdata with the pairs replaced
	 */
	protected function parsePairs(FieldFacade $field, $tagdata)
	{
		$pairs = $this->channel_fields_delegate->get_pair_field($tagdata, 'content');

		foreach ($pairs as $chk_data)
		{
			list($modifier, $content, $params, $chunk) = $chk_data;

			if ($field->getType() == 'grid' || $field->getType() == 'file_grid')
			{
				ee()->load->library('grid_parser');
				ee()->grid_parser->grid_field_names[$field->getId()][$field->getItem('fluid_field_data_id')] = $field->getName();
			}

			$tpl_chunk = $field->replaceTag($content, $params, $modifier);

			$tagdata = str_replace($chunk, $tpl_chunk, $tagdata);
		}

		return $tagdata;
	}

	/**
	 * Parses out the single tags and replaces them.
	 *
	 * @param FieldFacade $field The fieldtype instance we are processing
	 * @param string $tagdata The tagdata to parse
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
		$tag_info = $this->variable_parser_delegate->parseVariableProperties($tag);
		return $field->replaceTag(FALSE, $tag_info['params'], $tag_info['modifier'], $tag_info['full_modifier']);
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

	protected function parseConditionals(FieldFacade $field, $tagdata = NULL, $vars = [])
	{
		$tagdata = ($tagdata) ?: $this->getTagdata();

		foreach ($this->getSingleTags($tagdata) as $tag)
		{
			$vars[$tag] = $field->getData();
		}

		return $this->function_delegate->prep_conditionals($tagdata, $vars);
	}

	protected function replaceMetaTags(array $meta, $tagdata = NULL)
	{
		$tagdata = ($tagdata) ?: $this->getTagdata();

		foreach ($meta as $name => $value)
		{
			$tag = LD.$name.RD;
			$tagdata = str_replace($tag, $value, $tagdata);
		}

		return $tagdata;
	}

}

// EOF
