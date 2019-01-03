#!/bin/bash

## This source file is part of the open source project
## ExpressionEngine (https://expressionengine.com)
##
## @link      https://expressionengine.com/
## @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
## @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0

set -eu
here="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
contributors=$( cd "$here"/.. && git shortlog -es | cut -f2 | sed 's/^/- /' )
emails="\n", read -r -a array <<< $(git shortlog -es | cut -d '<' -f2 | sed 's/>//' | tr '[:upper:]' '[:lower:]')
gravatars=""

for email in "${array[@]}"
do
    hash=$(echo -n $email | md5sum | awk '{ print $1 }')
    gravatars="${gravatars}![](https://www.gravatar.com/avatar/$hash.jpg?r=pg&d=robohash )"
done

cat > "$here/../AUTHORS.md" <<- EOF
This file is an alphabetical list of all who have contributed source code to ExpressionEngine.

If an entry is incorrect or duplicated, add or correct the entry in \`.mailmap\` in the root of this repository. The list is generated using \`git shortlog\`.

$contributors

$gravatars

Additionally, the following contributed source code prior to 2009 when this repository was created:

- Paul Burdick
- Jamie Poitra

Thanks to Apple SwiftNIO (https://github.com/apple/swift-nio) for the inspiration for auto-generation.
EOF
