import ControlPanel from '../ControlPanel'

	class Entries extends ControlPanel {
		constructor() {
			super()

			this.urlMatcher = 'admin.php?/cp/publish/edit';

			this.elements({
			   "ChannelSort" : 'button[data-filter-label="channel"]',
			   "StatusSort" : 'button[data-filter-label="status"]',
			   "DateSort" : 'button[data-filter-label="date"]',
			   "SearchIn" : 'button[data-filter-label="search in"]',
			   "AuthorSort" : 'button[data-filter-label="author"]',
			   "ColumnsSort" : 'button[data-filter-label="columns"]',
			   		"Author" : 'input[value="author"]',
			   		"Id" : 'input[value="entry_id"]',

			   		"Date" : 'input[value="entry_date"]',
			   		"Status" : 'input[value="status"]',
			   		"Url" : 'input[value="url_title"]',

			   		"Expire" : 'input[value="expiration_date"]',
			   		"Channel" : 'input[value="channel"]',
			   		"Comments" : 'input[value="comments"]',
			   		"Category" : 'input[value="categories"]',
			   		"Title" : 'input[value="title"]',

			   "SearchBar" : 'input[name="filter_by_keyword"]',


			   "Clear" : 'a[class="filter-bar__button filter-bar__button--clear"]',
			   "NumberSort" : 'button[data-filter-label="show"]',
			   "Entries" : 'tbody',

			   "SelectAll" : 'input[title = "select all"]'





			})
		}

	}

export default Entries;