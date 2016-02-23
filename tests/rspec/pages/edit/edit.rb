class Edit < ControlPanelPage
  set_url '/system/index.php?/cp/publish/edit{&perpage}'

  elements :entry_rows, '.w-16 .tbl-ctrls form table tbody tr'
  elements :entry_checkboxes, '.w-16 .tbl-ctrls form table tbody tr input[type="checkbox"]'

  # Create a number of entries
  #
  # @param [Number] n = 10 Set a specific number of entries to create, defaults
  #   to 10
  # @return [void]
  def create_entries(n = 10, channel = 1)
    command = "cd fixtures && ruby entries.rb\
      --db-name #{$test_config[:db_name]}\
      --db-username #{$test_config[:db_username]}\
      --number #{n}"

    if $test_config[:db_password].empty?
      command += "--db-password #{$test_config[:db_password]}"
    end

    command += " #{channel}"

    system(command)
  end
end
