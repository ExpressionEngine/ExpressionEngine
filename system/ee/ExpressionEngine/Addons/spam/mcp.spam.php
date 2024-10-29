<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Library\Data\Collection as CoreCollection;
use ExpressionEngine\Addons\Spam\Service\SpamModerationInterface;

/**
 * Spam Module control panel
 */
class Spam_mcp
{
    public $stop_words_path = "spam/training/stopwords.txt";
    public $stop_words = array();
    private $base_url;
    protected $total;

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
        if (AJAX_REQUEST && ! empty($method)) {
            return $this->$method();
        }

        if (! empty($_POST['bulk_action'])) {
            $action = ee()->input->post('bulk_action');
            $selection = ee('Model')->get('spam:SpamTrap', ee()->input->post('selection'))->all();

            if ($action == 'approve') {
                $this->approve($selection);
            }

            if ($action == 'remove') {
                $this->remove($selection);
            }
        }

        $search = ee()->input->get_post('filter_by_keyword');

        $table = ee('CP/Table', array('search' => $search, 'sort_col' => 'trap_date', 'sort_dir' => 'desc'));

        $data = array();

        $total = ee('Model')->get('spam:SpamTrap')->count();

        $content_types = $this->getContentTypes();
        $types = ee('CP/Filter')->make('content_type', 'content_type', $content_types);
        $types->setPlaceholder(lang('all'));
        $types->disableCustomValue();

        $filters = ee('CP/Filter')
            ->add($types)
            ->add('Date', 'trap_date')
            ->add('Keyword')
            ->add('Perpage', $total, 'show_all_spam');

        $data['filters'] = $filters->render($this->base_url);

        $filter_values = $filters->values();
        $filter_fields = array();
        $this->base_url->addQueryStringVariables($filter_values);

        if (! empty($filter_values['content_type'])) {
            $filter_fields['content_type'] = $filter_values['content_type'];
        }

        if (! empty($filter_values['filter_by_date'])) {
            $filter_fields['trap_date'] = $filter_values['filter_by_date'];
        }

        $table->setColumns(
            array(
                'spam_content' => array(
                    'encode' => false
                ),
                'trap_date',
                'ip',
                'spam_type',
                'manage' => array(
                    'type' => Table::COL_TOOLBAR
                ),
                array(
                    'type' => Table::COL_CHECKBOX
                )
            )
        );

        $trap = $this->getSpamTrap($filter_fields, $table->sort_col, $table->sort_dir, $search, $filter_values['perpage'], ($table->config['page'] - 1) * $filter_values['perpage']);

        $trapped = array();

        foreach ($trap as $spam) {
            $toolbar = array('toolbar_items' => array(
                'view' => array(
                    'href' => '#',
                    'class' => 'spam-detail',
                    'rel' => 'spam-modal',
                    'title' => strtolower(lang('edit')),
                    'data-content' => htmlentities(nl2br($spam->document), ENT_QUOTES, 'UTF-8'),
                    'data-type' => htmlentities($spam->content_type, ENT_QUOTES, 'UTF-8'),
                    'data-date' => ee()->localize->human_time($spam->trap_date->getTimestamp()),
                    'data-ip' => htmlentities($spam->ip_address, ENT_QUOTES, 'UTF-8'),
                )
            ));

            if ($spam->author_id != 0 && is_object($spam->Author)) {
                $author = $spam->Author->getMemberName();
            } else {
                $author = lang('guest');
            }

            $summary = substr($spam->document, 0, 60) . '...';
            $title = htmlentities($summary, ENT_QUOTES, 'UTF-8');
            $title .= '<br><span class="meta-info">&mdash; ' . lang('by') . ': ' . htmlentities($author, ENT_QUOTES, 'UTF-8') . '</span>';

            $trapped[] = array(
                'content' => $title,
                'date' => ee()->localize->human_time($spam->trap_date->getTimestamp()),
                'ip' => $spam->ip_address,
                'type' => $content_types[$spam->content_type],
                $toolbar,
                array(
                    'name' => 'selection[]',
                    'value' => $spam->trap_id,
                    'data' => array(
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
        if (! empty($data['table']['search'])) {
            $data['cp_heading'] = sprintf(
                lang('search_results_heading'),
                $this->total,
                $data['table']['search']
            );
        }

        if (! empty($data['table']['data'])) {
            $data['pagination'] = ee('CP/Pagination', $total)
                ->perPage($table->config['limit'])
                ->currentPage($table->config['page'])
                ->render($this->base_url);
        }

        ee()->cp->add_js_script(array(
            'file' => array('cp/addons/spam'),
        ));

        ee()->view->header = array(
            'title' => lang('all_spam'),
            'toolbar_items' => array(
                'settings' => array(
                    'href' => ee('CP/URL', 'addons/settings/spam/settings'),
                    'title' => lang('settings')
                )
            )
        );

        return array(
            'body' => ee('View')->make('spam:index')->render($data),
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

        $sensitivity = ee()->input->post('spam_sensitivity') ?: $settings['sensitivity'];

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
                            'content' => "<a class='button button--secondary tn update' href='" . ee('CP/URL', 'addons/settings/spam/') . "'>" . lang('update_training') . "</a>"
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
                'field' => 'spam_sensitivity',
                'label' => 'lang:spam_sensitivity',
                'rules' => 'required|numeric'
            ),
            array(
                'field' => 'spam_word_limit',
                'label' => 'lang:spam_word_limit',
                'rules' => 'required|is_natural_no_zero'
            ),
            array(
                'field' => 'spam_content_limit',
                'label' => 'lang:spam_content_limit',
                'rules' => 'required|is_natural_no_zero'
            )
        ));

        if (AJAX_REQUEST) {
            ee()->form_validation->run_ajax();
            exit;
        } elseif (ee()->form_validation->run() !== false) {
            $fields = array();

            // Make sure we're getting only the fields we asked for
            foreach ($vars['sections'] as $settings) {
                foreach ($settings as $setting) {
                    foreach ($setting['fields'] as $field_name => $field) {
                        $fields[$field_name] = ee()->input->post($field_name);
                    }
                }
            }

            $config_update = ee()->config->update_site_prefs($fields);

            if (! empty($config_update)) {
                ee()->load->helper('html_helper');
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('cp_message_issue'))
                    ->addToBody($config_update)
                    ->defer();
            } else {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('success'))
                    ->addToBody(lang('spam_settings_updated'))
                    ->defer();

                // Delete the classifier from shared memory if our settings changed
                ee('spam:Training', 'default')->deleteClassifier();
            }

            ee()->functions->redirect($base_url);
        } elseif (ee()->form_validation->errors_exist()) {
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
        $vars['ajax_validate'] = true;
        $vars['cp_page_title'] = '';
        $vars['hide_top_buttons'] = true;
        $vars['save_btn_text'] = 'btn_save_settings';
        $vars['save_btn_text_working'] = 'btn_saving';

        $download_ajax_fail = ee('CP/Alert')->makeBanner('reorder-ajax-fail')
            ->asIssue()
            ->canClose()
            ->withTitle(lang('training_update_failed'))
            ->addToBody('%s');

        ee()->javascript->set_global('alert.download_ajax_fail', $download_ajax_fail->render());

        return array(
            'body' => ee('View')->make('spam:form')->render(array('data' => $vars)),
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
        $exceptions = [];

        foreach ($trapped as $spam) {
            $addon = ee('Addon')->get($spam->content_type);

            if (! $addon or ! $addon->hasSpam()) {
                continue;
            }

            $fqcn = $addon->getSpamClass();

            try {
                $approver = new $fqcn();

                if (! $approver instanceof SpamModerationInterface) {
                    throw new \Exception('Skipped approval action: ' . get_class($approver) . ' must implement <code>SpamModerationInterface</code>');
                }

                $approver->approve($spam->entity, $spam->optional_data);
            } catch (\Exception $e) {
                $exceptions[] = $this->prepErrorExceptionMessage($e);
            }
        }

        $alert = ee('CP/Alert')->makeInline('spam')
            ->asSuccess()
            ->withTitle(lang('success'))
            ->addToBody(sprintf(lang('spam_trap_approved'), count($trapped)));

        if (! empty($exceptions)) {
            $except = ee('CP/Alert')->makeInline('spam_errors')
                ->asWarning()
                ->addToBody($exceptions);

            $alert->setSubAlert($except);
        }

        $alert->defer();

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
        $exceptions = [];

        foreach ($trapped as $spam) {
            $addon = ee('Addon')->get($spam->content_type);

            if (! $addon or ! $addon->hasSpam()) {
                continue;
            }

            $fqcn = $addon->getSpamClass();

            try {
                $rejecter = new $fqcn();

                if (! $rejecter instanceof SpamModerationInterface) {
                    throw new \Exception('Skipped reject action: ' . get_class($rejecter) . ' must implement <code>SpamModerationInterface</code>');
                }

                $rejecter->reject($spam->entity, $spam->optional_data);
            } catch (\Exception $e) {
                $exceptions[] = $this->prepErrorExceptionMessage($e);
            }
        }

        $alert = ee('CP/Alert')->makeInline('spam')
            ->asSuccess()
            ->withTitle(lang('success'))
            ->addToBody(sprintf(lang('spam_trap_removed'), count($trapped)));

        if (! empty($exceptions)) {
            $except = ee('CP/Alert')->makeInline('spam_errors')
                ->asWarning()
                ->addToBody($exceptions);

            $alert->setSubAlert($except);
        }

        $alert->defer();

        $this->moderate($trapped, 'spam');
        ee()->functions->redirect($this->base_url);
    }

    /**
     * Prepare message from an Exception
     * @param  \Exception $e Exception object
     * @return string Compiled error message
     */
    private function prepErrorExceptionMessage(\Exception $e)
    {
        $message = str_replace("\\", "/", $e->getMessage());
        $message = str_replace(SYSPATH, '', $message);
        $file = str_replace("\\", "/", $e->getFile());
        $file = str_replace(SYSPATH, '', $file) . ':' . $e->getLine();

        return $message . ' ' . lang('in') . ' ' . $file;
    }

    public function download()
    {
        if (! AJAX_REQUEST) {
            show_error(lang('unauthorized_access'), 403);
        }

        try {
            ee('spam:Update')->download();
        } catch (\Exception $error) {
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
        if (! AJAX_REQUEST) {
            show_error(lang('unauthorized_access'), 403);
        }

        try {
            ee('spam:Update')->prepare();
        } catch (\Exception $error) {
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
        if (! AJAX_REQUEST) {
            show_error(lang('unauthorized_access'), 403);
        }

        try {
            $processing = ee('spam:Update')->updateParameters();
        } catch (\Exception $error) {
            ee()->output->send_ajax_response(array(
                'error' => $error->getMessage()
            ));
        }

        if ($processing === true) {
            $status = 'processing';
        } else {
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
        if (! AJAX_REQUEST) {
            show_error(lang('unauthorized_access'), 403);
        }

        try {
            $processing = ee('spam:Update')->updateVocabulary();
        } catch (\Exception $error) {
            ee()->output->send_ajax_response(array(
                'error' => $error->getMessage()
            ));
        }

        if ($processing === true) {
            $status = 'proccessing';
        } else {
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

        foreach ($collection as $spam) {
            $trained = ee('Model')->make('spam:SpamTraining', array(
                'source' => $spam->document,
                'class' => $class
            ));
            $trained->Kernel = $this->getKernel();
            $trained->Author = ee()->session->getMember();
            $result[] = $trained;
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
     * @return object The kernel model
     */
    private function getKernel($name = 'default')
    {
        return ee('Model')->get('spam:SpamKernel')->filter('name', $name)->first(true);
    }

    private function getContentTypes()
    {
        $content_types = array();

        // Query Builder instead of model here as we need aggregation for simplicity and performance
        $query = ee()->db->select('DISTINCT(content_type)')->get('spam_trap');

        foreach ($query->result() as $row) {
            ee()->lang->load($row->content_type);
            $content_types[$row->content_type] = lang($row->content_type);
        }

        return $content_types;
    }

    /**
     * Returns an array of content flagged as spam
     *
     * @param  integer $limit The number of entries to grab
     * @access private
     * @return array   Array of content to moderate
     */
    private function getSpamTrap($filters = array(), $sort = null, $direction = 'asc', $search = null, $limit = 1000, $offset = 0)
    {
        $result = ee('Model')->get('spam:SpamTrap');

        if (! empty($filters)) {
            foreach ($filters as $key => $filter) {
                if (! empty($filter)) {
                    if ($key == 'trap_date') {
                        if (is_array($filter)) {
                            $result->filter('trap_date', '>=', $filter[0]);
                            $result->filter('trap_date', '<', $filter[1]);
                        } else {
                            $result->filter('trap_date', '>=', ee()->localize->now - $filter);
                        }
                    } else {
                        $result->filter($key, $filter);
                    }
                }
            }
        }

        if (! empty($search)) {
            $result->filter('document', 'LIKE', "%$search%");
        }

        if (! empty($sort)) {
            $options = array(
                'content_type' => 'class',
                'spam_content' => 'document',
                'trap_date' => 'trap_date',
            );
            $result->order($options[$sort], $direction);
        }

        $this->total = $result->count();

        if (! empty($limit)) {
            $result->limit($limit);
        }

        if (! empty($offset)) {
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
     *                $class => array(
     *                    array($feature0, $feauture1, ...),
     *                      ...
     *                )
     * @param object $kernel
     * @access private
     * @return void
     */
    private function setMaximumLikelihood($classes, $collection, $existing, $kernel)
    {
        $training = array();
        $update = array();
        $insert = array();

        foreach ($classes as $class => $sources) {
            $count = count($sources[0]);
            $zipped = array();

            foreach ($sources as $key => $row) {
                for ($i = 0; $i < $count; $i++) {
                    $zipped[$i][] = $row[$i];
                }
            }

            $query = ee()->db->where('class', $class)->get('spam_parameters');
            $parameters = array();

            foreach ($query->result() as $parameter) {
                $parameters[$parameter->index] = $parameter;
            }

            $lookup = array();

            foreach ($collection->vectorizers as $i => $vectorizer) {
                if ($vectorizer instanceof ExpressionEngine\Addons\Spam\Library\Vectorizers\Tfidf) {
                    $vocab = array_keys($vectorizer->vocabulary);

                    for ($j = 0; $j < count($vocab); $j++) {
                        $lookup[$i + $j] = array('term' => $existing[$vocab[$j]]);
                    }
                } else {
                    $lookup[$i] = array('index' => $i);
                }
            }

            foreach ($zipped as $index => $feature) {
                $count = 0;
                $mean = 0;
                $variance = 0;
                $new = true;
                $id = null;

                if (! empty($parameters[$index])) {
                    $id = $parameters[$index]->parameter_id;
                    $mean = $parameters[$index]->mean;
                    $variance = $parameters[$index]->variance;
                    $count = $kernel->count;
                    $new = false;
                }

                $updated = $this->onlineStatistics($count, $mean, $variance, $feature);

                $training = array(
                    'kernel_id' => $kernel->kernel_id,
                    'class' => $class,
                    'mean' => $updated['mean'],
                    'variance' => $updated['variance'],
                    'index' => null,
                    'term' => null,
                );

                if (empty($lookup[$index]['term'])) {
                    $training['index'] = $lookup[$index]['index'];
                } else {
                    $training['term'] = (int) $lookup[$index]['term']->vocabulary_id;
                }

                if ($new) {
                    $insert[] = $training;
                } else {
                    $training['parameter_id'] = $id;
                    $update[] = $training;
                }
            }
        }

        if (! empty($insert)) {
            ee()->db->insert_batch('exp_spam_parameters', $insert);
        }

        if (! empty($update)) {
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

        $stopwords = explode("\n", ee()->lang->load('spam/stopwords', null, true, false));
        $tokenizer = ee('spam:Tokenizer');
        $tfidf = ee('spam:Vectorizers/Tfidf', $documents, $tokenizer, $stopwords);

        $kernel = $this->getKernel('default');
        $insert = array();
        $update = array();

        ee()->db->where_in('term', array_keys($tfidf->vocabulary));
        $query = ee()->db->get('spam_vocabulary');

        foreach ($query->result() as $term) {
            $existing[$term->term] = $term;
        }

        foreach ($tfidf->vocabulary as $term => $count) {
            if (! empty($existing[$term])) {
                $update[] = array(
                    'term' => $term,
                    'count' => (int) $existing[$term]->count + (int) $count,
                    'kernel_id' => $kernel->kernel_id
                );
            } else {
                $insert[] = array(
                    'term' => $term,
                    'count' => (int) $count,
                    'kernel_id' => $kernel->kernel_id
                );
            }
        }

        if (! empty($insert)) {
            ee()->db->insert_batch('exp_spam_vocabulary', $insert);
        }

        if (! empty($update)) {
            ee()->db->update_batch('exp_spam_vocabulary', $update, 'term');
        }

        // Add all our new vocab ids to the existing array for later use
        ee()->db->where_in('term', array_keys($tfidf->vocabulary));
        $query = ee()->db->get('spam_vocabulary');

        foreach ($query->result() as $term) {
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

        foreach ($training_collection->fitTransform($documents) as $key => $vector) {
            $training_classes[$classes[$key]][] = $vector;
        }

        $this->setMaximumLikelihood($training_classes, $training_collection, $existing, $kernel);
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
        ini_set('memory_limit', '64G');
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
     * @return array
     */
    private function onlineStatistics($count = 0, $mean = 0, $variance = 0, $data = array())
    {
        foreach ($data as $datum) {
            $count++;
            $delta = $datum - $mean;
            $mean = $mean + ($delta / $count);
            $variance = $variance + $delta * ($datum - $mean);
        }

        if ($count > 1) {
            $variance = $variance / ($count - 1);
        }

        return array('mean' => $mean, 'variance' => $variance);
    }
}

// EOF
