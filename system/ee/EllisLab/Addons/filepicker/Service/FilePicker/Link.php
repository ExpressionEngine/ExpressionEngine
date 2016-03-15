<?php

namespace EllisLab\Addons\FilePicker\Service\FilePicker;

class Link {

	protected $html;
	protected $filepicker;
	protected $selected;
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

	public function __toString()
	{
		return $this->render();
	}

	/**
	 * Render the link
	 *
	 * @return String An html link
	 */
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
		$this->addDataAttributeIfSet('selected', $this->selected);

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

	/**
	 * Set filename of the current selection
	 *
	 * @param String $filename Name of the selected file
	 * @return Link
	 */
	public function setSelected($filename)
	{
		$this->selected = $filename;
		return $this;
	}

	/**
	 * Set an HTML attribute on the link
	 *
	 * @param String $k The attribute key
	 * @param String $v The attribute value
	 * @return Link
	 */
	public function setAttribute($k, $v)
	{
		$this->attributes[$k] = $v;
		return $this;
	}

	/**
	 * Set several HTML attributes on the link
	 *
	 * @param Array[String] $attr Key/value pairs of attributes
	 * @return Link
	 */
	public function addAttributes($attr)
	{
		foreach ($attr as $k => $v)
		{
			$this->setAttribute($k, $v);
		}

		return $this;
	}

	/**
	 * Create a data attribute on the link if the value is not null
	 *
	 * @param String $name The attribute key, sans "data-"
	 * @param String $value The attribute value
	 * @return Link
	 */
	protected function addDataAttributeIfSet($name, $value)
	{
		if (isset($value))
		{
			$this->setAttribute('data-'.$name, $value);
		}
	}

	/**
	 * Set the innerText of the link. Encodes html.
	 *
	 * @param String $text The link text
	 * @return Link
	 */
	public function setText($text)
	{
		$this->setHtml(htmlentities($text));
		return $this;
	}

	/**
	 * Set the innerHTML of the link
	 *
	 * @param String $html The link html
	 * @return Link
	 */
	public function setHtml($html)
	{
		$this->html = $html;
		return $this;
	}

	/**
	 * Show the filepicker as thumbnails
	 *
	 * @return Link
	 */
	public function asThumbs()
	{
		$this->type = 'thumb';
		return $this;
	}

	/**
	 * Show the filepicker as a list [default]
	 *
	 * @return Link
	 */
	public function asList()
	{
		$this->type = 'list';
		return $this;
	}

	/**
	 * Populate the given image when clicking on a filepicker item
	 *
	 * @param String $selector Id of the image
	 * @return Link
	 */
	public function withImage($selector)
	{
		$this->image_selector = $selector;
		return $this;
	}

	/**
	 * Populate the given form field with the id of the selected image
	 *
	 * @param String $selector Name of the form field
	 * @return Link
	 */
	public function withValueTarget($selector)
	{
		$this->value_selector = $selector;
		return $this;
	}

	/**
	 * Populate the given form field with the image name when clicking on a filepicker item
	 *
	 * @param String $selector Id of the form field
	 * @return Link
	 */
	public function withNameTarget($selector)
	{
		$this->name_selector = $selector;
		return $this;
	}

	/**
	 * Show the filepicker without filters
	 *
	 * @return Link
	 */
	public function disableFilters()
	{
		$this->filters = FALSE;
		return $this;
	}

	/**
	 * Show the filepicker with filters [default]
	 *
	 * @return Link
	 */
	public function enableFilters()
	{
		$this->filters = TRUE;
		return $this;
	}

	/**
	 * Show the filepicker without uploads
	 *
	 * @return Link
	 */
	public function disableUploads()
	{
		$this->uploads = FALSE;
		return $this;
	}

	/**
	 * Show the filepicker with uploads [default]
	 *
	 * @return Link
	 */
	public function enableUploads()
	{
		$this->uploads = TRUE;
		return $this;
	}
}

// EOF
