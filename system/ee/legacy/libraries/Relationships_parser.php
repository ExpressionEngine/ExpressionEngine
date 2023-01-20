<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
require_once APPPATH . 'libraries/relationship_parser/Exceptions.php';
require_once APPPATH . 'libraries/relationship_parser/Tree_builder.php';

/**
 * Relationship
 *
 *
 * Takes an array of field_ids that correspond to the ids of the
 * relationship fields that we need to pull entries from in the
 * relationship query. This array comes directly from the tag data. For
 * example, if we have a channel set up like the following:
 *
 * Seasons
 * 	title
 * 	url_title
 * 	games		RELATIONSHIP (Games)
 * 	teams		RELATIONSHIP (Teams)
 *
 * Games
 * 	title
 * 	url_title
 * 	home		RELATIONSHIP (Teams, 1)
 * 	away		RELATIONSHIP (Teams, 1)
 *
 * Teams
 * 	title
 * 	url_title
 * 	players		RELATIONSHIP (Players)
 *
 * Players
 * 	title
 * 	url_title
 * 	first_name
 * 	last_name
 * 	number
 *
 * Then we might see tag data that looked like the following:
 *
 * 	{exp:channel:entries channel="Seasons"}
 * 		{games}
 * 			{games:home:title} vs {games:away:title}
 * 			{games:home:players}
 * 				{games:home:players:number} { games:home:players:first_name} {games:home:players:last_name}
 * 			{/games:home:players}
 * 			{games:away:players}
 * 				{games:away:players:number} {games:away:players:first_name} {games:away:players:last_name}
 * 			{games:away:players}
 * 		{/games}
 * 	{/exp:channel:entires}
 *
 *
 * We're only interested in the relationship fields, but we want to maintain
 * the parent child relationships of the tags, so we turn it into a tree
 * hierachy such as this:
 *
 *					{games}
 *		{games:home}		{games:away}
 * {games:home:players}	{games:away:players}
 *
 *
 * Using the branch depth of that structure, we can construct a join that will
 * return all of the potential entry ids from our adjecency list table. More
 * importantly, we can overlay this parent sibling information on our tree so
 * that we end up with a set of ids for each parent at any given tag:
 *
 * 				 {[6, 7]}
 * 				/		\
 *  {6:[2,4], 7:[8,9]}    	{6:[], 7:[2,5]}
 * 		/					\
 * 	...				  		 ...
 *
 *
 * This also means that we can query for most of the required data before the
 * channel entries loop runs. There are a few edge cases that we need to
 * consider in this approach. Since an entry can have multiple parents, some
 * of which may not be on the current tree, we cannot rely on the tree to
 * provide us with parent information. Instead we add a query with an inverted
 * tree at those edge-case locations.
 */
class EE_Relationships_parser
{
    public function __construct()
    {
        ee()->load->model('relationship_model');
    }

    /**
     * Get a relationship parser and query object, populated with the
     * information we'll need to parse out the relationships in this template.
     *
     * @param	The rfields array from the Channel Module at the time of parsing.
     *
     * @return Relationship_Parser	The parser object with the parsed out
     *								hierarchy and all of the entry data.
     */
    public function create(array $relationship_fields, array $entry_ids, $tagdata = '', array $grid_relationships = array(), $grid_field_id = null, $fluid_field_data_id = null, $disabledFeatures = [])
    {
        if (! empty($relationship_fields) && ! is_array(current($relationship_fields))) {
            $relationship_fields = array($relationship_fields);
        }

        $tagdata = ($tagdata) ?: ee()->TMPL->tagdata;

        $builder = new EE_relationship_tree_builder($relationship_fields, $grid_relationships, $grid_field_id, $fluid_field_data_id);

        $tree = $builder->build_tree($entry_ids, $tagdata);

        if ($tree) {
            return $builder->get_parser($tree, $disabledFeatures);
        }

        return null;
    }
}

// EOF
