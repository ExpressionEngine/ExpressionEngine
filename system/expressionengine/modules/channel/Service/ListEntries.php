<?PHP

class ListEntries extends ListService {

	/**
	 * Page all Available Entries
	 *
	 * Provides a paged view of all available entries.  Does not allow
	 * for filtering of the entries.
	 *
	 * @return	View	A populated view object with the correct page of
	 * 					entries.
	 */
	public function pageAll()
	{
		$page = ee()->input->get_post('page');
		$query = ee()->query_builder->get('ChannelEntry')
						->with('Channel', array('Member'=>'MemberGroup'), array('Category'=>'CategoryGroup'))
						->limit($this->getItemsPerPage())
						->offset($this->getOffsetForPage($page));	
		$entries = $query->execute();
		return $this->getEntryListView($entries);
	}

	/**
	 *
	 */
	public function pageFiltered()
	{
		$page = ee()->input->get_post('page');
		$query = ee()->query_builder->get('ChannelEntry')
						->with('Channel', array('Member'=>'MemberGroup'), array('Category'=>'CategoryGroup'))
						->limit($this->getItemsPerPage())
						->offset($this->getOffsetForPage($page));	
		
		if (($channel_id = ee()->input->get_post('channel_id')) !== NULL)
		{
			$query->where('ChannelEntry.channel_id == :channel_id', array('channel_id'=>$channel_id));
		}

		if (($cat_id = ee()->input->get_post('cat_id')) !== NULL)
		{
			$query->where('Category.cat_id == :cat_id', array('cat_id'=>$cat_id));
		}
		
		$entries = $query->execute();
		return $this->getEntryListView($entries);		
	}

	/**
	 *
	 */
	private function getEntryListView(array $entries)
	{
		foreach($entries as $entry)
		{
			$rows[] = ee()->view->make('EntriesList/Row', $entry);
		}
		
		return ee()->view->make('EntriesList/List', $rows);
	}
	
}
