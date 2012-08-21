<?php

/**
 * Interface which enforces ReSTful CRUD semantics on a class.
 */
interface ICrud {

	/**
	 * Create an object.
	 */
	public function create();

	/**
	 * Retrieves an object.
	 */
	public function retrieve();

	/**
	 * Updates an object.
	 */
	public function update();

	/**
	 * Deletes an object.
	 */
	public function delete();
}
