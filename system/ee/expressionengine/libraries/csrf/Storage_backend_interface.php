<?php

interface Csrf_storage_backend {

	/**
	 * Get the token expiration time
	 *
	 * @return int The token expiration in seconds
	 */
	public function get_expiration();

	/**
	 * Store a new token for the user
	 *
	 * @param $token String New token to store
	 * @return void
	 */
	public function store_token($token);

	/**
	 * Delete the user's current token
	 *
	 * @return void
	 */
	public function delete_token();

	/**
	 * Fetch the user's token
	 *
	 * @return string Current user token
	 */
	public function fetch_token();

}