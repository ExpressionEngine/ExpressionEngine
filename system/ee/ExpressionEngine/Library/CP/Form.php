<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP;

use ExpressionEngine\Library\CP\Form\Group;
use ExpressionEngine\Library\CP\Form\Button;
use ExpressionEngine\Library\CP\Form\Fields\Hidden;

class Form
{
    /**
     * @var array
     */
    protected $prototype = [
        'save_btn_text' => 'save',
        'save_btn_text_working' => 'saving',
        'ajax_validate' => null,
        'has_file_input' => null,
        'alerts_name' => null,
        'form_hidden' => null,
        'cp_page_title_alt' => null,
        'cp_page_title' => '',
        'action_button' => null,
        'hide_top_buttons' => null,
        'extra_alerts' => null,
        'buttons' => null,
        'base_url' => '',
        'sections' => null,
        'tabs' => null
    ];

    /**
     * Contains the objects, in order, for the form
     * @var array
     */
    protected $structure = [];

    /**
     * @var array
     */
    protected $buttons = [];

    /**
     * @var array
     */
    protected $hidden_fields = [];

    /**
     * @var bool
     */
    protected $tab = false;

    /**
     * @return $this
     */
    public function asTab(): Form
    {
        $this->tab = true;
        return $this;
    }

    /**
     * @return bool
     */
    public function isTab(): bool
    {
        return $this->tab;
    }

    /**
     * @return $this
     */
    public function asHeading(): Form
    {
        $this->tab = false;
        return $this;
    }

    /**
     * @param bool $bool
     * @return $this
     */
    public function asFileUpload(bool $bool = true): Form
    {
        $this->set('has_file_input', $bool);
        return $this;
    }

    /**
     * @param $alert_name
     * @return $this
     */
    public function addAlert(string $alert_name): Form
    {
        $alerts = $this->get('extra_alerts');
        if (!is_array($alerts)) {
            $this->set('extra_alerts', []);
            $alerts = $this->get('extra_alerts');
        }

        $alerts[] = $alert_name;
        $this->set('extra_alerts', $alerts);

        return $this;
    }

    /**
     * @param string $alert_name
     * @return bool
     */
    public function removeAlert(string $alert_name): Form
    {
        $alerts = $this->get('extra_alerts');
        if (in_array($alert_name, $alerts)) {
            foreach($alerts AS $key => $alert) {
                if($alert === $alert_name) {
                    unset($alerts[$key]);
                    break;
                }
            }

            if(count($alerts) == 0) {
                $alerts = null;
            }
        }

        $this->set('extra_alerts', $alerts);

        return $this;
    }

    /**
     * @param string $name
     * @return Group
     */
    public function getGroup(string $name): Group
    {
        $tmp_name = $this->buildTmpName($name, 'group');
        if (isset($this->structure[$tmp_name])) {
            return $this->structure[$tmp_name];
        }

        $this->structure[$tmp_name] = new Group($name);
        return $this->structure[$tmp_name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function removeGroup(string $name): bool
    {
        $tmp_name = $this->buildTmpName($name, 'group');
        if (isset($this->structure[$tmp_name])) {
            unset($this->structure[$tmp_name]);
            return true;
        }

        return false;
    }

    /**
     * @param string $name
     * @return BUtton
     */
    public function getButton(string $name): Button
    {
        $tmp_name = $this->buildTmpName($name, 'button');
        if (isset($this->buttons[$tmp_name])) {
            return $this->buttons[$tmp_name];
        }

        $this->buttons[$tmp_name] = new Button($name);
        return $this->buttons[$tmp_name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function removeButton(string $name): bool
    {
        $tmp_name = $this->buildTmpName($name, 'button');
        if (isset($this->buttons[$tmp_name])) {
            unset($this->buttons[$tmp_name]);
            return true;
        }

        return false;
    }

    /**
     * @param string $name
     * @return Hidden
     */
    public function getHiddenField(string $name): Hidden
    {
        $tmp_name = $this->buildTmpName($name, 'hf');
        if (isset($this->hidden_fields[$tmp_name])) {
            return $this->hidden_fields[$tmp_name];
        }

        $this->hidden_fields[$tmp_name] = new Hidden($name);
        return $this->hidden_fields[$tmp_name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function removeHiddenField(string $name): bool
    {
        $tmp_name = $this->buildTmpName($name, 'hf');
        if (isset($this->hidden_fields[$tmp_name])) {
            unset($this->hidden_fields[$tmp_name]);
            return true;
        }

        return false;
    }

    /**
     * @param string $name
     * @param string $key
     * @return string
     */
    protected function buildTmpName(string $name, string $key): string
    {
        return '_' . $key . '_' . $name;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function set(string $name, $value): Form
    {
        $this->prototype[$name] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        if (isset($this->prototype[$key])) {
            return $this->prototype[$key];
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $return = [];
        foreach ($this->prototype as $key => $value) {
            if (!is_null($value)) {
                $return[$key] = $value;
            }
        }

        $sections = $tabs = [];
        foreach ($this->structure as $structure) {
            if ($this->isTab()) {
                $tabs[$structure->getName()] = $structure->renderTab($return);
            } else {
                $sections[$structure->getName()] = $structure->toArray();
            }
        }

        $return['sections'] = $sections;
        if ($tabs) {
            $return['tabs'] = $tabs;
            $return['sections'] = [];
        }

        $buttons = [];
        foreach ($this->buttons as $button) {
            $buttons[] = $button->toArray();
        }

        if ($buttons) {
            $return['buttons'] = $buttons;
        }

        $hidden_fields = [];
        foreach ($this->hidden_fields as $hidden_field) {
            $hidden_fields[$hidden_field->getName()] = $hidden_field->get('value');
        }

        if ($hidden_fields) {
            $return['form_hidden'] = $hidden_fields;
        }

        return $return;
    }

    /**
     * @param string $text
     * @param string $href
     * @param string $rel
     * @return $this
     */
    public function withActionButton(string $text, string $href, string $rel = ''): Form
    {
        $this->set('action_button', ['text' => $text, 'href' => $href, 'rel' => $rel]);
        return $this;
    }

    /**
     * @return $this
     */
    public function withOutActionButton(): Form
    {
        $this->set('action_button', null);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSaveBtnText(): ?string
    {
        return $this->get('save_btn_text');
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setSaveBtnText(string $text): Form
    {
        $this->set('save_btn_text', $text);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSaveBtnTextWorking(): ?string
    {
        return $this->get('save_btn_text_working');
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setSaveBtnTextWorking(string $text): Form
    {
        $this->set('save_btn_text_working', $text);
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getAjaxValidate(): ?bool
    {
        return $this->get('ajax_validate');
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setAjaxValidate(bool $boolean): Form
    {
        $this->set('ajax_validate', $boolean);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAlertsName(): ?string
    {
        return $this->get('alerts_name');
    }

    /**
     * @return mixed
     */
    public function setAlertsName(string $text): Form
    {
        $this->set('alerts_name', $text);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCpPageTitleAlt(): ?string
    {
        return $this->get('cp_page_title_alt');
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setCpPageTitleAlt(string $text): Form
    {
        $this->set('cp_page_title_alt', $text);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCpPageTitle(): ?string
    {
        return $this->get('cp_page_title');
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setCpPageTitle(string $text): Form
    {
        $this->set('cp_page_title', $text);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHideTopButtons()
    {
        return $this->get('hide_top_buttons');
    }

    /**
     * @param bool $boolean
     * @return $this
     */
    public function setHideTopButtons(bool $boolean): Form
    {
        $this->set('hide_top_buttons', $boolean);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBaseUrl(): ?string
    {
        return $this->get('base_url');
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setBaseUrl(string $url): Form
    {
        $this->set('base_url', $url);
        return $this;
    }

    /**
     * Renders EE's shared form
     * @return string [rendered form view]
     */
    public function render()
    {
        return ee('View')->make('ee:_shared/form')->render($this->toArray());
    }
}
