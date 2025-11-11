<?php
/**
 * REST Controller for Ninja Forms Submissions API
 *
 * @package NF_Submissions_API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Constructor.
 */
/**
 * Class to faciltiate custom REST routes.
 */
class VWPNFSA_REST_Controller {

	/**
	 * Register custom REST routes.
	 */
	public function register_routes() {
		register_rest_route(
			'nf-submissions/v1',
			'/form/(?P<form_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_submissions' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		register_rest_route(
			'nf-submissions/v1',
			'/form/(?P<form_id>\d+)/fields',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_field_metadata' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);
	}

	/**
	 * Check API key permissions.
	 *
	 * @return bool
	 */
	/**
	 * Check API key permissions for specific form access.
	 * This function runs AFTER the 'rest_authentication_errors' filter.
	 *
	 * @param  WP_REST_Request $request The request object.
	 * @return bool|WP_Error
	 */
	public function check_permissions( $request ) {
		// Ensure that NFSA_Auth is loaded.
		if ( ! class_exists( 'NFSA_Auth' ) ) {
			include_once VWPNFSA_SUB_API_PATH . 'includes/class-vwpnfsa-auth.php';
		}
		// Get form ID from request parameters.
		$form_id = (int) $request->get_param( 'form_id' );

		// Extract the API key from the Authorization header again.
		$auth_header = isset( $_SERVER['HTTP_AUTHORIZATION'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) )
			: ( function_exists( 'apache_request_headers' ) && isset( apache_request_headers()['Authorization'] ) ? apache_request_headers()['Authorization'] : '' );

		$api_key = '';
		if ( stripos( $auth_header, 'Bearer ' ) === 0 ) {
			$api_key = trim( str_ireplace( 'Bearer', '', $auth_header ) );
		}

		// If for some reason the key isn't extracted, or empty after extraction.
		if ( empty( $api_key ) ) {
			// This might happen if the Authorization header was missing/malformed and
			// authenticate_request returned a 401, but a server layer converts it to 403,
			// or if authenticate_request was somehow bypassed.
			return new WP_Error( 'rest_forbidden_no_key_found', __( 'API Key not found in Authorization header.', 'api-for-ninja-forms' ), array( 'status' => 403 ) );
		}

		// Now, use your NFSA_Auth::is_valid_key to check form-specific permissions.
		if ( ! VWPNFSA_Auth::is_valid_key( $api_key, $form_id ) ) {
			return new WP_Error( 'rest_forbidden_form_access', __( 'API Key not authorized for this specific form.', 'api-for-ninja-forms' ), array( 'status' => 403 ) );
		}
		return true; // Authentication and form-specific permission check passed.
	}

	/**
	 * Handle submissions retrieval.
	 *
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_submissions( $request ) {
		$form_id    = (int) $request->get_param( 'form_id' );
		$begin_date = sanitize_text_field( $request->get_param( 'begin_date' ) );
		$end_date   = sanitize_text_field( $request->get_param( 'end_date' ) );
		$format     = sanitize_text_field( $request->get_param( 'format' ) );

		$data = VWPNFSA_Export::get_form_submissions( $form_id, $begin_date, $end_date );

		return VWPNFSA_Export::format( $data, $form_id, $format );
	}

	/**
	 * Handle field metadata retrieval.
	 *
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_field_metadata( $request ) {
		$form_id = (int) $request->get_param( 'form_id' );

		$form   = Ninja_Forms()->form( $form_id );
		$fields = $form ? $form->get_fields() : array();
		$meta   = array();
		foreach ( $fields as $field ) {
			$meta[] = array(
				'label' => $field->get_setting( 'label' ),
				'key'   => $field->get_setting( 'key' ),
				'type'  => $field->get_setting( 'type' ),
			);
		}

		return rest_ensure_response( $meta );
	}
}
