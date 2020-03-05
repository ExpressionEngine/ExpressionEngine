require_relative './logs.rb'

class ThrottleLog < Logs
  set_url_matcher /logs\/throttle/

  def initialize
    @menu_item = 'Throttling'
  end

  def generate_data(
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

    if hits
      command += " --hits " + hits.to_s
    end

    if locked_out
      command += " --locked-out"
    end

    command += " > /dev/null 2>&1"

    system(command)
  end
end
