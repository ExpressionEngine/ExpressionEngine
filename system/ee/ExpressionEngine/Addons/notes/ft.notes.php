<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license
 */

class Notes_ft extends EE_Fieldtype
{
    public $info = [];

    public $disable_frontedit = true;

    public $default_settings = [
        'note_content' => '',
        'field_hide_title' => true,
        'field_hide_publish_layout_collapse' => true,
    ];

    public $supportedEvaluationRules = null;

    public function __construct()
    {
        $addon = ee('Addon')->get('notes');

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
     * Displays the note
     *
     * @see EE_Fieldtype::display_field()
     */
    public function display_field($data)
    {
        return ee('View')->make('ee:_shared/form/fields/note')->render([
            'value' => $this->getParsedNote(),
        ]);
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
        return $this->getParsedNote();
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

        $data = array_merge($this->default_settings, $data);

        $settings = [
            [
                'title' => 'notes_note_content',
                'desc' => 'notes_note_content_desc',
                'fields' => [
                    'note_content' => [
                        'type' => 'textarea',
                        'required' => true,
                        'value' => $data['note_content'],
                    ]
                ]
            ]
        ];

        return ['field_options_notes' => [
            'label' => 'field_options',
            'group' => 'notes',
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
        // Merge the rest of the settings with their defaults
        $all = array_merge($this->default_settings, $data);
        return array_intersect_key($all, $this->default_settings);
    }

    /**
     * Accept all content types.
     *
     * @param string  The name of the content type
     * @return bool   Does not accept other content types
     */
    public function accepts_content_type($name)
    {
        return false;
    }

    private function getParsedNote()
    {
        ee()->load->library('typography');
        return ee()->typography->markdown(
            $this->settings['note_content']
        );
    }
}
