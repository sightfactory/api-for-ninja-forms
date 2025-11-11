<?php
/**
 * Submission Export and Formatter Class
 *
 * @package NF_Submissions_API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class NFSA_Export handles submission export and formatting.
 */
class VWPNFSA_Export {

	/**
	 * Retrieve submissions by form and date.
	 *
	 * @param  int    $form_id    Form ID.
	 * @param  string $begin_date Begin date.
	 * @param  string $end_date   End date.
	 * @return array
	 */
	public static function get_form_submissions( $form_id, $begin_date = '', $end_date = '' ) {
		$args = array(
			'post_type'              => 'nf_sub',
			'posts_per_page'         => -1,
			'orderby'                => 'date',
			'order'                  => 'ASC',
			'cache_results'          => false,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             =>
			array(
				array(
					'key'   => '_form_id',
					'value' => $form_id,
				),
			),
		);

		if ( $begin_date || $end_date ) {
			$args['date_query'] = array( 'inclusive' => true );

			if ( $begin_date ) {
				$args['date_query']['after'] = $begin_date . ' 00:00:00';
			}

			if ( $end_date ) {
				$args['date_query']['before'] = $end_date . ' 23:59:59';
			}
		}

		$query   = new WP_Query( $args );
		$results = array();

		if ( ! empty( $query->posts ) ) {
			foreach ( $query->posts as $id ) {
				$form                  = Ninja_Forms()->form( $form_id );
				$sub                   = $form->get_sub( $id );
				$row                   = $sub->get_field_values();
				$row['date_submitted'] = get_the_date( '', $id );
				$results[]             = $row;
			}
		}

		return $results;
	}

	/**
	 * Format submission data.
	 *
	 * @param  array  $data    Submission data.
	 * @param  int    $form_id Form ID.
	 * @param  string $format  Format type.
	 * @return WP_REST_Response
	 */
	public static function format( $data, $form_id, $format ) {
		switch ( strtolower( $format ) ) {
			case 'xlsx':
				return self::to_xlsx( $data );
			case 'pdf':
				return self::to_pdf( $data, $form_id );
			default:
				return rest_ensure_response( $data );
		}
	}


	/**
	 * Placeholder for XLSX export.
	 *
	 * @param  array $data Data array.
	 * @return WP_REST_Response
	 */
	protected static function to_xlsx( $data ) {
		$data = null;
		return new WP_REST_Response( array( 'error' => 'XLSX format not yet implemented.' ), 501 );
	}

	/**
	 * Placeholder for PDF export.
	 *
	 * @param  array $data    Data array.
	 * @param  int   $form_id Form I D.
	 * @return WP_REST_Response
	 */
	protected static function to_pdf( $data, $form_id ) {
		global $wp_filesystem;
		// Initialize WP_Filesystem if it's not already available.
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			include_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! WP_Filesystem() ) {
			// Handle error: couldn't initialize filesystem.
			return false;
		}

		$submissions = $data;
		if ( empty( $submissions ) ) {
			return false;
		}

		// Get metadata ( label to key map ).
		$form      = \Ninja_Forms()->form( $form_id );
		$fields    = $form ? $form->get_fields() : array();
		$field_map = array();
		foreach ( $fields as $field ) {
			$key   = $field->get_setting( 'key' );
			$label = $field->get_setting( 'label' );
			if ( $key && $label ) {
				$field_map[ $key ] = $label;
			}
		}

		// Include extra fields not in the form definition.
		$extra_fields = array( 'date_submitted', '_form_id', '_seq_num' );
		foreach ( $submissions as $submission ) {
			foreach ( $submission as $key => $value ) {
				if ( ! isset( $field_map[ $key ] ) && ! in_array( $key, $extra_fields, true ) && strpos( $key, '_field_' ) !== 0 ) {
					$field_map[ $key ] = trim( ucwords( str_replace( '_', ' ', ltrim( $key, '_' ) ) ) );

				}
			}
		}
		foreach ( $extra_fields as $key ) {
			$field_map[ $key ] = trim( ucwords( str_replace( '_', ' ', ltrim( $key, '_' ) ) ) );
		}
		unset( $field_map['_form_id'] );
		if ( isset( $field_map['_seq_num'] ) ) {
			$value = $field_map['_seq_num'];
			unset( $field_map['_seq_num'] );

			// Reinsert at the beginning.
			$field_map = array_merge( array( '_seq_num' => $value ), $field_map );
		}

		// Init PDF.
		$pdf = new FPDF( 'P', 'mm', 'A4' );
		$pdf->AddPage();
		$pdf->SetFont( 'Arial', '', 12 );

		foreach ( $submissions as $index => $submission ) {
			foreach ( $field_map as $key => $label ) {
				if ( isset( $submission[ $key ] ) ) {
					$value = maybe_unserialize( $submission[ $key ] );
					if ( is_array( $value ) ) {
						$value = implode( ', ', $value );
					}
					$value = wp_strip_all_tags( str_replace( array( "\n", "\r" ), ' ', $value ) );

					if ( filter_var( $value, FILTER_VALIDATE_URL ) && preg_match( '/\.(jpe?g|png )$/i', $value ) ) {
						$pdf->MultiCell( 0, 8, "{$label}:", 0 );

						// Download image temporarily.
						$ext      = pathinfo( wp_parse_url( $value, PHP_URL_PATH ), PATHINFO_EXTENSION );
						$tmp_file = tempnam( sys_get_temp_dir(), 'pdfimg_' ) . '.' . $ext;

						$response = wp_remote_get( $value );

						if ( is_wp_error( $response )
							|| wp_remote_retrieve_response_code( $response ) !== 200
						) {
							// Handle error: couldn't fetch file.
							return false;
						}

						$contents = wp_remote_retrieve_body( $response );

						$wp_filesystem->put_contents( $tmp_file, $contents, FS_CHMOD_FILE );

						// Insert image at 50mm width ( auto height ), max height 30mm.
						$y_before = $pdf->GetY();
						$pdf->Image( $tmp_file, $pdf->GetX(), $y_before, 50 );
						$pdf->Ln( 35 ); // leave room under image.
						$pdf->Ln( 6 ); // Add 4mm spacing below image.

						// Add clickable URL below the image.
						$pdf->SetTextColor( 0, 0, 255 );
						$pdf->SetFont( '', 'U' ); // Underline.
						$pdf->Write( 6, $value, $value ); // 6 = line height, $value = link target.
						$pdf->Ln( 8 ); // Space after link.
						// Reset text style.
						$pdf->SetTextColor( 0, 0, 0 );
						$pdf->SetFont( '', '' );

						wp_delete_file( $tmp_file );
					} else {
						$pdf->MultiCell( 0, 8, "{$label}: {$value}", 0 );
					}
				}
			}
			if ( $index < count( $submissions ) - 1 ) {
				$pdf->Ln( 4 );
				$pdf->Line( 10, $pdf->GetY(), 200, $pdf->GetY() );
				$pdf->Ln( 4 );
			}
		}

		// Output PDF.
		$pdf->Output( 'I', 'form-submissions.pdf' );
		exit;
	}
}
