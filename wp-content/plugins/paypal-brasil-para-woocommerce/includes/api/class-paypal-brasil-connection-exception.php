<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class PayPal_Brasil_Connection_Exception.
 */
class PayPal_Brasil_Connection_Exception extends Exception {
	protected $error_code;
	protected $data;

	/**
	 * PayPal_Brasil_Connection_Exception constructor.
	 *
	 * @param mixed $error_code
	 * @param mixed $data
	 */
	public function __construct( $error_code = 0, $data = null ) {
		$this->error_code = $error_code;
		$this->data       = $data;
		parent::__construct( __( 'There was an error connecting to PayPal.', "paypal-brasil-para-woocommerce" ), is_int( $error_code ) ? $error_code : 0 );
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