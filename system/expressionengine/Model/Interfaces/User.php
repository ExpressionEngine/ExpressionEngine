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
    public function getDisplayName();
   
	/**
	 * An email to be used to contact that user.  If we have one.
	 * When parameter is provided acts as a setter, otherwise acts
	 * as a getter.
	 *
	 * @return	string		Return the e-mail that we may use to contact this
	 * 						user.	
	 */ 
    public function getEmail();
   
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
