require_relative 'publish.rb'

class Edit < Publish
  set_url '/system/index.php?/cp/publish/edit/entry/{entry_id}'
end
