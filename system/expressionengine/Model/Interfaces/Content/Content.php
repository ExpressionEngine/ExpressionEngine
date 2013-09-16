<?PHP

/**
 * Represents a piece of content in ExpressionEngine (IE a Channel Entry)
 */
interface Content {

	/**
	 * Renders the piece of content for the front end, parses the tag data
	 * called by the module when rendering tagdata.
	 *
	 * @param	ParsedTemplate|string	$template	The parsed template from
	 * 						the template engine or a string of tagdata.
	 *
	 * @return	Template|string	The parsed template with relevant tags replaced
	 *							or the tagdata string with relevant tags replaced.
	 */
	public function render($template);

	/**
	 * A link back to the owning Structure that defines the structure of this
	 * piece of content.  (A link back to Channel.)
	 *
	 * @return	Structure	A link to the Structure objects that defines this
	 * 						Content's structure.
	 */
	public function getStructure();

	/**
	 * Saves this piece of content after being populated from a form.
	 *
	 * @return	void	
	 *
	 * @throws	ContentInvalidException	If content fails to validate a 
	 *						ContentInvalidException will be thrown with errors
	 *						on the exception object.
	 */	
	public function save();

	/**
	 * Validates a piece of content for saving, called by save.
	 *
	 * @return	void	
	 *
	 * @throws	ContentInvalidException If content fails to validate a 
	 * 						ContentInvalidException will be thrown with errors.
	 */
	public function validate();

	/**
	 * Deletes a piece of content, removing it from the db.
	 *
	 * @return	void 
	 */
	public function delete();
	
}
