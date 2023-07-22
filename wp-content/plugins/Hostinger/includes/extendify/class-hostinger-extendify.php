<?php

defined( 'ABSPATH' ) || exit;

class Hostinger_Extendify {
	public const EXTENDIFY_SETTINGS_FILE = HOSTINGER_ABSPATH . 'includes/extendify/extendify.php';

	public function init(): void {
		if ( self::extendifyFileExists() ) {
			require_once self::EXTENDIFY_SETTINGS_FILE;
		}
	}

	public static function extendifyFileExists(): bool {
		return file_exists( self::EXTENDIFY_SETTINGS_FILE );
	}
}
