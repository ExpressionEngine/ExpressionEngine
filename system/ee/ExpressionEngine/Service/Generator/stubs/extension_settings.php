
    public function settings()
    {
        // Set default settings
        $default_settings = [
            'yes_or_no' => 'n',
            'text_setting' => 'default'
        ];

        $this->settings = array_merge($this->settings, $default_settings);

        // Update the settings
        if (ee('Request')->isPost()) {
            $this->settings['yes_or_no'] = ee('Request')->post('yes_or_no');
            $this->settings['text_setting'] = ee('Request')->post('text_setting');

            ee()->db->where('class', __CLASS__)
                ->update('extensions', ['settings' => serialize($this->settings)]);
        }

        return array(
            'text_setting' => array('i', '', $this->settings['text_setting']),
            'yes_or_no' => array('r', array('y' => "Yes", 'n' => "No"), $this->settings['yes_or_no']),
        );
    }
