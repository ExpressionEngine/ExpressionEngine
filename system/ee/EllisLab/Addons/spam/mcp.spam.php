<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\Data\Collection as CoreCollection;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Spam Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class Spam_mcp {

	public $stop_words_path = "spam/training/stopwords.txt";
	public $stop_words = array();

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	public function __construct()
	{
		$this->base_url = ee('CP/URL')->make('addons/settings/spam');
		$update = ee('spam:Update');
	}

	/**
	 * Controller for the index view
	 *
	 * @access public
	 * @return void
	 */
	public function index()
	{
		$method = ee()->input->get('method');
		if (AJAX_REQUEST && ! empty($method))
		{
			return $this->$method();
		}

		if ( ! empty($_POST['bulk_action']))
		{
			$action = ee()->input->post('bulk_action');
			$selection = ee('Model')->get('spam:SpamTrap', ee()->input->post('selection'))->all();

			if ($action == 'approve')
			{
				$this->approve($selection);
			}

			if ($action == 'remove')
			{
				$this->remove($selection);
			}
		}

		$table = ee('CP/Table');
		$data = array();
		$trapped = array();

		$content_type = array(
			'Comment' => 'comment',
			'Email' => 'email',
			'Forum_core' => 'forum_post',
			'Wiki' => 'wiki_post',
		);

		$options = array(
			'all' => lang('all'),
			'Comment' => lang('comment'),
			'Email' => lang('email'),
			'Forum_core' => lang('forum_post'),
			'Wiki' => lang('wiki_post')
		);

		$total = ee('Model')->get('spam:SpamTrap')->count();
		$types = ee('CP/Filter')->make('content_type', 'content_type', $options);
		$types->setPlaceholder(lang('all'));
		$types->disableCustomValue();

		$filters = ee('CP/Filter')
			->add($types)
			->add('Date', 'date')
			->add('Perpage', $total, 'show_all_spam');

		$data['filters'] = $filters->render($this->base_url);

		$filter_values = $filters->values();
		$filter_fields = array();
		$search = ee()->input->post('search');
		$this->base_url->addQueryStringVariables($filter_values);

		if ( ! empty($filter_values['content_type']))
		{
			$filter_fields['class'] = $filter_values['content_type'];
		}

		if ( ! empty($filter_values['filter_by_date']))
		{
			$filter_fields['date'] = $filter_values['filter_by_date'];
		}

		$table->setColumns(
			array(
				'spam_content' => array(
					'encode' => FALSE
				),
				'date',
				'ip',
				'spam_type',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);

		$trap = $this->getSpamTrap($filter_fields, $table->sort_col, $table->sort_dir, $search, $filter_values['perpage'], ($table->config['page'] - 1) * $filter_values['perpage']);

		foreach ($trap as $spam)
		{
			$toolbar = array('toolbar_items' => array(
				'view' => array(
					'href' => '#',
					'class' => 'spam-detail',
					'rel' => 'spam-modal',
					'title' => strtolower(lang('edit')),
					'data-content' => htmlentities(nl2br($spam->document), ENT_QUOTES, 'UTF-8'),
					'data-type' => htmlentities($spam->class, ENT_QUOTES, 'UTF-8'),
					'data-date' => ee()->localize->human_time($spam->date->getTimestamp()),
					'data-ip' => htmlentities($spam->ip_address, ENT_QUOTES, 'UTF-8'),
				)
			));

			if ( ! empty($spam->Author))
			{
				$author = $spam->Author->getMemberName();
			}
			else
			{
				$author = lang('guest');
			}

			$summary = substr($spam->document, 0, 60) . '...';
			$title = htmlentities($summary, ENT_QUOTES, 'UTF-8');
			$title .= '<br><span class="meta-info">&mdash; ' . lang('by') . ': ' . htmlentities($author, ENT_QUOTES, 'UTF-8') . '</span>';

			$trapped[] = array(
				'content' => $title,
				'date' => ee()->localize->human_time($spam->date->getTimestamp()),
				'ip' => $spam->ip_address,
				'type' => lang($content_type[$spam->class]),
				$toolbar,
				array(
					'name' => 'selection[]',
					'value' => $spam->trap_id,
					'data'	=> array(
						'confirm' => lang('spam') . ': <b>' . htmlentities($summary, ENT_QUOTES, 'UTF-8') . '</b>'
					)
				)
			);
		}

		$table->setNoResultsText(sprintf(lang('no_found'), lang('spam')));
		$table->setData($trapped);

		$data['table'] = $table->viewData($this->base_url);
		$data['form_url'] = ee('CP/URL')->make('addons/settings/spam');
		$data['cp_page_title'] = lang('all_spam');

		// Set search results heading
		if ( ! empty($data['table']['search']))
		{
			$data['cp_heading'] = sprintf(
				lang('search_results_heading'),
				$this->total,
				$data['table']['search']
			);
		}

		if ( ! empty($data['table']['data']))
		{
			$data['pagination'] = ee('CP/Pagination', $total)
				->perPage($table->config['limit'])
				->currentPage($table->config['page'])
				->render($this->base_url);
		}

		ee()->cp->add_js_script(array(
			'file' => array('cp/addons/spam'),
		));

		return array(
			'body'       => ee('View')->make('spam:index')->render($data),
			'heading' => lang('all_spam')
		);
	}

	/**
	 * Controller method for the spam module settings page
	 *
	 * @access public
	 * @return void
	 */
	public function settings()
	{
		$base_url = ee('CP/URL')->make('addons/settings/spam/settings');
		ee()->load->library('form_validation');

		$settings = array(
			'sensitivity' => ee()->config->item('spam_sensitivity') ?: 70,
			'word_limit' => ee()->config->item('spam_word_limit') ?: 5000,
            'content_limit' => ee()->config->item('spam_content_limit') ?: 5000
        );

		$sensitivity = ee()->input->post('spam_sensitivity') ?:$settings['sensitivity'];

		$vars['sections'] = array(
			array(
				array(
					'title' => "<span class='range-value'>{$sensitivity}</span>% " . lang('spam_sensitivity'),
					'desc' => 'spam_sensitivity_desc',
					'fields' => array(
						'spam_sensitivity' => array(
							'type' => 'slider',
							'value' => $sensitivity
						)
					)
				),
			),
			'engine_training' => array(
				array(
					'title' => "update_training",
					'desc' => 'update_training_desc',
					'fields' => array(
						'update_training' => array(
							'type' => 'html',
							'content' => "<a class='btn tn action update' href='" . ee('CP/URL', 'addons/settings/spam/') . "'>" .  lang('update_training') . "</a>"
						)
					)
				),
				array(
					'title' => 'spam_word_limit',
					'desc' => 'spam_word_limit_desc',
					'fields' => array(
						'spam_word_limit' => array('type' => 'text', 'value' => $settings['word_limit'])
					)
				),
				array(
					'title' => 'spam_content_limit',
					'desc' => 'spam_content_limit_desc',
					'fields' => array(
						'spam_content_limit' => array('type' => 'text', 'value' => $settings['content_limit'])
					)
				),
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'spam_sensitivity',
				 'label'   => 'lang:spam_sensitivity',
				 'rules'   => 'required|numeric'
			),
			array(
				 'field'   => 'spam_word_limit',
				 'label'   => 'lang:spam_word_limit',
				 'rules'   => 'required|is_natural_no_zero'
			),
			array(
				 'field'   => 'spam_content_limit',
				 'label'   => 'lang:spam_content_limit',
				 'rules'   => 'required|is_natural_no_zero'
			)
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$fields = array();

			// Make sure we're getting only the fields we asked for
			foreach ($vars['sections'] as $settings)
			{
				foreach ($settings as $setting)
				{
					foreach ($setting['fields'] as $field_name => $field)
					{
						$fields[$field_name] = ee()->input->post($field_name);
					}
				}
			}

			$config_update = ee()->config->update_site_prefs($fields);

			if ( ! empty($config_update))
			{
				ee()->load->helper('html_helper');
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('cp_message_issue'))
					->addToBody($config_update)
					->defer();
			}
			else
			{
				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('success'))
					->addToBody(lang('spam_settings_updated'))
					->defer();

				// Delete the classifier from shared memory if our settings changed
				ee('spam:Training', 'default')->deleteClassifier();
			}

			ee()->functions->redirect($base_url);

		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('settings_save_error'))
				->addToBody(lang('settings_save_error_desc'))
				->defer();
			ee()->functions->redirect($base_url);
		}

		ee()->cp->add_js_script(array(
			'file' => array('cp/addons/spam'),
		));

		$vars['base_url'] = $base_url;
		$vars['ajax_validate'] = TRUE;
		$vars['cp_page_title'] = lang('spam_settings');
		$vars['save_btn_text'] = 'btn_save_settings';
		$vars['save_btn_text_working'] = 'btn_saving';

		$download_ajax_fail = ee('CP/Alert')->makeBanner('reorder-ajax-fail')
			->asIssue()
			->canClose()
			->withTitle(lang('training_update_failed'))
			->addToBody('%s');

		ee()->javascript->set_global('alert.download_ajax_fail', $download_ajax_fail->render());

		return array(
			'body'       => ee('View')->make('spam:form')->render(array('data' => $vars)),
			'breadcrumb' => array(
				ee('CP/URL')->make('addons/settings/spam')->compile() => lang('spam')
			),
			'heading' => lang('spam_settings')
		);
	}

	/**
	 * This method is used when content in the spam trap is marked as a false
	 * positive. It grabs the stored callback from the spam trap, runs it,
	 * and then clears that entry from the spam trap. Everywhere that uses the
	 * spam module is reponsible for providing it's own callback when it calls
	 * the moderate method.
	 *
	 * @param mixed $trapped
	 * @access private
	 * @return void
	 */
	private function approve($trapped)
	{
		foreach ($trapped as $spam)
		{
			if ( ! class_exists($spam->class))
			{
				ee()->load->file($spam->file);
			}

			$class = $spam->class;
			$class = new $class();

			$data = unserialize($spam->data);
			call_user_func_array(array($class, $spam->approve), $data);
		}

		ee('CP/Alert')->makeInline('spam')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(sprintf(lang('spam_trap_approved'), count($trapped)))
			->defer();

		$this->moderate($trapped, 'ham');
		ee()->functions->redirect($this->base_url);
	}

	/**
	 * This method is used when content in the spam trap is confirmed as spam.
	 * It will simply delete the content from the spam trap.
	 *
	 * @param CoreCollection $trapped
	 * @access public
	 * @return void
	 */
	public function remove($trapped)
	{
		foreach ($trapped as $spam)
		{
			if ( ! class_exists($spam->class))
			{
				ee()->load->file($spam->file);
			}

			$class = $spam->class;
			$class = new $class();

			$data = unserialize($spam->data);
			call_user_func_array(array($class, $spam->remove), $data);
		}

		ee('CP/Alert')->makeInline('spam')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(sprintf(lang('spam_trap_removed'), count($trapped)))
			->defer();

		$this->moderate($trapped, 'spam');
		ee()->functions->redirect($this->base_url);
	}

	public function download()
	{
		if ( ! AJAX_REQUEST)
		{
			show_error(lang('unauthorized_access'));
		}

		try
		{
			ee('spam:Update')->download();
		}
		catch (\Exception $error)
		{
			ee()->output->send_ajax_response(array(
				'error' => $error->getMessage()
			));
		}

		ee()->output->send_ajax_response(array(
			'success' => lang('training_downloaded')
		));
	}

	public function prepare()
	{
		if ( ! AJAX_REQUEST)
		{
			show_error(lang('unauthorized_access'));
		}

		try
		{
			ee('spam:Update')->prepare();
		}
		catch (\Exception $error)
		{
			ee()->output->send_ajax_response(array(
				'error' => $error->getMessage()
			));
		}

		ee()->output->send_ajax_response(array(
			'success' => lang('training_prepared')
		));
	}

	public function updateparams()
	{
		if ( ! AJAX_REQUEST)
		{
			show_error(lang('unauthorized_access'));
		}

		try
		{
			$processing = ee('spam:Update')->updateParameters();
		}
		catch (\Exception $error)
		{
			ee()->output->send_ajax_response(array(
				'error' => $error->getMessage()
			));
		}

		if ($processing === TRUE)
		{
			$status = 'processing';
		}
		else
		{
			$status = 'finished';
		}

		$spam_training = ee('spam:Training', 'default');
		$spam_training->deleteClassifier();

		ee()->output->send_ajax_response(array(
			'message' => lang('updating_parameters'),
			'finished' => lang('training_finished'),
			'status' => $status
		));
	}

	public function updatevocab()
	{
		if ( ! AJAX_REQUEST)
		{
			show_error(lang('unauthorized_access'));
		}

		try
		{
			$processing = ee('spam:Update')->updateVocabulary();
		}
		catch (\Exception $error)
		{
			ee()->output->send_ajax_response(array(
				'error' => $error->getMessage()
			));
		}

		if ($processing === TRUE)
		{
			$status = 'proccessing';
		}
		else
		{
			$status = 'finished';
		}

		$spam_training = ee('spam:Training', 'default');
		$spam_training->deleteClassifier();

		ee()->output->send_ajax_response(array(
			'message' => lang('updating_vocabulary'),
			'finished' => lang('training_finished'),
			'status' => $status
		));
	}

	/**
	 * Moderate content. Will insert record into training table and either delete
	 * or reinsert the data if it's spam or ham respectively.
	 *
	 * @param integer $id    ID of the content to moderate
	 * @param boolean $class  The name of the class this was flagged as
	 * @access public
	 * @return void
	 */
	private function moderate($collection, $class = 'spam')
	{
		$result = array();

		foreach ($collection as $spam)
		{
			$result[] = ee('Model')->make('spam:SpamTraining', array(
				'source' => $spam->document,
				'class' => $class
			));
			$spam->delete();
		}

		$result = new CoreCollection($result);
		$this->trainParameters($result);
		$result->save();
	}

	/**
	 * Grab the appropriate kernel ID or insert a new one
	 *
	 * @param string $name The name of the kernel
	 * @access private
	 * @return int The kernel ID
	 */
	private function getKernel($name = 'default')
	{
		return ee('Model')->get('spam:SpamKernel')->filter('name', $name)->first();
	}

	/**
	 * Returns an array of content flagged as spam
	 *
	 * @param  integer $limit The number of entries to grab
	 * @access private
	 * @return array   Array of content to moderate
	 */
	private function getSpamTrap($filters = array(), $sort = NULL, $direction = 'asc', $search = null, $limit = 1000, $offset = 0)
	{
		$result = ee('Model')->get('spam:SpamTrap');

		if ( ! empty($filters))
		{
			foreach ($filters as $key => $filter)
			{
				if ( ! empty($filter))
				{
					$result->filter($key, $filter);
				}
			}
		}

		if ( ! empty($search))
		{
			$result->filter('document', 'LIKE', "%$search%");
		}

		if ( ! empty($sort))
		{
			$options = array(
				'content_type' => 'class',
				'spam_content' => 'document',
				'date' => 'date',
			);
			$result->order($options[$sort], $direction);
		}

		$this->total = $result->count();

		if ( ! empty($limit))
		{
			$result->limit($limit);
		}

		if ( ! empty($offset))
		{
			$result->offset($offset);
		}

		return $result->all();
	}

	/**
	 * Returns an array of member data for all our known spammers
	 *
	 * @access private
	 * @return array
	 */
	private function getSpammers($limit = 1000)
	{
		return ee('Model')->get('spam:SpamTraining')
					->with('Author')
					->filter('class', 'spam')
					->limit($limit)
					->all();
	}

	/**
	 * Returns an array of member data for known non-spammers
	 *
	 * @access private
	 * @return array
	 */
	private function getRealPeople($limit = 1000)
	{
		return ee('Model')->get('spam:SpamTraining')
					->with('Author')
					->filter('class', 'ham')
					->limit($limit)
					->all();
	}

	/**
	 * Returns an array of sources and classes for training
	 *
	 * @access private
	 * @return array
	 */
	private function getTrainingData($limit = 1000)
	{
		return ee('Model')->get('spam:SpamTraining')
					->limit($limit)
					->all();
	}

	/**
	 * Sets the maximum likelihood estimates for a given training set and kernel
	 *
	 * @param array $training Multi-dimensional array of training data:
	 * 						  $class => array(
	 * 						  	  array($feature0, $feauture1, ...),
	 * 						  	  ...
	 * 						  )
	 * @param string $kernel
	 * @access private
	 * @return void
	 */
	private function setMaximumLikelihood($classes, $collection, $existing,$kernel)
	{
		$training = array();
		$update = array();
		$insert = array();


		foreach ($classes as $class => $sources)
		{
			$count = count($sources[0]);
			$zipped = array();

			foreach ($sources as $key => $row)
			{
				for ($i = 0; $i < $count; $i++)
				{
					$zipped[$i][] = $row[$i];
				}
			}

			$query = ee()->db->where('class', $class)->get('spam_parameters');
			$parameters = array();

			foreach ($query->result() as $parameter)
			{
				$parameters[$parameter->index] = $parameter;
			}

			$lookup = array();

			foreach ($collection->vectorizers as $i => $vectorizer)
			{
				if ($vectorizer instanceof EllisLab\Addons\Spam\Library\Vectorizers\Tfidf)
				{
					$vocab = array_keys($vectorizer->vocabulary);

					for ($j = 0; $j < count($vocab); $j++)
					{
						$lookup[$i + $j] = array('term' => $existing[$vocab[$j]]);
					}
				}
				else
				{
					$lookup[$i] = array('index' => $i);
				}
			}

			foreach ($zipped as $index => $feature)
			{
				$count = 0;
				$mean = 0;
				$variance = 0;
				$new = TRUE;
				$id = NULL;

				if ( ! empty($parameters[$index]))
				{
					$id = $parameters[$index]->parameter_id;
					$mean = $parameters[$index]->mean;
					$variance = $parameters[$index]->variance;
					$count = $kernel->count;
					$new = FALSE;
				}

				$updated = $this->onlineStatistics($count, $mean, $variance, $feature);

				$training = array(
					'kernel_id' => $kernel->kernel_id,
					'class' => $class,
					'mean' => $updated['mean'],
					'variance' => $updated['variance'],
					'index' => NULL,
					'term' => NULL,
				);

				if (empty($lookup[$index]['term']))
				{
					$training['index'] = $lookup[$index]['index'];
				}
				else
				{
					$training['term'] = (int)$lookup[$index]['term']->vocabulary_id;
				}

				if ($new)
				{
					$insert[] = $training;
				}
				else
				{
					$training['parameter_id'] = $id;
					$update[] = $training;
				}
			}
		}

		if ( ! empty($insert))
		{
			ee()->db->insert_batch('exp_spam_parameters', $insert);
		}

		if ( ! empty($update))
		{
			ee()->db->update_batch('exp_spam_parameters', $update, 'parameter_id');
		}
	}

	/**
	 * Loops through all content marked as spam/ham and re-trains the parameters
	 *
	 * @access private
	 * @return void
	 */
	private function trainParameters($data)
	{
		$classes = $data->pluck('class');
		$documents = $data->pluck('source');

		$stopwords = explode("\n", ee()->lang->load('spam/stopwords', NULL, TRUE, FALSE));
		$tokenizer = ee('spam:Tokenizer');
		$tfidf = ee('spam:Vectorizers/Tfidf', $documents, $tokenizer, $stopwords);

		$kernel = $this->getKernel('default');
		$insert = array();
		$update = array();

		ee()->db->where_in('term', array_keys($tfidf->vocabulary));
		$query = ee()->db->get('spam_vocabulary');

		foreach ($query->result() as $term)
		{
			$existing[$term->term] = $term;
		}

		foreach ($tfidf->vocabulary as $term => $count)
		{
			if ( ! empty($existing[$term]))
			{
				$update[] = array(
					'term' => $term,
					'count' => (int)$existing[$term]->count + (int)$count,
					'kernel_id' => $kernel->kernel_id
				);
			}
			else
			{
				$insert[] = array(
					'term' => $term,
					'count' => (int) $count,
					'kernel_id' => $kernel->kernel_id
				);
			}
		}


		if ( ! empty($insert))
		{
			ee()->db->insert_batch('exp_spam_vocabulary', $insert);
		}

		if ( ! empty($update))
		{
			ee()->db->update_batch('exp_spam_vocabulary', $update, 'term');
		}

		// Add all our new vocab ids to the existing array for later use
		ee()->db->where_in('term', array_keys($tfidf->vocabulary));
		$query = ee()->db->get('spam_vocabulary');

		foreach ($query->result() as $term)
		{
			$existing[$term->term] = $term;
		}


		// Increment the total document count
		$kernel->count += count($data);
		$kernel->save();
		$tfidf->document_count = $kernel->count;
		$tfidf->generateLookups();

		// Calculate our new feature vectors
		$vectorizers = array();
		$vectorizers[] = ee('spam:Vectorizers/ASCIIPrintable');
		$vectorizers[] = ee('spam:Vectorizers/Entropy');
		$vectorizers[] = ee('spam:Vectorizers/Links');
		$vectorizers[] = ee('spam:Vectorizers/Punctuation');
		$vectorizers[] = ee('spam:Vectorizers/Spaces');
		$vectorizers[] = $tfidf;
		$training_collection = ee('spam:Collection', $vectorizers);
		$training_classes = array();

		foreach ($training_collection->fitTransform($documents) as $key => $vector)
		{
			$training_classes[$classes[$key]][] = $vector;
		}

		$this->setMaximumLikelihood($training_classes, $training_collection, $existing,  $kernel);
		$spam_training = ee('spam:Training', 'default');
		$spam_training->deleteClassifier();
	}

	/**
	 * This will create initial training data from everything that's in the
	 * training table
	 *
	 * @access private
	 * @return void
	 */
	public function trainAll()
	{
		set_time_limit(0);
		ini_set('max_execution_time', 0);
		ini_set('memory_limit','64G');
		$start_time = microtime(true);
		$this->trainParameters(ee('Model')->get('spam:SpamTraining')->all());
		$time = (microtime(true) - $start_time);
		var_dump($time);
		die();
	}

	/**
	 * Use Knuth's algorithm to update the mean and variance as we go. This
	 * should avoid any numerical instability due to cancellation
	 *
	 * @param mixed $count
	 * @param mixed $mean
	 * @param mixed $variance
	 * @param mixed $data
	 * @access private
	 * @return void
	 */
	private function onlineStatistics($count = 0, $mean = 0, $variance = 0, $data = array())
	{
		foreach ($data as $datum)
		{
			$count++;
			$delta = $datum - $mean;
			$mean = $mean + ($delta / $count);
			$variance = $variance + $delta * ($datum - $mean);
		}

		if ($count > 1)
		{
			$variance = $variance / ($count - 1);
		}

		return array('mean' => $mean, 'variance' => $variance);
	}

}

// EOF
