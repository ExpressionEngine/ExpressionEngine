#!/usr/bin/env ruby
# Bumps the version numbers in all EE files based on build.properties

require 'date'

# Replaces a file_name using the regexp hash, where the index is the regular
# expression and the value is the replacement
def replace (file_name, regexp)
  text = File.read(file_name)

  regexp.each {|pattern, replacement|
    text.gsub!(pattern, replacement)
  }

  File.open(file_name, 'w') {|file| file.write(text)}
end

# Create build date
build_date = Date.today.strftime('%Y%m%d')

# First load in the data from build.properties
contents    = File.read('build.properties')
ee_version  = /^ee2.version\s*?= (.*)/.match(contents)[1].chomp
ee_build    = (tmp = /^ee2.build\s*?= (.*)/.match(contents)) ? tmp[1].chomp : build_date
msm_version = /^ee2.msm.version\s*?= (.*)/.match(contents)[1].chomp
msm_build   = (tmp = /^ee2.msm.build\s*?= (.*)/.match(contents)) ? tmp[1].chomp : build_date
df_version  = /^ee2.forum.version\s*?= (.*)/.match(contents)[1].chomp
df_build    = (tmp = /^ee2.forum.build\s*?= (.*)/.match(contents)) ? tmp[1].chomp : build_date

# Core.php
replace(
  'system/expressionengine/libraries/Core.php',
  Hash[
    /define\('APP_VER',(\s+)'.*?'\);/   => "define('APP_VER',\\1'#{ee_version}');",
    /define\('APP_BUILD',(\s+)'.*?'\);/ => "define('APP_BUILD',\\1'#{ee_build}');"
  ]
)

# wizard.php
replace(
  'system/installer/controllers/wizard.php',
  Hash[/\$version(\s+)= '.*?';/ => "$version\\1= '#{ee_version}';"]
)

# mod.forum.php
replace(
  'system/expressionengine/modules/forum/mod.forum.php',
  Hash[
    /\$version(\s+)= '.*?';/ => "$version\\1= '#{df_version}';",
    /\$build(\s+)= '.*?';/ => "$build\\1= '#{df_build}';"
  ]
)

# upd.forum.php
replace(
  'system/expressionengine/modules/forum/upd.forum.php',
  Hash[/\$version(\s+)= '.*?';/ => "$version\\1= '#{df_version}';"]
)

# sites.php (controllers)
replace(
  'system/expressionengine/controllers/cp/sites.php',
  Hash[
    /\$version(\s+)= '.*?';/ => "$version\\1= '#{msm_version}';",
    /\$build_number(\s+)= '.*?';/ => "$build_number\\1= '#{msm_build}';"
  ]
)
