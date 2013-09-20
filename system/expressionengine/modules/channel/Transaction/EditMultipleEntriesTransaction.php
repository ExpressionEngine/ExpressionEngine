<?PHP

class EditMultipleEntriesTransaction extends Transaction {
	protected $entries = array();

	public function setEntries(array $entries)
	{
		$this->entries = $entries;
		return $this;
	}

	public function handleStep() 
	{

	}

	protected function form(array $errors = array())
	{
		$entry_views = array();
		foreach ($this->entries as $entry)
		{
			$entry_views[] = ee()->view->make('Edit/Multiple/Entry', 
								array('entry'=>$entry, 'errors'=>(isset($errors[$entry->entry_id]) ? $errors[$entry->entry_id] : NULL)));
		}
		return ee()->view->make('Edit/Multiple/Form', array('views'=>$entry_views));
	}

	protected function process()
	{
		$entry_data = ee()->input->post('entries');
		$ids = array_keys($entry_data);
		
		$query = ee()->query_builder->get('ChannelEntry')
						->with('Channel', array('Member'=>'MemberGroup'), array('Category'='CategoryGroup'))
						->where('ChannelEntry.entry_id IN :entry_ids', array('entry_ids'=>$ids))
						->orderBy('entry_id');
		$entries = $query->execute();

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
