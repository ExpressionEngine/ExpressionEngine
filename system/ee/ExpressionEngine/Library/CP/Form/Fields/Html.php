<?php

namespace EeObjects\Forms\Form\Fields;

use EeObjects\Forms\Form\Field;

class Html extends Field
{
    /**
     * @var null[]
     */
    protected $field_prototype = [
        'content' => '',
    ];

    /**
     * @param $content
     * @return $this
     */
    public function setContent($content): Html
    {
        $this->set('content', $content);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->get('content');
    }
}