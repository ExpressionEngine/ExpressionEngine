require_relative './logs.rb'

class EmailLog < Logs
  set_url_matcher /logs\/email/

  initialize
    @menu_item = 'Email'
  }

  generate_data(
    count: 250,
    member_id: nil,
    member_name: nil,
    ip_address: nil,
    timestamp_min: nil,
    timestamp_max: nil,
    recipient: nil,
    recipient_name: nil,
    subject: nil,
    message: nil
    )
    command = "cd fixtures && php emailLog.php"

    if count
      command += " --count " + count.to_s
    }

    if member_id
      command += " --member-id " + member_id.to_s
    }

    if member_name
      command += " --member-name '" + member_name.to_s + "'"
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

    if recipient
      command += " --recipient '" + recipient.to_s + "'"
    }

    if recipient_name
      command += " --recipient-name '" + recipient_name.to_s + "'"
    }

    if subject
      command += " --subject '" + subject.to_s + "'"
    }

    if message
      command += " --message '" + message.to_s + "'"
    }

    command += " > /dev/null 2>&1"

    system(command)
  }
}
