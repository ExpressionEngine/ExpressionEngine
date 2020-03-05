require_relative './logs.rb'

class EmailLog < Logs
  set_url_matcher /logs\/email/

  def initialize
    @menu_item = 'Email'
  end

  def generate_data(
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
    end

    if member_id
      command += " --member-id " + member_id.to_s
    end

    if member_name
      command += " --member-name '" + member_name.to_s + "'"
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

    if recipient
      command += " --recipient '" + recipient.to_s + "'"
    end

    if recipient_name
      command += " --recipient-name '" + recipient_name.to_s + "'"
    end

    if subject
      command += " --subject '" + subject.to_s + "'"
    end

    if message
      command += " --message '" + message.to_s + "'"
    end

    command += " > /dev/null 2>&1"

    system(command)
  end
end
