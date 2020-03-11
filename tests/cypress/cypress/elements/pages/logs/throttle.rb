require_relative './logs.rb'

class ThrottleLog < Logs
  set_url_matcher /logs\/throttle/

  initialize
    @menu_item = 'Throttling'
  }

  generate_data(
    count: 250,
    ip_address: nil,
    timestamp_min: nil,
    timestamp_max: nil,
    hits: nil,
    locked_out: nil
    )
    command = "cd fixtures && php throttlingLog.php"

    if count
      command += " --count " + count.to_s
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

    if hits
      command += " --hits " + hits.to_s
    }

    if locked_out
      command += " --locked-out"
    }

    command += " > /dev/null 2>&1"

    system(command)
  }
}
