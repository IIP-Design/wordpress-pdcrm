<?php

class CHIEF_SFC_Export {
	public static function init() {
		add_action( 'admin_post_sfc_export', array( __CLASS__, 'export' ) );
		add_action( 'admin_post_nopriv_sfc_export', array( __CLASS__, 'export' ) );
	}

	// json decoder to print the cells
	public static function fc_json_decode( $json, $arr = false ) {
		$result = json_decode( $json, $arr );
		if ( $result ) {
			foreach ( $result as &$val ) {
				if ( is_string( $val ) ) {
					$nested = CHIEF_SFC_Export::fc_json_decode( $val, $arr );
					if ( $nested ) {
						$val = $nested;
					}
				}
			}
			return $result;
		}
		return $json;
	}

	public static function export() {
		global $wpdb;
		if ( !current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}
		$show_all = (array_key_exists( 'show_all', $_GET ) && $_GET['show_all']) ? 1 : 0;

		// $query = "SELECT * FROM {$wpdb->prefix}form_capture_data " . ($show_all ? '' : ' WHERE fc_failure = 1');
		$query = "SELECT * FROM {$wpdb->prefix}form_capture_data WHERE fc_submission_date >= DATE_SUB(NOW(), INTERVAL " . $_GET['month'] . " MONTH) " . (!$show_all ? ' AND fc_failure = 1' : '' );

		$results = $wpdb->get_results( $query );

		$output_filename = 'Form_Capture_Errors.csv';

		// Set CSV header info
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $output_filename );
		header( 'Content-Filename: ' . $output_filename );
		header( 'Expires: 0' );
		header( 'Pragma: no-cache' );

		$output_handle = fopen( 'php://output', 'w' );

		// Insert header row
		fputcsv( $output_handle, ['ID', 'Form ID', 'Submission ID', 'Request', 'Response', 'Date', 'Failure'] );
		try {
			foreach ( $results as $result ) {
				$request_data = CHIEF_SFC_Export::fc_json_decode( $result->fc_request_data, true );
				if ( $request_data && is_array($request_data) && array_key_exists( 'body', $request_data ) ) {
					$request_data = $request_data['body'];
				}
				$response = CHIEF_SFC_Export::fc_json_decode( $result->fc_response, true );
				if ( $response && is_array($response) && array_key_exists( 'response', $response ) ) {
					$response = $response['response'];
				}
				fputcsv( $output_handle, [
					$result->fc_id,
					$result->fc_form_id,
					$result->fc_submission_id,
					print_r( $request_data, 1 ),
					print_r( $response, 1 ),
					$result->fc_submission_date,
					($result->fc_failure ? 1 : 0)
				] );
			}
		} catch (Exception $e) {
			wp_die($e, 500);
		}

		// Close output file stream
		fclose( $output_handle );
		exit;
	}
}