<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fields = array(
	'review-payment',
	'payer-id',
	'pay-id',
	'override-address',
);

$prefix = 'paypal-brasil-shortcut-';

foreach ( $fields as $field ) {
	$value = isset( $_GET[ $field ] ) ? sanitize_text_field( $_GET[ $field ] ) : '';
	echo sprintf( '<input type="hidden" name="%s" value="%s" placeholder="%s">', $prefix . $field, $value, $field );
}
