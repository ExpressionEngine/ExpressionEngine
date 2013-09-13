<?php
/**
 * Member module pseudo-implementation for a user
 *
 * Creating (not sure about exceptions):
 * $member = new Member();
 * $member->username($email);
 * try {
 *     $member->save();
 * } catch (MemberValidationException $ex) {
 *     var_dump($member->error());
 * }
 *
 * Authenticating:
 * $member = new Member(array(
 *     'username' => $username,
 *     'password' => $password
 * ));
 *
 * if ( ! $member->authenticate()) {
 *     // handle failed login
 * }
 *
 */
class Member implements User {

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
	 * Validate the user data.
	 */
	public function validate()
	{
		// run validation and throw exception?
		// check things such as required fields?
		// was password set, but not hashed
	}

	/**
	 * Save the user data
	 */
	public function save()
	{
		$this->validate();
		// save data
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

	/**
	 * Getters and Setters
	 */
	public function __call($key, $value = NULL)
	{
		if ( ! isset($value))
		{
			return $this->entity->$key;
		}

		$this->entity->$key = $value;
	}
}
