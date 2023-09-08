<?php

class ChannelFormTemplatesGenerator extends ChannelTemplatesGenerator
{
    protected $templates = [
        'form.html'
    ];

    /**
     * @inheritDoc
     */
    protected function generateChannelFields(ChannelModel $channel)
    {
        // Get an array of rendered template stubs for each field in the channel
        return $channel->fields->map(function($field) {
            $stub = "form_fields/${$field->field_type}";
            if(!$this->stubExists($stub)) {
                $stub = 'form_fields/_fallback';
            }

            return $this->getStub($stub)->render([
                'field_name' => $field->field_name,
                'field_value' => '',
                'field_label' => '',
            ]);
        });
    }

}
