require_relative './logs.rb'

class CpLog < Logs
  set_url_matcher /logs\/cp/

  def initialize
    @menu_item = 'Control Panel'
  end

  def generate_data(
    count: 250,
    site_id: nil,
    member_id: nil,
    username: nil,
    ip_address: nil,
    timestamp_min: nil,
    timestamp_max: nil,
    action: nil
    )
    command = "cd fixtures && php cpLog.php"

    if count
      command += " --count " + count.to_s
    end

    if site_id
      command += " --site-id " + site_id.to_s
    end

    if member_id
      command += " --member-id " + member_id.to_s
    end

    if username
      command += " --username '" + username.to_s + "'"
    end

    if ip_address
      command += " --ip-address '" + ip_address.to_s + "'"
    end

    if timestamp_min
      command += " --timestamp-min " + timestamp_min.to_s
    end

    if timestamp_max
      command += " --timestamp-max " + timestamp_max.to_s
    end

    if action
      command += " --action '" + action.to_s + "'"
    end

    command += " > /dev/null 2>&1"

    system(command)
  end
end
