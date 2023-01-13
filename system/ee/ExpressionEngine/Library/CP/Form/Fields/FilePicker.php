<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\Form\Fields;

class FilePicker extends Html
{
    /**
     * @return array
     */
    public function toArray(): array
    {
        ee()->load->library('file_field');
        $type = $this->isImage() ? 'image' : 'all';
        $dir = $this->isAll() ? 'all' : $this->getUploadDir();
        $picker = ee()->file_field->dragAndDropField($this->getName(), $this->getValue(), $dir, $type);
        $this->setContent($picker);
        $this->set('type', 'html');
        return parent::toArray();
    }

    /**
     * @return $this
     */
    public function asAny(): FilePicker
    {
        $this->set('_image_field', false);
        return $this;
    }

    /**
     * @return $this
     */
    public function asImage(): FilePicker
    {
        $this->set('_image_field', true);
        return $this;
    }

    /**
     * @return bool
     */
    public function isImage(): bool
    {
        return $this->get('_image_field') === true;
    }

    /**
     * @param int $upload_dir
     * @return FilePicker
     */
    public function withDir(int $upload_dir): FilePicker
    {
        $this->set('_upload_dir', $upload_dir);
        return $this;
    }

    /**
     * @return $this
     */
    public function withAll(): FilePicker
    {
        $this->set('_upload_dir', false);
        return $this;
    }

    /**
     * @return bool
     */
    public function isAll()
    {
        return (int)$this->getUploadDir() < 1;
    }

    /**
     * @return mixed
     */
    public function getUploadDir()
    {
        return $this->get('_upload_dir');
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return isset($_POST[$this->getName()]) ? $_POST[$this->getName()] : $this->get('value');
    }
}
