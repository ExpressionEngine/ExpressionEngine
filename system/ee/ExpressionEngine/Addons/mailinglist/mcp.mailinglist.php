<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use ExpressionEngine\Library\CP\Table;


class Mailinglist_mcp {

	var $perpage = 100;
	var $base_url;
	protected $breadcrumbs;
	
    protected $query_string;
    private $base = 'addons/settings/mailinglist';	
	

	/**
	  *  Constructor
	  */
	function __construct($switch = TRUE)
	{
		ee()->load->helper('form');
		$this->base_url = ee('CP/URL')->make('addons/settings/mailinglist');
		ee()->cp->set_right_nav(array(
			'ml_create_new' => $this->base_url . '/edit_mailing_list',
			'mailinglist_preferences' => $this->base_url . '/mailing_list_preferences'
		));
		
        $this->breadcrumbs = array(
            ee('CP/URL')->make('settings/addons/mailinglist')->compile() => lang('mailinglist')
        );		
		

	}


	// --------------------------------------------------------------------

	/**
	  *  Mailinglist Home Page
	  */
	function index()
	{
		ee()->load->library('javascript');
		ee()->load->helper('form');
		ee()->load->library('table');
		ee()->load->model('mailinglist_model');
		ee()->cp->add_js_script(array('fp_module' => 'mailinglist'));

		$vars['cp_page_title'] = ee()->lang->line('ml_mailinglist');
		//ee()->cp->set_breadcrumb('', 'mailinglist');
		
        ee()->view->cp_breadcrumbs = array(
            '' => lang('mailinglist')
        );

		ee()->load->model('mailinglist_model');

		$mailinglists = ee()->mailinglist_model->get_mailinglists();

		$vars['mailinglists'] = array();
		$vars['list_id_options'] = array();
		
	    // Define table
	    $table = ee('CP/Table', array(
	        'sortable' => true,
	        'search' => true,
	        'filter' => true,
	        'limit' => 10
	    ));
		
		$table->setColumns(
		  array(
		    'title' => array('type' => 'text', 'label' => lang('ml_mailinglist_title')),
		    'name' => array('type' => 'text', 'label' => lang('ml_mailinglist_name')),
		    'view_list' => array('type' => 'text', 'encode' => FALSE, 'label' => lang('ml_view_list')),
		    'edit_list' => array('type' => 'text', 'encode' => FALSE, 'label' => lang('ml_edit_list')),	
			'download_list' => array('type' => 'text', 'encode' => FALSE, 'label' => lang('ml_download_list')),		
		    'edit_template' => array('type' => 'text', 'encode' => FALSE, 'label' => lang('ml_edit_template')),
		    'total_emails' => array('type' => 'text', 'label' => lang('ml_total_emails')),				
		    array(
		      'type'  => Table::COL_CHECKBOX
		    )
		  )
		);
		
		$tableData = array();
		foreach ($mailinglists->result() as $list)
		{
            $checkbox = array(
                'name' => 'selection[]',
                'value' => $list->list_id,
                'data' => array(
                    'confirm' => lang('mailinglist') . ': <b>' . htmlentities($list->list_name, ENT_QUOTES, 'UTF-8') . '</b>'
                ));
				
				$view_list_link = ee('CP/URL')->make('addons/settings/mailinglist/view', array('list_id' => $list->list_id));
				$edit_list_link = ee('CP/URL')->make('addons/settings/mailinglist/edit_mailing_list', array('list_id' => $list->list_id));
				$template_link = ee('CP/URL')->make('addons/settings/mailinglist/edit_template',  array('list_id' => $list->list_id));	
				$download = ee('CP/URL')->make('addons/settings/mailinglist/export',  array('list_id' => $list->list_id));
				$count = ee()->mailinglist_model->mailinglist_count($list->list_id);

	
			
	        $tableData[] = array(
			'title' => $list->list_title,
			'name' => $list->list_name,
			'view_list' => '<a href="' . $view_list_link . '">' . lang('ml_view').'</a>',
			'edit_list' => '<a href="' . $edit_list_link . '">' . lang('edit').'</a>',
			'download_list' => '<a href="' . $download . '">' . lang('download').'</a>',
			'edit_template' => '<a href="' . $template_link . '">' . lang('ml_edit_template').'</a>',
			'count' => $count,
			$checkbox,
			);
																


		}
		
		
        $table->setData($tableData);

        $vars['table'] = $table->viewData($this->base_url);
        $vars['base_url'] = $this->base_url;
		$vars['subForm'] = $this->subscribe();

 
        ee()->javascript->set_global('lang.remove_confirm', lang('configurations') . ': <b>### ' . lang('configurations') . '</b>');
        ee()->cp->add_js_script(array(
            'file' => array('cp/confirm_remove'),
        ));

        return ee('View')->make('mailinglist:index')->render($vars);
    }
	
			//$table->setData($tableData);
		//$table->setNoResultsText('no_questions', 'create_question', ee('CP/URL', 'addons/settings/quiz/create'));
		
	    // Render table
	   // $data['table'] = $table->viewData(ee('CP/URL', 'addons/settings/mailinglist'));
		//$data['table']['heading'] = 'Questions';
		//$data['form_url'] = '';		
		//$data['base_url'] = '';	

	
		

	//	ee()->javascript->compile();
		


	//	return ee()->load->view('index', $data, TRUE);
	//}
	
	
	
	
    public function export()
    {
		
		$list_id = $list_id = ee()->input->get_post('list_id');
		
		if (! $list_id) { return; }
		
        $csv = ee('CSV');
		ee()->load->model('mailinglist_model');

        $emails = ee()->mailinglist_model->get_emails_by_list($list_id, 'email');
		
		$datum = array();

        foreach ($emails->result() as $email) {
            $datum = [
                lang('email') => $email->email
            ];
            $csv->addRow($datum);
        }


        ee()->load->helper('download');
        force_download('mailinglist_emails.csv', (string) $csv);
    }	
	
	
	
	function postMailinglist()
	{
		$list_id = ee()->input->get_post('list_id');
		ee()->load->model('mailinglist_model');
		
		$data = array(
						'list_name'		=> ee()->input->post('list_name'),
						'list_title'	=> ee()->input->post('list_title'),
						'list_template'	=> addslashes($this->default_template_data())
					);

		ee()->mailinglist_model->update_mailinglist($list_id, $data);

		$message = ($list_id == FALSE) ? ee()->lang->line('ml_mailinglist_created') : ee()->lang->line('ml_mailinglist_updated');

		ee()->session->set_flashdata('message_success', $message);
		
		ee()->functions->redirect(ee('CP/URL')->make('addons/settings/mailinglist'));

		
	}	
	
	
	
	
	

	// --------------------------------------------------------------------

	/**
	  *  Mailing List Default Template Data
	  */
	function default_template_data()
	{
return <<<EOF
{message_text}

To remove your email from the "{mailing_list}" mailing list, click here:
{if html_email}<a href="{unsubscribe_url}">{unsubscribe_url}</a>{/if}
{if plain_email}{unsubscribe_url}{/if}
EOF;
	}
	
	

	// --------------------------------------------------------------------

	/**
	  *  Create/Edit Mailing List
	  */
	function edit_mailing_list()
	{
		ee()->load->model('mailinglist_model');
		ee()->cp->set_breadcrumb('', 'mailinglist');
		
        ee()->view->cp_breadcrumbs = array(
            '' => lang('edit_mailinglist')
        );
		
		$list_id			= 0;
		$vars['list_name']	= '';
		$vars['list_title']	= '';


		if (ee()->input->get_post('list_id') != 0)
		{
			$query = ee()->mailinglist_model->get_list_by_id(
													ee()->input->get_post('list_id'),
													'list_title, list_template, list_id, list_name');

			if ($query->num_rows() == 1)
			{
				$list_id = $query->row('list_id');
				$vars['list_title'] = $query->row('list_title');
				$vars['list_name'] = $query->row('list_name');

				//ee()->form_validation->set_old_value('list_id', $list_id);
			}
		}



        $form['sections'] = array(
            'fields' => array(
                array(
                    'title' => 'list_title',
                    'fields' => array(
                        'list_title' => array(
                            'type' => 'text',
                            'value' => $vars['list_title'],
                            'required' => TRUE
                        )
                    )
                ),
                array(
                    'title' => 'list_name',
                    'fields' => array(
                        'list_name' => array(
                            'type' => 'text',
                            'value' => $vars['list_name'],
							'required' => TRUE
                        )
                    )
                ),
			)
		);

        $form += array(
			
            'base_url' => ee('CP/URL')->make('addons/settings/mailinglist/postMailinglist'),
            'cp_page_title' => lang('edit'),
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving'
        );

        return ee('View')->make('ee:_shared/form')->render($form);		

	}





	// --------------------------------------------------------------------

	/**
	  *  Mailing List Short Name Callback
	  */
	function _unique_short_name($str)
	{
		ee()->load->model('mailinglist_model');

		if ( ! ee()->mailinglist_model->unique_shortname(ee()->form_validation->old_value('list_id'), $str))
		{
			ee()->form_validation->set_message('_unique_short_name', ee()->lang->line('ml_short_name_taken'));
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	  *  Mailing List Template
	  */
	function edit_template()
	{
		ee()->load->helper('form');

		if ( ! $list_id = ee()->input->get_post('list_id'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		ee()->load->model('mailinglist_model');

		$list = ee()->mailinglist_model->get_list_by_id($list_id, 'list_title, list_template');

		if ($list->num_rows() == 0)
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		ee()->cp->set_breadcrumb($this->base_url, ee()->lang->line('ml_mailinglist'));

		$vars['cp_page_title'] = ee()->lang->line('mailinglist_template');
		$vars['form_hidden']['list_id'] = $list_id;
		$vars['list_title'] = $list->row('list_title');
		$vars['template_data'] = $list->row('list_template');

		return ee()->load->view('edit_template', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	  *  Update Mailing List Template
	  */
	function update_template()
	{
		if ( ! $list_id = ee()->input->get_post('list_id'))
		{
			show_error(ee()->lang->line('ml_no_list_id'));
		}

		if ( ! isset($_POST['template_data']))
		{
			return FALSE;
		}

		ee()->load->model('mailinglist_model');

		ee()->mailinglist_model->update_template($list_id, ee()->input->post('template_data'));

		ee()->session->set_flashdata('message_success', ee()->lang->line('template_updated'));
		ee()->functions->redirect($this->base_url);
	}

	// --------------------------------------------------------------------

	/**
	  *  Delete Mailing List Confirm
	  */
	function delete_mailinglist_confirm()
	{
		if ( ! ee()->input->post('toggle'))
		{
			ee()->functions->redirect($this->base_url);
		}

		ee()->cp->set_breadcrumb($this->base_url, ee()->lang->line('ml_mailinglist'));

		$vars['cp_page_title'] = ee()->lang->line('ml_delete_mailinglist');

		$vars['question_key'] = 'ml_delete_list_question';
		$vars['message'] = ee()->lang->line('ml_all_data_nuked'); // an extra warning message

		$vars['form_action'] = $this->base_url . '/delete_mailinglists';

		ee()->load->helper('form');

		foreach ($_POST['toggle'] as $key => $val)
		{
			$vars['damned'][] = $val;
		}

		ee()->load->model('mailinglist_model');

		$query = ee()->mailinglist_model->get_list_by_id($_POST['toggle'], 'list_title');

		$vars['list_names'] = array();

		foreach ($query->result() as $row)
		{
			$vars['list_names'][] = $row->list_title;
		}

		ee()->javascript->compile();

		return ee()->load->view('delete_confirm', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	  *  Delete Mailing List(s)
	  */
	function delete_mailinglists()
	{
		if (ee()->input->post('delete') == '')
		{
			ee()->functions->redirect($this->base_url);
		}

		ee()->load->model('mailinglist_model');

		$message = ee()->mailinglist_model->delete_mailinglist($_POST['delete']);

		ee()->session->set_flashdata('message_success', $message);
		ee()->functions->redirect($this->base_url);
	}



	function subscribe()
	{
		
		ee()->load->model('mailinglist_model');
		$mailinglists = ee()->mailinglist_model->get_mailinglists();
		
		$listOptions = array();

		
		foreach ($mailinglists->result() as $list) {
			$listOptions[$list->list_id] = $list->list_title;
		}	
		
		
		// Form definition array
		$vars['sections'] = array(
		  array(
		    array(
		      'title' => 'bulk_emails',
		      'fields' => array(
  		        'list_id' => array(
		          'type' => 'select',
		          'choices' => $listOptions,
				  'value' => '',
  		          'required' => TRUE
  		        ),
		        'addresses' => array(
		          'type' => 'textarea',
		          'value' => '',
				  'rows' => 20,
		          'required' => TRUE
		        )
		      )
		    )
		  )
		);

		// Final view variables we need to render the form
		$vars += array(
		  'base_url' => ee('CP/URL', 'addons/settings/mailinglist/postSubscribe'),
		  'cp_page_title' => FALSE,
		  'save_btn_text' => 'btn_save_settings',
		  'save_btn_text_working' => 'btn_saving'
		);
		
		
		return $vars;
		
		
		return ee('View')->make('ee:_shared/form')->render($vars);		
		
	}




	// --------------------------------------------------------------------

	/**
	  *  Subscribe
	  */
	function postSubscribe()
	{
		if (ee()->input->post('addresses') == '')
		{
			ee()->session->set_flashdata('message_failure', ee()->lang->line('ml_missing_email'));
			ee()->functions->redirect($this->base_url);
		}

		ee()->load->helper('email');

		//  Fetch existing addresses
		$subscribe = TRUE;

		$list_id = ee()->input->get_post('list_id');

		ee()->load->model('mailinglist_model');

		$query = ee()->mailinglist_model->get_emails_by_list($list_id, 'email');

		$current = array();

		if ($query->num_rows() == 0)
		{
			if ($subscribe == FALSE)
			{
				ee()->functions->redirect($this->base_url);
			}
		}
		else
		{
			foreach ($query->result() as $row)
			{
				$current[] = $row->email;
			}
		}

		//  Clean up submitted addresses
		$email	= trim($_POST['addresses']);
		$email	= preg_replace("/[,|\|]/", "", $email);
		$email	= str_replace(array("\r\n", "\r", "\n"), " ", $email);
		$email	= preg_replace("/\t+/", " ", $email);
		$email	= preg_replace("/\s+/", " ", $email);
		$emails	= array_unique(explode(" ", $email));

		//  Insert new addresses
		$vars['good_email'] = 0;
		$vars['dup_email']	= 0;

		$vars['bad_email']  = array();

		foreach($emails as $addr)
		{
			if (preg_match('/<(.*)>/', $addr, $match))
			{
				$addr = $match['1'];
			}

			if ($subscribe == TRUE)
			{
				if ( ! valid_email($addr))
				{
					$vars['bad_email'][] = $addr;
					continue;
				}

				if (in_array($addr, $current))
				{
					$vars['dup_email']++;
					continue;
				}

				$data = array(
								'list_id'		=> $list_id,
								'authcode'		=> random_string('alnum', 10),
								'email'			=> $addr,
								'ip_address'	=> ee()->input->ip_address()
							);

				ee()->mailinglist_model->insert_subscription($data);
			}
			else
			{
				ee()->mailinglist_model->delete_subscription($list_id, $addr);
			}

			$vars['good_email']++;
		}

		if (count($vars['bad_email']) == 0 AND $vars['dup_email'] == 0)
		{
			if ($subscribe == TRUE)
			{
				ee()->session->set_flashdata('message_success', ee()->lang->line('ml_emails_imported'));
				ee()->functions->redirect($this->base_url);
			}
			else
			{
				ee()->session->set_flashdata('message_success', ee()->lang->line('ml_emails_deleted'));
				ee()->functions->redirect($this->base_url);
			}
		}

		$vars['cp_page_title'] = ee()->lang->line('ml_batch_subscribe');
		ee()->cp->set_breadcrumb($this->base_url);

		$vars['notice'] = '';

		$vars['notice_import_del'] = ($subscribe == TRUE) ? 'ml_total_emails_imported' : 'ml_total_emails_deleted';

		if (count($vars['bad_email']) > 0)
		{
			sort($vars['bad_email']);

			$vars['notice_bad_email'] = ($subscribe == TRUE) ? 'ml_bad_email_heading' : 'ml_bad_email_del_heading';
		}

		return ee()->load->view('subscribe', $vars, TRUE);
	}

    private function createEmailFilter()
    {
        $status = ee('CP/Filter')->make('filter_by_email', 'filter_by_status', array(
            'o' => lang('open'),
            'c' => lang('closed'),
            'p' => lang('pending')
        ));
        $status->disableCustomValue();

        return $status;
    }


    protected function makeViewFilters()
    {
        $filters = ee('CP/Filter');

		ee()->load->model('mailinglist_model');
		$mailinglists = ee()->mailinglist_model->get_mailinglists();
		
		$listOptions = array();
		
		foreach ($mailinglists->result() as $list) {
			$listOptions[$list->list_id] = $list->list_title;
		}	

        $lists_filter = $filters->make('mailing_list', 'mailinglist_filter', $listOptions)
            ->setPlaceholder(lang('all'))
            ->disableCustomValue();

        $filters->add($lists_filter)
            ->add('Keyword');

        return $filters;
    }



	// --------------------------------------------------------------------

	/**
	  *  View Mailinglist
	  */
	function view()
	{


		ee()->load->library('table');
		$filters = $this->makeViewFilters();
		
	    // Define table 
	    $table = ee('CP/Table', array(
	        'sortable' => true,
	        'search' => true,
	        'filter' => true,
	        'limit' => 10
	    ));		
		
		$table->setColumns(
		  array(
		    'email' => array('type' => 'text', 'label' => lang('email')),
		    'ip_address' => array('type' => 'text', 'label' => lang('ip_address')),
		    'list_id' => array('type' => 'text', 'label' => lang('ml_mailinglist')),
		    'manage' => array(
		      'type'  => Table::COL_CHECKBOX
		    )
		  )
		);
		
		
		
		$total = 5;	
		ee()->db->select('list_id, list_title');
		$res = ee()->db->get('mailing_lists');

		$list_ops = array();

		foreach ($res->result_array() as $row)
		{
			$list_ops[$row['list_id']] = $row['list_title'];
		}		
		
		
        $lists = ee('CP/Filter')->make('list_id', 'lists', $list_ops);
        $lists->setPlaceholder(lang('all'));
        $lists->disableCustomValue();

        $filters = ee('CP/Filter')
            ->add($lists)
            ->add('Keyword')
            ->add('Perpage', $total, 'show_all');
		
        $search = ee()->input->get_post('filter_by_keyword');

        $data['filters'] = $filters->render($this->base_url = ee('CP/URL')->make('addons/settings/mailinglist/view'));
		
        $filter_values = $filters->values();
        $filter_fields = array();
        //$this->base_url->addQueryStringVariables($filter_values);
		

		
		
		$tableData = $this->mailinglistData($table->sort_col, $table->sort_dir, $filter_values, $list_ops);
		
	
		
		$data['cp_page_title'] = ee()->lang->line('ml_view_mailinglist');

	    $table->setData($tableData);
		
		
		//$values = $filters->values();
		//$keyword_value = $values['filter_by_keyword'];	
		

        //$data['filters'] = $filters->render($this->base_url);	
		
		

		
		
		
        $data['table'] = $table->viewData($this->base_url);
        $data['form_url'] = ee('CP/URL')->make('addons/settings/mailinglist');
        $data['cp_page_title'] = lang('lists');		
		
		
		
		
        if (! empty($data['table']['data'])) {
            $data['pagination'] = ee('CP/Pagination', $total)
                ->perPage($table->config['limit'])
                ->currentPage($table->config['page'])
                ->render($this->base_url);
        }

		
		ee()->cp->add_js_script(array(
		  'file' => array('cp/confirm_remove'),
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
            'body' => ee('View')->make('mailinglist:view')->render($data),
           'heading' => lang('viewstiff'),
           'breadcrumb' => array(
               ee('CP/URL')->make('addons/settings/mailinglist')->compile() => lang('mailinglist'),
			   '' => lang('view')
           ),
        );
		
		}	




	function mailinglistData($sort_col, $sort_dir, $filter_values, $mailing_lists)
	{
		ee()->load->model('mailinglist_model');
		
		$tableData = array();
		$list_id = '';
		$email = '';
		$order = '';
		$rownum = '';
		$perpage = '';

		if (isset($filter_values['list_id'])) {
			$list_id = $filter_values['list_id'];
			ee()->db->where('list_id', $filter_values['list_id']);
			
		}
		if (isset($filter_values['filter_by_keyword'])) {
			$email = $filter_values['filter_by_keyword'];
			ee()->db->like('email', $filter_values['filter_by_keyword']);
		}
		if (isset($filter_values['perpage'])) {
			$perpage = $filter_values['perpage'];
			
		}	
		
        $page = ((int) ee()->input->get('page')) ?: 1;
		$total = ee()->db->count_all_results('mailing_list');	
		

		$lists = array();
		
		foreach ($mailing_lists as $k => $v)
		{
			$lists[$k] = htmlentities($v, ENT_QUOTES, 'UTF-8');
		}

		//$mailing_q = ee()->mailinglist_model->mailinglist_details(
		//	$list_id
		//);
		
		$mailing_q = ee()->mailinglist_model->mailinglist_search($list_id, $email, $order, $page, $perpage);

				
	    $tableData_q = array();
		
		if ($mailing_q->num_rows() == 0) { return $tableData; }
		
	    foreach ($mailing_q->result() as $subs) {
			
			$column = array(
			  'name' => 'selection[]',
			  'value' => $subs->user_id,
			  'data'  => array(
			    'confirm' => lang('content') . ': <b>' . htmlentities($subs->email, ENT_QUOTES, 'UTF-8') . 'in ' . $lists[$subs->list_id] . '</b>'
			  )
			);			
			
			
	        $tableData[] = array(
	            'email' => $subs->email,
	            'ip_address' => $subs->ip_address,
	            'list_id' => $lists[$subs->list_id],
	            'checkbox' => $column
	        );
	    }
		return $tableData;
	}
		


	// ------------------------------------------------------------------------

	/**
	 * View Ajax Filter
	 */
	function _mailinglist_filter($state, $params)
	{
		ee()->load->model('mailinglist_model');

		$email		= ee()->input->get_post('email');
		$list_id	= ee()->input->get_post('list_id');

		ee()->db->select('list_id, list_title');
		$res = ee()->db->get('mailing_lists');

		$lists = array();

		foreach ($res->result_array() as $row)
		{
			$lists[$row['list_id']] = $row['list_title'];
		}

		if ($list_id != '')
		{
			ee()->db->where('list_id', $list_id);
			$total = ee()->db->count_all_results('mailing_list');
		}
		else
		{
			$total = ee()->db->count_all('mailing_list');
		}

		$mailing_q = ee()->mailinglist_model->mailinglist_search(
			$list_id, $email, $state['sort'], $state['offset'], $params['perpage']
		);

	$mailing_q->result_array();
		
		
		
	    $tableData = array();
	    foreach ($mailing_q as $subs) {
	        $tableData[] = array(
	            'email' => $subs->email,
	            'ip_address' => $subs->ip_address,
	            'list_id' => $subs->question_description,
	            'checkbox' => array(
	                'name' => 'toggle[]',
	                'value' => $subs->user_id
	            )
	        );
	    }
		return $tableData;

	}

	// --------------------------------------------------------------------

	/**
	  *  Delete Emails - Confirm
	  */
	function delete_confirm()
	{
		if ( ! ee()->input->post('toggle'))
		{
			ee()->functions->redirect($this->base_url . '/view');
		}

		ee()->cp->set_breadcrumb($this->base_url);

		$vars['cp_page_title'] = ee()->lang->line('ml_delete_confirm');
		$vars['question_key'] = 'ml_delete_question';
		$vars['form_action'] = $this->base_url . '/delete_email_addresses';

		ee()->load->helper('form');

		foreach ($_POST['toggle'] as $key => $val)
		{
			$vars['damned'][] = $val;
		}

		ee()->javascript->compile();

		return ee()->load->view('delete_confirm', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	  *  Delete Email Addresses
	  */
	function delete_email_addresses()
	{
		if (ee()->input->post('delete') == '')
		{
			ee()->functions->redirect($this->base_url . '/view');
		}

		ee()->load->model('mailinglist_model');
		$message = ee()->mailinglist_model->delete_email($_POST['delete']);

		ee()->session->set_flashdata('message_success', $message);
		ee()->functions->redirect($this->base_url);
	}
	
	
	// --------------------------------------------------------------------

	/**
	  *  Delete Specific email/list users
	  */
	function delete_user()
	{
		
        $user_ids = ee('Security/XSS')->clean(ee('Request')->post('selection'));

        if (! empty($user_ids)) {
			
			ee()->load->model('mailinglist_model');
			ee()->mailinglist_model->delete_email($user_ids);
			
			
			$removed = 'emails removed';

            ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('subscribers_removed'))
                    ->addToBody(lang('remove_success_desc'))
                    ->addToBody($removed)
                    ->defer();

        }
		
		ee()->functions->redirect($this->base_url);

		//ee()->session->set_flashdata('message_success', $message);
		//ee()->functions->redirect($this->base_url);
	}	
	
	
	
}
// END CLASS

/* End of file mcp.mailinglist.php */
/* Location: ./system/expressionengine/modules/mailinglist/mcp.mailinglist.php */