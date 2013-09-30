<?PHP

class EditMultipleEntriesTransaction extends Transaction {
	public $entry_ids = NULL;

	public function step()
	{
		if (($entry_ids = ee()->input->post('toggle'))) 
		{
			return $this->form(
				ee()->query_builder->get('ChannelEntries', $entry_ids)
			);
				
		}
		elseif (($entry_data = ee()->input->post('entries')))
		{
			return $this->process($entry_data);
		}
		elseif ($this->entry_ids !== NULL)
		{
			return $this->form(
				ee()->query_builder->get('ChannelEntries', $this->entry_ids)
			);	
		}
		throw new TransactionException('Unknown state!');
	}

	protected function form(array $entries, array $errors = array())
	{
		$entry_views = array();
		foreach ($this->entries as $entry)
		{
			$entry_views[] = ee()->view->make(
				'Edit/Multiple/Entry', 
				array(
					'entry'  => $entry, 
					'errors' => (isset($errors[$entry->entry_id]) ? $errors[$entry->entry_id] : NULL)
				)
			);
		}
		return ee()->view->make('Edit/Multiple/Form', array('views'=>$entry_views));
	}

	protected function process($entry_data)
	{
		$ids = array_keys($entry_data);
		
		$query = ee()->query_builder->get('ChannelEntry')
			->with('Channel', array('Member'=>'MemberGroup'), array('Category'='CategoryGroup'))
			->where('ChannelEntry.entry_id IN :entry_ids', array('entry_ids'=>$ids))
			->orderBy('entry_id');
		$entries = $query->run();

		foreach($entry_data as $id => $entry)
		{
			foreach($entry as $property=>$value)
			{
				$entries[$id]->{$property} = $value;					
			}
		}

		$validation_errors = array();
		foreach($entries as $entry)
		{
			$errors = $entry->validate();
			if ($errors->hasErrors())
			{
				$validation_errors[$entry->entry_id] = $errors;	
			}
		}

		if ( ! empty($validation_errors))
		{
			$this->entries = $entries;
			return $this->form();
		}

		foreach ($entries as $entry)
		{
			$entry->save();
		}
		return ee()->view->make('Muliple/Edit/Success', $entries);
	}

}
