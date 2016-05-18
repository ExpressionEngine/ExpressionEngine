<?php

namespace EllisLab\ExpressionEngine\Service\Validation;

use EllisLab\ExpressionEngine\Service\View\View;
use EllisLab\ExpressionEngine\Service\View\StringView;

class Result {

	/**
	 * @var Default formatted view
	 */
	protected $default_view;

	/**
	 * @var Default view for line items
	 */
	protected $line_view;

	/**
	 * @var array List of failed fields ([field => errors])
	 */
	protected $failed = array();

	/**
	 * Add a failed rule. Used internally to populate the result
	 *
	 * @param String $field Field name
	 * @param ValidationRule $rule Failed rule object
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
	 * Check if a field has errors
	 */
	public function hasErrors($field)
	{
		return array_key_exists($field, $this->failed);
	}

	/**
	 * Get error strings for a given field
	 */
	public function getErrors($field, View $view = NULL)
	{
		$out = array();

		$view = $view ?: $this->getLineView();

		foreach ($this->failed[$field] as $rule)
		{
			$out[$rule->getName()] = trim($view->render(compact('rule')));
		}

		return $out;
	}

	/**
	 * Get all error strings for all failed fields
	 */
	public function getAllErrors(View $view = NULL)
	{
		$out = array();

		foreach (array_keys($this->failed) as $field)
		{
			$out[$field] = $this->getErrors($field, $view);
		}

		return $out;
	}

	/**
	 * Get failed rule objects
	 */
	public function getFailedRules($field = NULL)
	{
		if (isset($field))
		{
			return $this->failed[$field];
		}

		return $this->failed;
	}

	/**
	 * Render
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
	protected function getLineView()
	{
		if ( ! isset($this->line_view))
		{
			$this->line_view = new StringView($this->getLineTemplate());
		}

		return $this->line_view;
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
			<em class="ee-form-error-message"><?=vsprintf(lang($key), $params) ?></em>
		<?php endforeach; ?>
STR;
	}

	/**
	 *
	 */
	protected function getLineTemplate()
	{
		return <<<'STR'
		<?php $this->lang->load('form_validation'); ?>
		<?php list($key, $params) = $rule->getLanguageData(); ?>
		<?=vsprintf(lang($key), $params) ?>
STR;
	}
}

// EOF
