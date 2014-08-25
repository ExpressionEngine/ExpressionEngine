<?php
namespace EllisLab\ExpressionEngine\Model\Relationship;

/**
 * Implements the primary cascade code.
 *
 * The connections between models can be seen as a directed graph, where
 * vertices go from the parent node to the child node(s).
 *
 * For OneToMany and ManyToOne relationships, the parent node is the 'one'
 * side, so that the edges of the graph will always be One --> Many.
 *
 * In a OneToOne the owner relationship is the one that does not contain
 * the key of the other in its fields. This is similar to the 'one' side
 * in a ManyToOne/OneToMany relationship.
 *
 * ManyToMany is special in that they are both outwards pointing from the
 * pivot: Many <-- pivot --> Many. While we will essentially ignore the pivot
 * when traversing as it is not a model concern, this restriction is still in
 * place so that ManyToMany are only traversed if they are specified in the
 * cascade.
 *
 * When the graph is acyclic, walking it is simple, we simply follow the out-
 * going edges of each node.
 * For cyclic graphs, we need to do a bit more magic. TODO
 *
 * The developer can choose to specify their own "cascade" which controls the
 * nodes that are walked. In that case the native edges can be walked backwards.
 * When a node has non-ManyToMany relationships that are walked backwards, we
 * do not apply the callback action to the node. Instead, we first walk to the
 * parent to resolve the dependency, and then let the natural graph result in
 * the children being traversed.
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


Deleting needs to work differently, we don't want to query for everything
that we might delete. So we really need to run another join.


TODO many-to-many and cycles

If there is a cycle, figure out the multiplicity of the edges between the two
nodes and choose the edge with the most incoming edges to the fewest
outgoing edges?

*/
class Cascade {

	private $model;
	private $method;
	private $graph_visits;
	private $user_cascade;

	/**
	 * @param Model $model			The starting model for the cascade
	 * @param String $method		The method that was called (e.g. save, delete, etc)
	 * @param Array  $user_cascade	The cascade specified by the user.
	 */
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
		$this->graph_node = $model->getGraphNode();
	}

	/**
	 * Walk the node tree, and call the action_callback on each in the correct
	 * order.
	 *
	 * @param Closure $action_callback
	 * @return void
	 */
	public function walk($action_callback)
	{
		// If there is a user cascade, we may need to walk a node in the wrong
		// direction. We need to find all of our parents to know if that is the
		// case.
		if ( ! empty($this->user_cascade))
		{
			$dependencies = $this->getDependencies();
			$dependencies = $this->graph_visits->rejectVisited($dependencies);
			$dependencies = array_intersect_key($this->user_cascade, $dependencies);

			if (count($dependencies))
			{
				$dependency_getter = key($dependencies);

				$this->model->$dependency_getter->save(
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
		$child_relationships = $this->getChildren();

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
			$reldata = $this->graph_node->getEdgeByName($name);
			$collection->{$reldata->to_key} = $this->model->{$reldata->key};

			// walk down the graph
			call_user_func_array(array($collection, $this->method), $new_user_cascade);
		}
	}

	public function walkUp($callback)
	{

	}

	/**
	 * Get all children of the current node. Children are at the end of our
	 * outgoing edges, but we only want those that we actually have data for.
	 */
	private function getChildren()
	{
		$children = $this->graph_node->getAllOutgoingEdges();

		foreach ($children as $name => $info)
		{
			if ($this->model->hasRelated($name))
			{
				$children[$name] = $this->model->$name;
			}
			else
			{
				unset($children[$name]);
			}
		}

		return $children;
	}

	/**
	 * Get all dependencies by class name. Dependencies are the parent nodes
	 * or, in other words, the nodes on the incoming edges.
	 */
	private function getDependencies()
	{
		$dependencies = $this->graph_node->getAllIncomingEdges();

		foreach ($dependencies as $name => $info)
		{
			$dependencies[$name] = $info->to_class;
		}

		return $dependencies;
	}

	/**
	 * Normalize the user cascade so that all of the numeric keys match the
	 * array syntax.
	 *
	 * In: save('Channel', array('Member' => 'MemberGroup'))
	 * Out: array('Channel' => array(), 'Member' => array('MemberGroup'))
	 *
	 */
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