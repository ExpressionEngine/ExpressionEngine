module Themes
  # Helps prepare the User Themes for rspec tests
  class Prepare
    def initialize
      themes             = '../../themes/'
      @member_theme_src  = File.expand_path('ee/member', themes)
      @forum_theme_src   = File.expand_path('ee/forum', themes)
      @user_theme_dir    = File.expand_path('user', themes)
    end

    # Copies themes/ee/member themes to themes/user
    def copy_member_themes
      FileUtils.copy_entry @member_theme_src, @user_theme_dir + '/member'
    end

    # Copies themes/ee/forum themes to themes/user
    def copy_forum_themes
      FileUtils.copy_entry @forum_theme_src, @user_theme_dir + '/forum'
    end
  end
end
