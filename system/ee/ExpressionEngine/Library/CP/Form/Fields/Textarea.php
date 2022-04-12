<?php

namespace EeObjects\Forms\Form\Fields;

use EeObjects\Forms\Form\Field;

class Textarea extends Field
{
    /**
     * @var null[]
     */
    protected $field_prototype = [
        'kill_pipes' => null,
        'cols' => null,
        'rows' => null
    ];

    /**
     * @param bool $kill
     * @return $this
     */
    public function setKillPipes(bool $kill = false): Textarea
    {
        $this->set('kill_pipes', $kill);
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getKillPipes(): ?bool
    {
        return $this->get('kill_pipes');
    }

    /**
     * @param int $cols
     * @return $this
     */
    public function setCols(int $cols): Textarea
    {
        $this->set('cols', $cols);
        return $this;
    }

    /**
     * @return int|null
     */
    public function getCols(): ?int
    {
        return $this->get('cols');
    }

    /**
     * @param int $rows
     * @return $this
     */
    public function setRows(int $rows): Textarea
    {
        $this->set('rows', $rows);
        return $this;
    }

    /**
     * @return int|null
     */
    public function getRows(): ?int
    {
        return $this->get('rows');
    }
}