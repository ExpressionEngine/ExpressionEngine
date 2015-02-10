<?php

namespace EllisLab\ExpressionEngine\Service\Validation;

use EllisLab\ExpressionEngine\Service\View\View;
use EllisLab\ExpressionEngine\Service\View\StringView;

class Result {

	/**
	 * @var View
	 */
	protected $default_view;

	/**
	 * @var array List of failed fields ([field => errors])
	 */
	protected $failed = array();

	/**
	 * Add a failed rule. Used internally to populate the result
	 *
	 * @param String $field Field name
	 * @param String $rule Rule name
	 */
	public function addFailed($field, $rule)
	{
		if ( ! isset($this->failed[$field]))
		{
			$this->failed[$field] = array();
		}

		$this->failed[$field][] = $rule;
	}

	/**
	 * Get all failures as [field => failed_rules]
	 */
	public function getFailed($field = NULL)
	{
		if (isset($field))
		{
			return $this->failed[$field];
		}

		return $this->failed;
	}

	/**
	 * Check if the validated object is valid
	 *
	 * @return bool Valid?
	 */
	public function isValid()
	{
		return (bool) empty($this->failed);
	}

	/**
	 * Convenience alias to isValid. Depending on your data and the
	 * result variable name `passed()` can sometimes work better than
	 * `isValid()`.
	 */
	public function passed()
	{
		return $this->isValid();
	}

	/**
	 * Check if the validated object is invalid
	 *
	 * @return bool Invalid?
	 */
	public function isNotValid()
	{
		return ! $this->isValid();
	}

	/**
	 * Convenience alias to isValid. Depending on your data and the
	 * result variable name `failed()` can sometimes work better than
	 * `isNotValid()`.
	 */
	public function failed()
	{
		return $this->isNotValid();
	}

	/**
	 *
	 */
	public function hasErrors($field)
	{
		return array_key_exists($field, $this->failed);
	}

	/**
	 *
	 */
	public function getErrors($field)
	{
		return $this->renderError($field, $this->getDefaultView());
	}

	/**
	 *
	 */
	public function renderErrors(View $view = NULL)
	{
		$out = array();

		foreach ($this->failed as $field => $rule)
		{
			$out[$field] = $this->renderError($field, $view);
		}

		return $out;
	}

	/**
	 *
	 */
	public function renderError($field, View $view = NULL)
	{
		if ( ! $this->hasErrors($field))
		{
			return '';
		}

		$rules = $this->failed[$field];

		$view = $view ?: $this->getDefaultView();

		return trim(
			$view->render(compact('field', 'rules'))
		);
	}

	/**
	 *
	 */
	protected function getDefaultView()
	{
		if ( ! isset($this->default_view))
		{
			$this->default_view = new StringView($this->getDefaultTemplate());
		}

		return $this->default_view;
	}

	/**
	 *
	 */
	protected function getDefaultTemplate()
	{
		return <<<'STR'
		<?php $this->lang->load('form_validation'); ?>

		<?php foreach ($rules as $rule): ?>
			<?php list($key, $params) = $rule->getLanguageData(); ?>
			<p><?=sprintf(lang($key), $params) ?></p>
		<?php endforeach; ?>
STR;
	}
}