require_relative './logs.rb'

class DeveloperLog < Logs
  set_url_matcher /logs\/developer/

  initialize
    @menu_item = 'Developer'
  }

  generate_data(
    count: 250,
    timestamp_min: nil,
    timestamp_max: nil,
    description: nil
    )
    command = "cd fixtures && php developerLog.php"

    if count
      command += " --count " + count.to_s
    }

    if timestamp_min
      command += " --timestamp-min " + timestamp_min.to_s
    }

    if timestamp_max
      command += " --timestamp-max " + timestamp_max.to_s
    }

    if description
      command += " --description '" + description.to_s + "'"
    }

    command += " > /dev/null 2>&1"

    system(command)
  }
}
