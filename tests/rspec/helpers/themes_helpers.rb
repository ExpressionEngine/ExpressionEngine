module Themes
  # Helps prepare the User Themes for rspec tests
  class Prepare
    def initialize
      themes             = '../../themes/'
      system             = '../../system/'
      @member_theme_src  = File.expand_path('ee/templates/_themes/member', system)
      @member_assets_src  = File.expand_path('ee/member', themes)
      @forum_theme_src   = File.expand_path('ee/templates/_themes/forum', system)
      @forum_assets_src  = File.expand_path('ee/forum', themes)

      @user_assets_dir    = File.expand_path('user', themes)
      @user_theme_dir    = File.expand_path('user/templates/_themes', system)
      FileUtils.mkdir_p @user_theme_dir
    end

    # Copies themes/ee/member assets to themes/user and templates to system/user
    def copy_member_themes
      FileUtils.copy_entry @member_theme_src, @user_theme_dir + '/member'
      FileUtils.copy_entry @member_assets_src, @user_assets_dir + '/member'

    end

    # Copies themes/ee/member assets to themes/user and templates to themes/user
    # In v5 this will not be a needed check
    def copy_member_themes_to_user
      FileUtils.copy_entry @member_theme_src, @user_assets_dir + '/member'
      FileUtils.copy_entry @member_assets_src, @user_assets_dir + '/member'
    end

    # Copies themes/ee/forum assets to themes/user and templates to system/user/
    def copy_forum_themes
      FileUtils.copy_entry @forum_theme_src, @user_theme_dir + '/forum'
      FileUtils.copy_entry @forum_assets_src, @user_assets_dir + '/forum'
    end

    # Copies themes/ee/forum assets to themes/user and templates to themes/user
    # In v5 this will not be a needed check
    def copy_forum_themes_to_user
      FileUtils.copy_entry @forum_theme_src, @user_assets_dir + '/forum'
      FileUtils.copy_entry @forum_assets_src, @user_assets_dir + '/forum'
    end
  end
end
