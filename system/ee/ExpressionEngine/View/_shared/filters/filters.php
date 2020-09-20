<?php

if ( ! empty($filters) && is_array($filters)) {
	foreach ($filters as $name => $filter) {
		if (in_array($filter['name'], ['filter_by_keyword', 'search_in', 'coldumns', 'perpage'])) {
			continue;
		}
		echo $filter['html'];
	}
}
