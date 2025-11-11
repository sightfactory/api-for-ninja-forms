<?php
/**
 * Provides custom REST API endpoints to retrieve Ninja Forms submissions
 *
 * @package API for Ninja Forms
 *
 * Plugin Name: API for Ninja Forms
 * Description: Provides custom REST API endpoints to retrieve Ninja Forms submissions and field metadata with API key authentication and multiple export formats.
 * Version: 1.0.1
 * Author: Sightfactory
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: api-for-ninja-forms
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Requires Plugins: ninja-forms
 * Third Party:       Setasign/FPDF
 * Third Party URI:   https://github.com/Setasign/FPDF
 * Third Party License: FPDF License (compatible with MIT/BSD-style)
 * Third Party License URI: https://github.com/Setasign/FPDF?tab=License-1-ov-file#readme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define constants.

define( 'VWPNFSA_SUB_API_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'VWPNFSA_SUB_API_URL', plugin_dir_url( __FILE__ ) );

require VWPNFSA_SUB_API_PATH . '/vendor/autoload.php';
use setasign\Fpdi\Fpdf\Fpdf;
// Include necessary files.
require_once VWPNFSA_SUB_API_PATH . 'includes/class-vwpnfsa-rest-controller.php';
require_once VWPNFSA_SUB_API_PATH . 'includes/class-vwpnfsa-auth.php';
require_once VWPNFSA_SUB_API_PATH . 'includes/class-vwpnfsa-export.php';
require_once VWPNFSA_SUB_API_PATH . 'includes/class-vwpnfsa-settings.php';

/**
 * Register REST routes.
 */
function vwpnfsa_register_routes() {
	$controller = new VWPNFSA_REST_Controller();
	$controller->register_routes();
}
add_action( 'rest_api_init', 'vwpnfsa_register_routes' );

/**
 * Add settings menu.
 */
function vwpnfsa_register_settings_menu() {
	$settings = new VWPNFSA_Settings();
	$settings->register_menu();
}
add_action( 'admin_menu', 'vwpnfsa_register_settings_menu' );
