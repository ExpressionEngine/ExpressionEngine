<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\Data\Collection as CoreCollection;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
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
		$this->base_url = ee('CP/URL', 'addons/settings/spam');
		ini_set('memory_limit', '16G');
		set_time_limit(0);
	}

	/**
	 * Controller for the index view
	 * 
	 * @access public
	 * @return void
	 */
	public function index()
	{
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
			'Forum_core' => 'forum_post',
			'Wiki' => 'wiki_post',
		);
		$options = array(
			'all' => lang('all'),
			'Comment' => lang('comment'),
			'Forum_core' => lang('forum_post'),
			'Wiki' => lang('wiki_post')
		);
		$total = ee('Model')->get('spam:SpamTrap')->count();

		$types = ee('Filter')->make('content_type', 'content_type', $options);
		$types->setPlaceholder(lang('all'));
		$types->disableCustomValue();

		$filters = ee('Filter')
			->add($types)
			->add('Date', 'date')
			->add('Perpage', $total, 'show_all_spam');

		$filter_values = $filters->values();
		$perpage = ee()->input->get('perpage') ?: 20;
		$sort_col = ee()->input->get('sort_col') ?: 'date';
		$sort_dir = ee()->input->get('sort_dir') ?: 'desc';
		$page = ee()->input->get('page') > 0 ? ee()->input->get('page') : 1;
		$offset = ! empty($page) ? ($page - 1) * $perpage : 0;
		$search = ee()->input->post('search');

		$filter_fields = array();

		if ( ! empty($filter_values['content_type']))
		{
			$filter_fields['class'] = $filter_values['content_type'];
		}

		if ( ! empty($filter_values['filter_by_date']))
		{
			$filter_fields['date'] = $filter_values['filter_by_date'];
		}

		$trap = $this->getSpamTrap($filter_fields, $sort_col, $sort_dir, $search, $perpage, $offset);

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

		foreach ($trap as $spam)
		{
			$toolbar = array('toolbar_items' => array(
				'view' => array(
					'href' => '#',
					'class' => 'spam-detail',
					'rel' => 'spam-modal',
					'title' => strtolower(lang('edit')),
					'data-content' => htmlentities(nl2br($spam->document), ENT_QUOTES),
					'data-type' => htmlentities($spam->class, ENT_QUOTES),
					'data-date' => ee()->localize->human_time($spam->date->getTimestamp()),
					'data-ip' => htmlentities($spam->ip_address, ENT_QUOTES),
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
			$title = htmlentities($summary, ENT_QUOTES);
			$title .= '<br><span class="meta-info">&mdash; ' . lang('by') . ': ' . htmlentities($author, ENT_QUOTES) . '</span>';

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
						'confirm' => lang('spam') . ': <b>' . htmlentities($summary, ENT_QUOTES) . '</b>'
					)
				)
			);
		}

		$table->setNoResultsText('no_search_results');
		$table->setData($trapped);

		$this->base_url->addQueryStringVariables($filter_values);
		$this->base_url->setQueryStringVariable('sort_col', $table->sort_col);
		$this->base_url->setQueryStringVariable('sort_dir', $table->sort_dir);

		$data['filters'] = $filters->render($this->base_url);
		$data['table'] = $table->viewData($this->base_url);
		$data['form_url'] = cp_url('addons/settings/spam');
		$data['cp_page_title'] = lang('all_spam');

		// Set search results heading
		if ( ! empty($data['table']['search']))
		{
			ee()->view->cp_heading = sprintf(
				lang('search_results_heading'),
				$data['table']['total_rows'],
				$data['table']['search']
			);
		}

		if ( ! empty($data['table']['data']))
		{
			$data['pagination'] = ee('CP/Pagination', $total)
				->perPage($perpage)
				->currentPage($page)
				->render($this->base_url);
		}

		ee()->javascript->set_global('lang.remove_confirm', lang('spam') . ': <b>### ' . lang('spam') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/v3/confirm_remove'),
			'file' => array('cp/addons/spam'),
		));

		return ee('View')->make('spam:index')->render($data);
	}

	public function settings()
	{
		$base_url = ee('CP/URL', 'addons/settings/spam/settings', ee()->session->session_id());
		ee()->load->library('form_validation');

		$settings = array(
			'sensitivity' => empty(ee()->config->item('spam_sensitivity')) ? 70 : ee()->config->item('spam_sensitivity'),
			'word_limit' => empty(ee()->config->item('spam_word_limit')) ? 5000 : ee()->config->item('spam_word_limit'),
			'content_limit' => empty(ee()->config->item('spam_content_limit')) ? 5000 : ee()->config->item('spam_content_limit')
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
				ee()->view->set_message('issue', lang('cp_message_issue'), ul($config_update), TRUE);
				ee()->functions->redirect($base_url);

			}
			else
			{
				ee()->view->set_message('success', lang('success'), lang('spam_settings_updated'), TRUE);
			}
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee()->view->set_message('issue', lang('settings_save_error'), lang('settings_save_error_desc'));
		}

		$vars['base_url'] = $base_url;
		$vars['ajax_validate'] = TRUE;
		$vars['cp_page_title'] = lang('spam_settings');
		$vars['save_btn_text'] = 'btn_save_settings';
		$vars['save_btn_text_working'] = 'btn_saving';

		return ee('View')->make('spam:form')->render(array('data' => $vars));
	}

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
			call_user_func_array(array($class, $spam->method), $data);
		}

		ee('Alert')->makeInline('spam')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(sprintf(lang('spam_trap_approved'), count($trapped)))
			->defer();

		$this->moderate($trapped, FALSE);
		ee()->functions->redirect($this->base_url);
	}

	private function remove($trapped)
	{
		ee('Alert')->makeInline('spam')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(sprintf(lang('spam_trap_removed'), count($trapped)))
			->defer();

		$this->moderate($trapped);
		ee()->functions->redirect($this->base_url);
	}

	/**
	 * Moderate content. Will insert record into training table and either delete
	 * or reinsert the data if it's spam or ham respectively.
	 * 
	 * @param integer $id    ID of the content to moderate
	 * @param boolean $spam  True if content is spa,
	 * @access public
	 * @return void
	 */
	private function moderate($collection, $isSpam = TRUE)
	{
		$result = array();

		foreach ($collection as $spam)
		{
			$result[] = ee('Model')->make('spam:SpamTraining', array(
				'source' => $spam->document,
				'class' => $isSpam
			));
			$spam->delete();
		}

		$result = new CoreCollection($result);
		$this->trainParameters($result);
		$result->save();
	}

	/**
	 * Controller for running the testing
	 * 
	 * @access public
	 * @return void
	 */
	public function test()
	{
		$start_time = microtime(true);
		$limit = 1000;

		$documents = ee('Model')->get('SpamTraining')
						->limit($limit)
						->order('RAND()')
						->all();

		$data = array();
		$negatives = 0;
		$positives = 0;
		$total = $documents->count();

		foreach ($documents as $document)
		{
			$bayes = ee('spam:Core');
			$classification = (int) $bayes->classifier->classify($document->source, 'spam');

			if($classification > $document->class)
			{
				$positives++;
			}

			if($classification < $document->class)
			{
				$negatives++;
			}

			if($classification != $document->class)
			{
				 //ee()->db->delete('spam_training', array('training_id' => $document->training_id)); 
			}
		}
 
		$data['memory'] = memory_get_usage();
		$data['memory_per'] = $data['memory'] / $total;
		$data['accuracy'] = ($total - ($negatives + $positives)) / $total;
		$data['total'] = $total;
		$data['positives'] = $positives;
		$data['negatives'] = $negatives;
		$data['time'] = (microtime(true) - $start_time);
		$data['per'] = $data['time'] / $total;

		return ee()->load->view('test', $data, TRUE);
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
					$result = $result->filter($key, $filter);
				}
			}
		}

		if ( ! empty($search))
		{
			$result = $result->filter('document', 'LIKE', "%$search%");
		}

		if ( ! empty($sort))
		{
			$options = array(
				'content_type' => 'class',
				'spam_content' => 'document',
				'date' => 'date',
			);
			$result = $result->order($options[$sort], $direction);
		}

		if ( ! empty($limit))
		{
			$result = $result->limit($limit);
		}

		if ( ! empty($offset))
		{
			$result = $result->offset($offset);
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
					->filter('class', TRUE)
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
					->filter('class', FALSE)
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
	private function setMaximumLikelihood($training_collection, $kernel)
	{
		$training = array();

		foreach ($training_collection as $class => $sources)
		{
			$count = count($sources[0]);
			$zipped = array();
			$update = array();
			$insert = array();

			foreach ($sources as $key => $row)
			{
				for ($i = 0; $i < $count; $i++)
				{
					$zipped[$i][] = $row[$i];
				}
			}

			$query = ee()->db->where('class', $class == 1 ? 'y' : 'n')->get('spam_parameters');
			$parameters = array();

			foreach ($query->result() as $parameter)
			{
				$parameters[$parameter->index] = $parameter;
			}	

			foreach ($zipped as $index => $feature)
			{
				$count = 0;
				$mean = 0;
				$variance = 0;
				$new = TRUE;

				if ( ! empty($parameters[$index]))
				{
					$mean = $parameters[$index]->mean;
					$variance = $parameters[$index]->variance;
					$count = $kernel->count;
					$new = FALSE;
				}

				$updated = $this->onlineStatistics($count, $mean, $variance, $feature);

				$training = array(
					'kernel_id' => $kernel->kernel_id,
					'class' => $class == 1 ? 'y' : 'n',
					'index' => $index,
					'mean' => $updated['mean'],
					'variance' => $updated['variance']
				);

				if ($new)
				{
					$insert[] = $training;
				}
				else
				{
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
			ee()->db->update_batch('exp_spam_parameters', $update, 'index');
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
					'count' => $existing[$term]->count + $count,
					'kernel_id' => $kernel->kernel_id
				);
			}
			else
			{
				$insert[] = array(
					'term' => $term,
					'count' => $count,
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

		// Now grab the entire stored vocabulary
		$query = ee()->db->limit(ee()->config->item('spam_word_limit') ?: 5000)->get('spam_vocabulary');
		$vocabulary = array();

		foreach ($query->result() as $vocab)
		{
			$vocabulary[$vocab->term] = $vocab->count;
		}	

		$tfidf->vocabulary = $vocabulary;

		// Increment the total document count
		$kernel->count += count($data);
		$kernel->save();
		$tfidf->document_count = $kernel->count;
		$tfidf->generateLookups();

		// Calculate our new feature vectors
		$vectorizers = array();
		$vectorizers[] = $tfidf;
		$vectorizers[] = ee('spam:Vectorizers/ASCIIPrintable');
		$vectorizers[] = ee('spam:Vectorizers/Entropy');
		$vectorizers[] = ee('spam:Vectorizers/Links');
		$vectorizers[] = ee('spam:Vectorizers/Punctuation');
		$vectorizers[] = ee('spam:Vectorizers/Spaces');
		$training_collection = ee('spam:Collection', $vectorizers);
		$training_classes = array();

		foreach ($training_collection->fitTransform($documents) as $key => $vector)
		{
			$training_classes[$classes[$key]][] = $vector;
		}

		$this->setMaximumLikelihood($training_classes, $kernel);
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
	private function trainAll()
	{
		$start_time = microtime(true);
		$this->trainParameters(ee('Model')->get('spam:SpamTraining')->all());
		$time = (microtime(true) - $start_time);
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
		$variance = $variance * ($count - 1);

		foreach ($data as $datum)
		{
			$count++;
			$delta = $datum - $mean;
			$mean = $mean + ($delta / $count);
			$variance = $variance + $delta * ($datum - $mean);
		}

		$variance = $variance / ($count - 1);
		return array('mean' => $mean, 'variance' => $variance);
	}

}
