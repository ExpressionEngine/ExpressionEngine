#!/bin/bash
#
#
# This is just going to be in here temporarily.  Since JQTouch is fairly new and in
# active development, it's SVN environment is updated quite regularly.  Without 
# the ability to add svn externals, this seems like a decent solution for the time being
#
# NOTE:  Needs to be run from the templates/cp_themes/mobile/ folder.  ;)
# Usage:  ./touch_refresh.sh

### Images coming from the JQ Touch Source ###
JQTOUCHIMG='/Volumes/Development/ee/jqtouch/themes/apple/img/';

### Base JQTouch CSS File ###
JQBASECSS='/Volumes/Development/ee/jqtouch/jqtouch/jqtouch.css';

### Theme Specific JQTOUCH CSS File ###
JQTOUCHCSS='/Volumes/Development/ee/jqtouch/themes/apple/theme.css';

### JQtouch javascript file. ###
JQTOUCHJS='/Volumes/Development/ee/jqtouch/jqtouch/jqtouch.js';

EECSSFILE=`pwd`'/css/global.css';

## Remove the css file that's currently around.
echo '';
echo '>> Removing Existing CSS File >>';
echo '';
rm $EECSSFILE

echo '>> Combining JQTouch Master and Apple Theme CSS File into EE global.css >>';
echo '';
cat $JQBASECSS >> $EECSSFILE;
cat $JQTOUCHCSS >> $EECSSFILE;

echo '>> Adding in ExpressionEngine theme directory variables.';
sed -i -n 's/url(img\//url(<?=$cp_theme_url?>images\//g' $EECSSFILE >> $EECSSFILE;
rm $EECSSFILE-n;
sed -i -n 's/url(..\/images\//url(<?=$cp_theme_url?>images\//g' $EECSSFILE >> $EECSSFILE;
rm $EECSSFILE-n;

echo '>> Updating Images >>';
echo '';
# totally hackish in this context, As I watch revisions, I'm going to add in a mtime check, I suppose.
find $JQTOUCHIMG -name '*.png' -exec cp {} `pwd`/images/ \;


echo 'All Done.  Happy Coding.  :)';
echo '';
exit 0;