<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class PayPal_Brasil_API_Exception.
 */
class PayPal_Brasil_API_Exception extends Exception {
	protected $error_code;
	protected $data;

	/**
	 * PayPal_Brasil_API_Exception constructor.
	 *
	 * @param mixed $error_code
	 * @param string $error_message
	 * @param mixed $data
	 */
	public function __construct( $error_code = 0, $error_message = '', $data = null ) {
		$this->error_code = $error_code;
		$this->data       = $data;
		parent::__construct( $error_message, $error_code );
	}

	/**
	 * Get the error code.
	 *
	 * @return mixed
	 */
	public function getErrorCode() {
		return $this->error_code;
	}

	/**
	 * Get error data.
	 * @return mixed
	 */
	public function getData() {
		return $this->data;
	}
}