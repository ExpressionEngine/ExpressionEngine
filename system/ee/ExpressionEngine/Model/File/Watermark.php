<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\File;

use ExpressionEngine\Service\Model\Model;

/**
 * Watermark Model
 *
 * A model representing one of the watermarks associated with an image
 * manipulation belonging to an upload destination
 */
class Watermark extends Model
{
    protected static $_primary_key = 'wm_id';
    protected static $_table_name = 'file_watermarks';

    protected static $_typed_columns = array(
        'wm_use_font' => 'boolString',
        'wm_use_drop_shadow' => 'boolString',
        //'wm_font_size'       => 'int',
        //'wm_padding'         => 'int',
        //'wm_opacity'         => 'int',
        //'wm_hor_offset'      => 'int',
        //'wm_vrt_offset'      => 'int',
        //'wm_x_transp'        => 'int',
        //'wm_y_transp'        => 'int',
        //'wm_shadow_distance' => 'int'
    );

    protected static $_relationships = array(
        'FileDimension' => array(
            'type' => 'belongsTo',
            'from_key' => 'wm_id',
            'to_key' => 'watermark_id'
        )
    );

    protected static $_validation_rules = array(
        'wm_name' => 'required|xss|noHtml|unique',
        'wm_type' => 'enum[text,image]',
        'wm_image_path' => 'fileExists',
        'wm_test_image_path' => 'fileExists',
        'wm_use_font' => 'enum[y,n]',
        'wm_font_size' => 'isNaturalNoZero',
        'wm_text' => 'validateWatermarkText',
        'wm_vrt_alignment' => 'enum[top,middle,bottom]',
        'wm_hor_alignment' => 'enum[left,center,right]',
        'wm_padding' => 'isNatural',
        'wm_opacity' => 'isNatural',
        'wm_hor_offset' => 'integer',
        'wm_vrt_offset' => 'integer',
        'wm_x_transp' => 'isNatural',
        'wm_y_transp' => 'isNatural',
        'wm_font_color' => 'hexColor',
        'wm_use_drop_shadow' => 'enum[y,n]',
        'wm_shadow_distance' => 'integer',
        'wm_shadow_color' => 'hexColor'
    );

    protected $wm_id;
    protected $wm_name;
    protected $wm_type;
    protected $wm_image_path;
    protected $wm_test_image_path;
    protected $wm_use_font;
    protected $wm_font;
    protected $wm_font_size;
    protected $wm_text;
    protected $wm_vrt_alignment;
    protected $wm_hor_alignment;
    protected $wm_padding;
    protected $wm_opacity;
    protected $wm_hor_offset;
    protected $wm_vrt_offset;
    protected $wm_x_transp;
    protected $wm_y_transp;
    protected $wm_font_color;
    protected $wm_use_drop_shadow;
    protected $wm_shadow_distance;
    protected $wm_shadow_color;

    /**
     * Require text only if watermark type is text
     */
    public function validateWatermarkText($key, $value, $params, $rule)
    {
        if ($this->wm_type != 'text') {
            $rule->skip();
        }
        if (empty($this->getProperty($key))) {
            $rule->stop();
            return 'required';
        }
        return true;
    }

    public function set__wm_font_color($wm_font_color)
    {
        $this->setRawProperty('wm_font_color', ltrim((string) $wm_font_color, '#'));
    }

    public function set__wm_shadow_color($wm_shadow_color)
    {
        $this->setRawProperty('wm_shadow_color', ltrim((string) $wm_shadow_color, '#'));
    }

    /**
     * Custom getter to parse path variables in the image path
     */
    public function __get($name)
    {
        $value = parent::__get($name);

        if ($name == 'wm_image_path') {
            $overrides = array();

            if ($this->FileDimension !== null && $this->FileDimension->site_id == ee()->config->item('site_id')) {
                $overrides = ee()->config->get_cached_site_prefs($this->FileDimension->site_id);
            }

            $value = parse_config_variables($value, $overrides);
        }

        return $value;
    }
}

// EOF
