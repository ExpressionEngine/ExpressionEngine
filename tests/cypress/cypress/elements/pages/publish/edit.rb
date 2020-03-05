require_relative 'publish.rb'

class Edit < Publish
  set_url '/admin.php?/cp/publish/edit/entry/{entry_id}'
end
