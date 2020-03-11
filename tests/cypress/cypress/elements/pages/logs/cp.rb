require_relative './logs.rb'

class CpLog < Logs
  set_url_matcher /logs\/cp/

  initialize
    @menu_item = 'Control Panel'
  }

  generate_data(
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
    }

    if site_id
      command += " --site-id " + site_id.to_s
    }

    if member_id
      command += " --member-id " + member_id.to_s
    }

    if username
      command += " --username '" + username.to_s + "'"
    }

    if ip_address
      command += " --ip-address '" + ip_address.to_s + "'"
    }

    if timestamp_min
      command += " --timestamp-min " + timestamp_min.to_s
    }

    if timestamp_max
      command += " --timestamp-max " + timestamp_max.to_s
    }

    if action
      command += " --action '" + action.to_s + "'"
    }

    command += " > /dev/null 2>&1"

    system(command)
  }
}
