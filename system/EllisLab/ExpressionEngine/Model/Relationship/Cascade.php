<?php
namespace EllisLab\ExpressionEngine\Model\Relationship;

/**
 * Implements the primary cascade code.
 *
 * The connections between models can be seen as a directed graph, where
 * vertices go from one to many nodes, or in other language, from parent
 * to child nodes. A parent node must be a one node, otherwise the relation-
 * ship does not resolve.
 *
 * When the graph is acyclic, walking the tree is simple, we simply follow
 * the outgoing edges of each node.
 * For cyclic graphs, we need to do a bit more magic. TODO
 *
 * The cascade can be overriden by the user, in which case the edges of the
 * graph are controlled by the user's cascade. This means that native edges
 * can be walked backwards. When a node has non many-to-many relationships that
 * are walked backwards we do not save the node. It is a child, so it needs the
 * ids from all of its parents before being saved.
 *
 * TODO many-to-many and cycles
 *
 */
/*


$t = ee()->api->get('Template')
	->with('TemplateGroup')
	->limit('template_id', 5)
	->all();

$tg = $t->getTemplateGroup();

$tg->getTemplates();




					Site
	TemplateGroup			TemplateGorup
t1	t2	t3	t4	t5			t6	 t7	 t8	 t9

[t1,t2,t3,t4,t5] - collection 1
[t6, t7, t8, t9] - collection 2


Relationships ordered by to_model or to_class, indexed by their
id. This is only necessary.

Stupid backlinks. If I only queried for two templates and their template group,
when I access getTemplates() from the template group, then it needs to query.
So I need access to the parent collection.

If you save a collection, then the cascade needs to originate at the colleciton,
otherwise it will walk up and down repeatedly.


Relationships are not correctly reversed until after save.

One's should have set and get:
setAuthor(), getAuthor()

Many's should have set, get, and add:
setTemplates(), getTemplates(), addTemplate().


$tg->setTemplates($collection1);

$collection1[0]->getTemplateGroup(); // not true until tg is saved.
$tg->getTemplates(); // $collection1


$newtemplates->setTemplateGroup($tg);
$newtemplates->getTemplateGroup(); // $tg

$tg->getTemplates(); // not true until newtemplates is saved

$tg->addTemplate($newtemplate);
$newtemplate->setTemplateGroup($someother);


$g->save(); // newtemplate saved with tg
$newtemplate->save(); // newtemplate saved with someother.


$templates = $tg->getTemplates();
$templates[] = $newtemplate();
$tg->save();



*/
class Cascade {

	private $model;
	private $graph_visits;
	private $user_cascade;
	private $cached_natives;

	public function __construct($model, $method, $user_cascade)
	{
		if (isset($user_cascade[0]) && $user_cascade[0] instanceOf GraphVisited)
		{
			$this->graph_visits = array_shift($user_cascade);
		}
		else
		{
			$this->graph_visits = new GraphVisited(); // addVisited, wasVisited
		}

		$this->model = $model;
		$this->method = $method;
		$this->user_cascade = $this->mapToKeys($user_cascade);
		$this->cached_natives = $model->getGraphNode()->getAllEdges();
	}

	public function walk($action_callback)
	{
		// If there is a user cascade, we may need to walk a node in the wrong
		// direction. We need to find all of our parents to know if that is the
		// case.
		if ( ! empty($this->user_cascade))
		{
			$dependencies = $this->dependenciesFor($this->model);
			$dependencies = $this->graph_visits->rejectVisited($dependencies);

			$dependencies = array_intersect_key($this->user_cascade, $dependencies);

			if (count($dependencies))
			{
				$first_dependency_getter = 'get'.key($dependencies);

				// TODO The parent model isn't actually connected to the child,
				// so this will query.
				$this->model->$first_dependency_getter()->save(
					$this->graph_visits
				);
				return; // the dependency will walk back down and call save again
			}
		}

		// run the save/delete/whatever routine
		$action_callback($this->model);

		// mark node as visited
		$this->graph_visits->addVisited(get_class($this->model));

		// Grab all the children we need to recurse into
		$child_relationships = $this->childrenFor($this->model);

		foreach ($child_relationships as $name => $collection)
		{
			// slice the user cascade and add the graph visit item
			if (array_key_exists($name, $this->user_cascade))
			{
				$new_user_cascade = $user_cascade[$name];
				array_unshift($new_user_cascade, $this->graph_visits);
			}
			else
			{
				$new_user_cascade = array($this->graph_visits);
			}

			// Propagate keys
			// TODO multiple edges? Check this.
			$reldata = $this->cached_natives[$name];
			$collection->{$reldata->to_key} = $this->model->{$reldata->key};

			// walk down the graph
			call_user_func_array(array($collection, $this->method), $new_user_cascade);
		}
	}

	private function childrenFor($model)
	{
		$children = array();

		foreach ($this->cached_natives as $name => $info)
		{
			if ($info->is_parent && $this->model->hasRelated($name))
			{
				$relationship_getter = 'get'.$name;
				$children[$name] = $this->model->$name;
			}
		}

		return $children;
	}

	private function dependenciesFor($model)
	{
		$deps = array();

		foreach ($this->cached_natives as $name => $info)
		{
			if ( ! $info->is_parent)
			{
				$deps[$name] = $info->to_class;
			}
		}

		return $deps;
	}

	private function collectNatives()
	{
		$m = $this->model;

		$natives = array();
		$names = array_keys($m::getMetaData('relationships'));

		foreach ($names as $name)
		{
			$natives[$name] = $this->model->getRelationshipInfo($name);
		}

		return $natives;
	}

	private function mapToKeys($user_cascade)
	{
		$out = array();

		foreach ($user_cascade as $key => $value)
		{
			if (is_numeric($key))
			{
				$out[$value] = array();
			}
			else
			{
				$out[$key] = $value;
			}
		}

		return $out;
	}
}

class GraphVisited {

	private $visited = array();

	public function addVisited($model_name)
	{
		$this->visited[] = $model_name;
	}

	public function wasVisited($model_name)
	{
		return in_array($model_name, $this->visited);
	}

	public function rejectVisited(array $model_names)
	{
		return array_diff($model_names, $this->visited);
	}
}