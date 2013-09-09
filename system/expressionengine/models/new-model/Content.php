<?PHP

/**
 * Represents a piece of content in ExpressionEngine (IE a Channel Entry)
 */
interface Content {

	/**
	 * Renders the piece of content for the front end, parses the tag data
	 * called by the module when rendering tagdata.
	 */
	public function render($template);

	/**
	 * A link back to the owning Structure that defines the structure of this
	 * piece of content.  (A link back to Channel.)
	 */
	public function structure();

	/**
	 * Saves this piece of content after being populated from a form.
	 */	
	public function save();

	/**
	 * Validates a piece of content for saving, called by save.
	 */
	public function validate();
	
}
