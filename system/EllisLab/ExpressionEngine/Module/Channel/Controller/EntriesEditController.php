<?PHP

class EntriesEditController extends CI_Controller
{

	public function edit_entries()
	{
		if ( ! ee()->input->have_post())
		{
			$service = new EntriesListService();
			return $service->pageFiltered();
		}		

		$action = ee()->input->post('action');
		switch($action)
		{
			case 'edit':
				$transaction = new MultipleEntriesEditTransaction();
				break;
			case 'delete':
				$this->delete_entries();
				break;
			case 'add_categories':
				$transaction = new AddCategoriesTransaction();
				break;
			case 'remove_categories':
				$transaction = new RemoveCategoriesTransaction();
				break;
		}
		return $transaction->handleStep();
	}

	protected function delete_entries()
	{
		$entries = ee()->input->post('toggle');
		foreach($entries as $entry_id)
		{
			$entry = new ChannelEntry();
			$entry->entry_id = $entry_id;
			$entry->delete();
		}

		// Alternative
		$query = ee()->query_builder->delete('ChannelEntry', $entries);
		$query->execute();
	}

}
