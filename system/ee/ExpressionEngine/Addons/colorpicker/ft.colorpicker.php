<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license
 */

use Mexitek\PHPColors\Color;

class Colorpicker_ft extends EE_Fieldtype
{
    public $info = [];

    public $disable_frontedit = true;

    public $size = 'small';

    public $default_settings = [
        'allowed_colors' => 'any',
        // The default color to use on invalid field input
        'colorpicker_default_color' => '',
        // An array of colors
        'value_swatches' => null,
        'manual_swatches' => '',
        // How the swatches should be crated
        //   v = By values with a grid
        //   m = Manually with a textarea
        'populate_swatches' => 'v'
    ];

    public function __construct()
    {
        $addon = ee('Addon')->get('colorpicker');

        $this->info = [
            'name' => $addon->getName(),
            'version' => $addon->getVersion()
        ];
    }

    /**
     * Validates the field's value
     */
    public function validate($value)
    {
        $value = trim($value);

        if ($value == '') {
            return true;
        }

        // Is it a valid 6 digit hex color?
        if (! preg_match('/#([a-f0-9]{6})\b/i', $value)) {
            ee()->lang->loadfile('channel');

            return ee()->lang->line('invalid_hex_code');
        }

        // Enforce that the color is one of the swatches or default color when in the swatches mode
        if ($this->get_setting('allowed_colors') == 'swatches') {
            if (! in_array($value, $this->getSwatches())
                and $this->get_setting('colorpicker_default_color') !== $value) {
                ee()->lang->loadfile('fieldtypes');

                return ee()->lang->line('colorpicker_color_not_allowed');
            }
        }

        return true;
    }

    /**
     * Saves the field's value
     *
     * @see EE_Fieldtype::save()
     */
    public function save($data)
    {
        return $data;
    }

    /**
     * Displays the color picker field
     *
     * @see EE_Fieldtype::display_field()
     */
    public function display_field($data)
    {
        ee()->cp->add_js_script('file', array('library/simplecolor', 'components/colorpicker'));

        return $this->create_colorpicker([
            'allowedColors' => $this->get_setting('allowed_colors'),
            'inputId' => $this->field_id,
            'inputName' => $this->field_name,
            'initialColor' => $data,
            'swatches' => $this->getSwatches(),
            'defaultColor' => $this->get_setting('colorpicker_default_color'),
            // 'disabled'      => $this->get_setting('field_disabled')
        ]);
    }

    /**
     * Creates a color picker input with the specified values
     * The values are passed to the react color picker component
    */
    private function create_colorpicker($info, $disabled = false)
    {
        $data = base64_encode(json_encode($info));

        $disabled = $disabled ? 'disabled' : '';

        return "<input name=\"{$info['inputName']}\" data-colorpicker-react=\"{$data}\" data-input-value=\"\" {$disabled}/>";
    }

    // -----------------------------------------------------------------------
    // Template Tag
    // -----------------------------------------------------------------------

    /**
     * Replace the field tag on the frontend.
     *
     * @param string  Stored data for the field
     * @param array   The tag's parameters
     * @param mixed   If the tag is a pair, the tagdata. Otherwise FALSE.
     * @return string Parsed tagdata
     */
    public function replace_tag($data, $params = array(), $tagdata = false)
    {
        // Data is preformatted, just return it!
        return $data;
    }

    /**
     * :contrast_color modifier
     *
     * Returns a black or white color in contrast to the fields color
     */
    public function replace_contrast_color($data, $params = array(), $tagdata = false)
    {
        try {
            $color = new Color($data);
            $contrast = $color->isLight() ? '#000000' : '#ffffff';
        } catch (\Exception $e) {
            $contrast = '#ffffff';
        }

        return $contrast;
    }

    // -----------------------------------------------------------------------
    // Settings
    // -----------------------------------------------------------------------

    /**
     * Displays the field's settings
     */
    public function display_settings($data)
    {
        ee()->lang->loadfile('fieldtypes');

        ee()->cp->add_js_script('file', array('library/simplecolor', 'components/colorpicker'));

        // The settings contain color picker fields,
        // so when the user chooses the color picker fieldtype, render them
        ee()->javascript->output('$(document).ready(function () {
			$("input[name=field_type]").change(function () {
				setTimeout(function () {
					ColorPicker.renderFields()
				}, 100);
			})
		})');

        $data = array_merge($this->default_settings, $data);

        $grid = $this->getSwatchesMiniGrid($data);

        $settings = [
            // ColorPicker type
            [
                'title' => 'colorpicker_allowed_colors',
                'desc' => 'colorpicker_allowed_colors_desc',
                'fields' => [
                    'allowed_colors' => [
                        'type' => 'radio',
                        'value' => $data['allowed_colors'],
                        'choices' => [
                            'any' => lang('colorpicker_allowed_colors_any'),
                            'swatches' => lang('colorpicker_allowed_colors_swatches')
                        ]
                    ]
                ]
            ],

            // Default Color
            [
                'title' => 'colorpicker_default_color',
                'desc' => 'colorpicker_default_color_desc',
                'fields' => [
                    'colorpicker_default_color' => [
                        'type' => 'html',
                        'content' => $this->create_colorpicker([
                            'allowedColors' => 'any',
                            'initialColor' => $data['colorpicker_default_color'],
                            'inputName' => 'colorpicker_default_color'
                        ])
                    ]
                ]
            ],
            [
                'title' => 'swatches',
                'desc' => 'colorpicker_swatches_options_desc',
                'fields' => array(
                    'populate_swatches_with_values' => array(
                        'type' => 'radio',
                        'name' => 'populate_swatches',
                        'choices' => array(
                            'v' => lang('colorpicker_swatches_populate_values'),
                        ),
                        'value' => $data['populate_swatches']
                    ),
                    'value_swatches' => [
                        'type' => 'html',
                        'margin_left' => true,
                        'content' => ee('View')->make('ee:_shared/form/mini_grid')->render($grid->viewData())
                    ],

                    'populate_swatches_manually' => array(
                        'type' => 'radio',
                        'name' => 'populate_swatches',
                        'choices' => array(
                            'm' => lang('colorpicker_swatches_populate_manually'),
                        ),
                        'value' => $data['populate_swatches']
                    ),
                    'manual_swatches' => array(
                        'type' => 'textarea',
                        'margin_left' => true,
                        'value' => $data['manual_swatches']
                    )
                )
            ]
        ];

        return ['field_options_colorpicker' => [
            'label' => 'field_options',
            'group' => 'colorpicker',
            'settings' => $settings
        ]];
    }

    /**
     * Validate the settings
     *
     * This is called before the settings are fully saved
     *
     * @param mixed   settings data
     * @return mixed  validation result
     */
    public function validate_settings($data)
    {
        return;
    }

    /**
     * Saves the field's settings
     */
    public function save_settings($data)
    {
        $value_colors = [];

        // Get the values colors
        if (isset($data['value_swatches']['rows'])) {
            $data['value_swatches'] = $data['value_swatches']['rows'];

            foreach ($data['value_swatches'] as $row) {
                $value_colors[] = $row['color'];
            }
        }

        $data['value_swatches'] = $value_colors;

        // Merge the rest of the settings with their defaults
        $all = array_merge($this->default_settings, $data);

        return array_intersect_key($all, $this->default_settings);
    }

    private function getSwatches()
    {
        if ($this->get_setting('populate_swatches') == 'm') {
            $manual_colors = [];

            foreach (explode("\n", $this->get_setting('manual_swatches')) as $color) {
                $manual_colors[] = trim($color);
            }

            return $manual_colors;
        } else {
            return $this->get_setting('value_swatches');
        }
    }

    private function getSwatchesMiniGrid($data)
    {
        $grid = ee('CP/MiniGridInput', array('field_name' => 'value_swatches'));

        $grid->loadAssets();
        $grid->setColumns(['colors' => ['label' => '']]);
        $grid->setNoResultsText(lang('no_colorpicker_swatches'), lang('add_new'));

        $grid->setBlankRow([
            // array('html' => '<input name="color" />'),
            // array('html' => form_input('color', ''))
            array('html' => $this->create_colorpicker(['inputName' => 'color'], true))
        ]);

        $grid->setData([]);

        // Populate the grid with the currently saved swatches
        if (isset($data['value_swatches'])) {
            $pairs = [];
            $i = 1;

            foreach ($data['value_swatches'] as $color) {
                $pairs[] = array(
                    'attrs' => array('row_id' => $i),
                    'columns' => array(
                        // array('html' => form_input('color', $color))
                        [
                            'html' => $this->create_colorpicker([
                                'initialColor' => $color,
                                'inputName' => 'color'
                            ])
                        ]
                    )
                );
                $i++;
            }

            $grid->setData($pairs);
        }

        return $grid;
    }

    /**
     * Accept all content types.
     *
     * @param string  The name of the content type
     * @return bool   Accepts all content types
     */
    public function accepts_content_type($name)
    {
        return true;
    }
}
