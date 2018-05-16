<?php

class UserDeletionHelper {
	const IV_SIZE = 16;

	/**
	 * @param string        $str
	 * @param null | string $key
	 *
	 * @return string
	 */
	static public function decrypt( $str, $key = null ) {
		if ( null == $key ) {
			$key = getenv( "KEK" );
		}
		if ( ! empty( $key ) ) {
			$strDec        = base64_decode( $str );
			$iv            = substr( $strDec, 0, self::IV_SIZE );
			$text_only     = substr( $strDec, self::IV_SIZE );
			$plaintext_dec = openssl_decrypt( $text_only, 'AES-256-CBC', $key, 0, $iv );

			return $plaintext_dec;
		}

		return $str;
	}

	/**
	 * @param string        $str
	 * @param null | string $key
	 *
	 * @return string
	 */
	public static function encrypt( $str, $key = null ) {
		if ( null == $key ) {
			$key = getenv( "KEK" );
		}
		$iv    = openssl_random_pseudo_bytes( self::IV_SIZE );
		$crypt = openssl_encrypt( $str, 'AES-256-CBC', $key, null, $iv );

		return trim( base64_encode( $iv . $crypt ) );
	}
}