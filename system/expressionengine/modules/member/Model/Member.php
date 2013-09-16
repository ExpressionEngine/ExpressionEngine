<?php

class Member extends Model implements Content, User {

	private $entity;

	/**
	 * Create a new member / fill an existing member
	 *
	 * @param Array  $data  Default fields to populate
	 */
	public function __construct(array $data = NULL)
	{
		if (isset($data))
		{
			// call setters to populate data
			foreach ($data as $key => $value)
			{
				$this->$key($value);
			}
		}
	}

	// Implement Content

	/**
	 * Show the member / memberdata on the frontend.
	 *
	 * @param	ParsedTemplate|string	$template	The parsed template from
	 * 						the template engine or a string of tagdata.
	 *
	 * @return	Template|string	The parsed template with relevant tags replaced
	 *							or the tagdata string with relevant tags replaced.
	 */
	public function render($template)
	{

	}

	/**
	 * A link back to the member structure.
	 *
	 * @return	MemberStructure   A link to the Structure objects that defines this
	 *                            Content's structure.
	 */
	public function structure()
	{

	}

	/**
	 * Delete the member
	 *
	 * @return	void
	 */
	public function delete()
	{

	}

	// Implement User

	/**
	 * The name we will be displaying on both front end and backend.
	 *
	 * @return	string	The user's display name.
	 */
	public function displayName()
	{
		return $this->entity->screen_name;
	}

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
	public function email($email=NULL)
	{
		if ($email !== NULL)
		{
			$this->entity->email = $email;
			return $this;
		}
		else
		{
			return $this->entity->email;
		}
	}

	/**
	 * Authenticate the member using EE's authentication
	 *
	 * Needs to have username/email
	 */
	public function authenticate()
	{
		$result = ee()->auth->verify_username(/* ... */);
		// generate error / session?
	}

	/**
	 * Implement password setter directly to disable the getter on the password.
	 */
	public function password($password)
	{
		if ($this->salt()) // we're already logged in
		{
			$this->password = hash($password, $this->salt);
		}
		else
		{
			// New user, or authenticating:
			// Set password, but flag for hashing so the raw password
			// cannot be saved. Need to set raw password in case authenticate
			// gets called.
		}
	}

	// Implement methods shared by the interaces

	/**
	 * Validate the member data.
	 */
	public function validate()
	{
		// check things such as required fields?
		// was password set, but not hashed?
	}

	/**
	 * Save this member
	 *
	 * @return	void
	 *
	 * @throws	ContentInvalidException	If content fails to validate a
	 *						ContentInvalidException will be thrown with errors
	 *						on the exception object.
	 */
	public function save()
	{
		if ( ! $this->validate())
		{
			throw new ContentInvalidException;
		}

		// save
	}
}