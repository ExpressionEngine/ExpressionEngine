<?php

namespace EllisLab\Addons\FilePicker\Service\FilePicker;

class Link {

	protected $html;
	protected $filepicker;
	protected $attributes = array('class' => '');

	protected $type = 'list';
	protected $filters = TRUE;
	protected $uploads = TRUE;

	protected $image_selector;
	protected $value_selector;
	protected $name_selector;

	public function __construct(FilePicker $fp)
	{
		$this->filepicker = $fp;
	}

	public function render()
	{
		$url = $this->filepicker->getUrl();

		$url->addQueryStringVariables(array(
			'type' => $this->type,
			'hasFilters' => $this->filters,
			'hasUpload' => $this->uploads,
		));

		$this->setAttribute('rel', 'modal-file');
		$this->setAttribute('href', $url->compile());
		$this->addDataAttributeIfSet('input-image', $this->image_selector);
		$this->addDataAttributeIfSet('input-value', $this->value_selector);
		$this->addDataAttributeIfSet('input-name', $this->name_selector);

		$attr = '';

		foreach ($this->attributes as $key => $value)
		{
			if ($key == 'class')
			{
				$value = 'm-link filepicker '.$value;
			}

			$attr .= " {$key}='{$value}'";
		}

		return "<a{$attr}>{$this->html}</a>";
	}

	public function setAttribute($k, $v)
	{
		$this->attributes[$k] = $v;
		return $this;
	}

	public function addAttributes($attr)
	{
		foreach ($attr as $k => $v)
		{
			$this->setAttribute($k, $v);
		}

		return $this;
	}

	protected function addDataAttributeIfSet($name, $value)
	{
		if (isset($value))
		{
			$this->setAttribute('data-'.$name, $value);
		}
	}

	public function setText($text)
	{
		$this->setHtml(htmlentities($text));
		return $this;
	}

	public function setHtml($html)
	{
		$this->html = $html;
		return $this;
	}

	public function asThumbs()
	{
		$this->type = 'thumb';
		return $this;
	}

	public function asList()
	{
		$this->type = 'list';
		return $this;
	}

	public function withImage($selector)
	{
		$this->image_selector = $selector;
		return $this;
	}

	public function withValueTarget($selector)
	{
		$this->value_selector = $selector;
		return $this;
	}

	public function withNameTarget($selector)
	{
		$this->name_selector = $selector;
		return $this;
	}

	public function disableFilters()
	{
		$this->filters = FALSE;
		return $this;
	}

	public function enableFilters()
	{
		$this->filters = TRUE;
		return $this;
	}

	public function disableUploads()
	{
		$this->uploads = FALSE;
		return $this;
	}

	public function enableUploads()
	{
		$this->uploads = TRUE;
		return $this;
	}
}
