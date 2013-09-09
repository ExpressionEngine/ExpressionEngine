<?PHP

/**
 * An interface representing any user of the software (including a guest).
 */
interface User {

	/**
	 * The name we will be displaying on both front end and backend.
	 *
	 * @return	string	The user's display name.
	 */
    public function displayName();
   
	/**
	 * An email to be used to contact that user.  If we have one.
	 * When parameter is provided acts as a setter, otherwise acts
	 * as a getter.
	 *
	 * @param	string	$email	This user's email.  If provided methods acts as
	 * 						A setter and returns $this.
	 *
	 * @return	string|$this	If parameter is provided, returns $this to
	 * 						allow setter chaining.  Otherwise, acts as a getter
	 * 						and returns the email.
	 */ 
    public function email($email=NULL);
   
	/**
	 * A method that attempts to authenticate a user and log them in.
	 *
	 * @return	boolean	TRUE on success, FALSE otherwise.
	 */ 
    public function authenticate();
   
	/**
	 * Save a user.
	 *
	 * NOTE We're reconsidering the use of exceptions for reporting validation
	 * errors.  Exceptions are intended for exceptional states.  Are validation
	 * errors truly exceptional?
	 */ 
    public function save();
	
	/**
	 * Validate a user.
	 */
    public function validate();

	/**
	 * Delete a user.
	 */
    public function delete();

}
