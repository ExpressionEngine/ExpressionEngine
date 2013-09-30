<?PHP
namespace EllisLab\ExpressionEngine\Model;


/**
 * Wrapper class for multiple errors, to be returned from validation.
 */
class Errors {
	public $errors = array();

	/**
	 * Did validation result in any errors?
	 *
	 * @return	boolean	TRUE if there are errors, FALSE otherwise.
	 */	
	public function hasErrors() {
		return ( ! empty($this->errors));
	}
}
