<?php
/**
 * REST API Endpoints for Ninja Forms Submissions
 *
 * @package NF_Submissions_API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Class NFSA_Routes handles registration of the custom REST routes.
 */
class VWPNFSA_Routes {

	/**
	 * Register the custom routes.
	 */
	public static function register() {
		register_rest_route(
			'nf-submissions/v1',
			'/form/(?P<form_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_submissions' ),
				'permission_callback' => array( __CLASS__, 'check_permissions' ),
				'args'                => array(
					'begin'  => array( 'required' => false ),
					'end'    => array( 'required' => false ),
					'format' => array( 'required' => false ),
				),
			)
		);

		register_rest_route(
			'nf-submissions/v1',
			'/form/(?P<form_id>\d+)/fields',
			array(
				'callback'            => array( __CLASS__, 'get_field_metadata' ),
				'methods'             => 'GET',
				'permission_callback' => array( __CLASS__, 'check_permissions' ),
			)
		);
	}

	/**
	 * Permission check using API key in headers.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return bool
	 */
	public static function check_permissions( $request ) {
		$key     = $request->get_header( 'x-api-key' );
		$form_id = $request->get_param( 'form_id' );
		return NFSA_Auth::is_valid_key( $key, $form_id );
	}

	/**
	 * Get submissions for a form.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public static function get_submissions( $request ) {
		$form_id = absint( $request['form_id'] );
		$begin   = sanitize_text_field( $request->get_param( 'begin' ) );
		$end     = sanitize_text_field( $request->get_param( 'end' ) );
		$format  = sanitize_text_field( $request->get_param( 'format' ) );

		$data = NFSA_Export::get_form_submissions( $form_id, $begin, $end );
		return NFSA_Export::format( $data, $format );
	}

	/**
	 * Get field metadata for a form.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public static function get_field_metadata( $request ) {
		$form_id = absint( $request['form_id'] );
		$form    = Ninja_Forms()->form( $form_id );
		$fields  = array();

		foreach ( $form->get_fields() as $field ) {
			$fields[] = array(
				'key'      => $field->get_setting( 'key' ),
				'label'    => $field->get_setting( 'label' ),
				'type'     => $field->get_setting( 'type' ),
				'required' => $field->get_setting( 'required' ),
			);
		}

		return rest_ensure_response( $fields );
	}
}

add_action( 'rest_api_init', array( 'VWPNFSA_Routes', 'register' ) );
