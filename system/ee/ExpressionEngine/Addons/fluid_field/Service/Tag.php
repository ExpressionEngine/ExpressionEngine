<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\FluidField\Service;

use ExpressionEngine\Model\Content\FieldFacade;

/**
 * A tag for a fieldtype within a Fluid Field
 */
class Tag
{
    /**
     * @var string $tag The name of the tag
     */
    protected $tag;


    /**
     * @var null|string $prefix The optional prefix for the tag
     */
    protected $prefix;

    /**
     * @var string $tagdata The contents of the tag
     */
    protected $tagdata;

    /**
     * @var bool $has_pair A flag noting whether or not this tag contains a tag pair
     */
    protected $has_pair = false;

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

        // Use 'content' as the default tag name
        $this->setTag('content');
    }

    /**
     * Set the Tag name parsing and storing a prefix if present
     *
     * @param string $tag
     * @return static
     */
    public function setTag($tag)
    {
        $this->tag = $tag;
        $this->has_pair = false;

        if(strpos($tag, ':')) {
            $this->setPrefix(substr($tag, 0, strrpos($tag, ':')));
        }

        if (strpos($this->tagdata, LD . "/$tag" . RD) !== false) {
            $this->has_pair = true;
        }

        return $this;
    }

    /**
     * Get the Tag name
     *
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Set the Tag prefix
     *
     * @param string $prefix
     * @return static
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Get the Tag prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix ? $this->prefix .':' : '';
    }

    /**
     * Get the Tag name without a prefix
     *
     * @return string
     */
    public function getTagWithoutPrefix()
    {
        return $this->removeTagPrefix($this->getTag());
    }

    /**
     * Remove the Tag prefix from the given subject
     *
     * @param string $subject
     * @return string
     */
    protected function removeTagPrefix($subject)
    {
        return str_replace($this->getPrefix(), '', $subject);
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
        $vars = $this->variable_parser_delegate->extractVariables($tagdata, $this->getTag());

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

        $name = $field->getName();
        $field->setName($this->getTagWithoutPrefix());

        if ($field->getType() == 'relationship') {
            ee()->load->library('relationships_parser');

            $channel = ee()->session->cache('mod_channel', 'active');

            $rel_fields = array();

            foreach ($channel->rfields as $site_id => $rfields) {
                $rel_fields[$site_id] = array();
                foreach ($rfields as $rel_name => $rel_id) {
                    if ($rel_id == $field->getId()) {
                        $rel_name = $field->getName();
                    }

                    $rel_fields[$site_id][$rel_name] = $rel_id;
                }
            }
            // strip prefix in $chunk/$content
            $tagdata = str_replace($this->getTag(), $this->getTagWithoutPrefix(), $tagdata);

            $relationship_parser = ee()->relationships_parser->create(
                $rel_fields,
                array($field->getContentId()),
                $tagdata,
                array(),
                null,
                $field->getItem('fluid_field_data_id')
            );

            if (! is_null($relationship_parser)) {
                // just before we pass the data to hook, let Pro do its thing
                $tagdata = ee('pro:FrontEdit')->prepareTemplate($tagdata, "{$this->tag}:");
                $tagdata = $relationship_parser->parse($field->getContentId(), $tagdata, $channel);
            }

            $tagdata = $field->replaceTag($tagdata);
            $field->setName($name);
            return $tagdata;
        }

        if ($this->hasPair()) {
            $tagdata = $this->parsePairs($field, $tagdata);
        }

        $tagdata = $this->parseConditionals($field, $tagdata, $meta);

        $tagdata = $this->parseSingle($field, $tagdata);

        $field->setName($name);
        return $tagdata;
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
        $pairs = $this->channel_fields_delegate->get_pair_field($tagdata, $this->tag);

        foreach ($pairs as $chk_data) {
            list($modifier, $content, $params, $chunk) = $chk_data;

            // strip prefix in $chunk/$content
            $content = str_replace($this->getPrefix(), '', $content);

            if ($field->getType() == 'grid' || $field->getType() == 'file_grid') {
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
        foreach ($this->getSingleTags($tagdata) as $tag) {
            $field_output = $this->replaceSingle($field, $this->removeTagPrefix($tag));
            $tag = LD . $tag . RD;
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

        return $field->replaceTag(false, $tag_info['params'], $tag_info['modifier'], $tag_info['full_modifier'], $tag_info['all_modifiers'] ?: []);
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

    protected function parseConditionals(FieldFacade $field, $tagdata = '', $vars = [])
    {
        $tagdata = ($tagdata) ?: $this->getTagdata();

        foreach ($this->getSingleTags($tagdata) as $tag) {
            $vars[$tag] = $field->getData();
        }

        if(!isset($vars[$this->getTag()])) {
            $vars[$this->getTag()] = !empty($field->getData());
        }

        return $this->function_delegate->prep_conditionals($tagdata, $vars);
    }

    protected function replaceMetaTags(array $meta, $tagdata = '')
    {
        $tagdata = ($tagdata) ?: $this->getTagdata();

        foreach ($meta as $name => $value) {
            $tag = LD . $name . RD;
            $tagdata = str_replace($tag, (string) $value, $tagdata);
        }

        return $tagdata;
    }
}

// EOF
