<?php
/**
 * API Key Authentication for Ninja Forms Submissions API
 *
 * @package NF_Submissions_API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class NFSA_Auth
 */
class VWPNFSA_Auth {

	/**
	 * Validate API key for form access.
	 *
	 * @param  string $key     API key.
	 * @param  int    $form_id Form ID.
	 * @return bool
	 */
	public static function is_valid_key( $key, $form_id ) {
		if ( empty( $key ) ) {
			return false;
		}

		$keys = get_option( 'vwpnfsa_api_keys', array() );

		if ( ! isset( $keys[ $key ] ) ) {
			return false;
		}

		$allowed_forms = $keys[ $key ]['forms'];

		if ( in_array( 'all', $allowed_forms, true ) ) {
			return true;
		}

		return in_array( (string) $form_id, $allowed_forms, true );
	}

	/**
	 * Generate a new API key and store it.
	 *
	 * @param  array $form_ids Allowed form IDs.
	 * @return string Generated key.
	 */
	public static function generate_key( $form_ids = array( 'all' ) ) {
		$keys = get_option( 'vwpnfsa_api_keys', array() );

		do {
			$key = bin2hex( random_bytes( 16 ) );
		} while ( isset( $keys[ $key ] ) );

		$keys[ $key ] = array(
			'forms'   => array_map( 'strval', $form_ids ),
			'created' => current_time( 'mysql' ),
		);

		update_option( 'vwpnfsa_api_keys', $keys );

		return $key;
	}

	/**
	 * Remove an API key.
	 *
	 * @param string $key API key to remove.
	 */
	public static function revoke_key( $key ) {
		$keys = get_option( 'vwpnfsa_api_keys', array() );

		if ( isset( $keys[ $key ] ) ) {
			unset( $keys[ $key ] );
			update_option( 'vwpnfsa_api_keys', $keys );
		}
	}


	/**
	 * Authenticate REST API requests using a Bearer token (API Key).
	 *
	 * @param mixed $result Authentication result from previous filters.
	 * @return mixed True on success, WP_Error on failure, or original result if already authenticated.
	 */
	public static function authenticate_request( $result ) {
		if ( ! empty( $result ) ) {
			return $result; // Another plugin has already authenticated.
		}
		global $wp;
		$route = $wp->query_vars['rest_route'] ?? '';

		// Bug fix, we only want to check the NinjaForm api endpoint.
		if ( strpos( $route, '/wp-json/nf-submissions/v1/' ) === false ) {
			return $result; // End processing and return original result.
		}
		$auth_header = isset( $_SERVER['HTTP_AUTHORIZATION'] )
		? sanitize_text_field( wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) )
		: ( function_exists( 'apache_request_headers' ) ? apache_request_headers()['Authorization'] ?? '' : '' );

		if ( ! $auth_header || stripos( $auth_header, 'Bearer ' ) !== 0 ) {
			return new WP_Error( 'rest_forbidden', 'Missing or invalid Authorization header.', array( 'status' => 401 ) );
		}

		$token = trim( str_ireplace( 'Bearer', '', $auth_header ) );
		$keys  = get_option( 'vwpnfsa_api_keys', array() );

		if ( ! isset( $keys[ $token ] ) ) {
			return new WP_Error( 'rest_forbidden', 'Invalid API Key.', array( 'status' => 401 ) );
		}

		// Store allowed forms in global state for later access.
		$GLOBALS['vwpnfsa_allowed_forms'] = $keys[ $token ]['forms'];

		return true;
	}
}


add_filter( 'rest_authentication_errors', array( 'VWPNFSA_Auth', 'authenticate_request' ) );
