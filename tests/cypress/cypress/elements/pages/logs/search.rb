require_relative './logs.rb'

class SearchLog < Logs
  set_url_matcher /logs\/search/

  initialize
    @menu_item = 'Search'
  }

  generate_data(
    count: 250,
    site_id: nil,
    member_id: nil,
    screen_name: nil,
    ip_address: nil,
    timestamp_min: nil,
    timestamp_max: nil,
    type: nil,
    terms: nil
    )
    command = "cd fixtures && php searchLog.php"

    if count
      command += " --count " + count.to_s
    }

    if site_id
      command += " --site-id " + site_id.to_s
    }

    if member_id
      command += " --member-id " + member_id.to_s
    }

    if screen_name
      command += " --screen-name '" + screen_name.to_s + "'"
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

    if type
      command += " --type '" + type.to_s + "'"
    }

    if terms
      command += " --terms '" + terms.to_s + "'"
    }

    command += " > /dev/null 2>&1"

    system(command)
  }
}
