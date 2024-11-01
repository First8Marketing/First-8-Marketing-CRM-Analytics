<?php

/**
 * Plugin Name: First 8 Marketing - Intelligent CRM and Customer Journey Analytics
 * Plugin URI: https://first8marketing.com
 * Description: Turn your WordPress into lead-driver, sale-mover & churn-killer. Motivate your high paying customers until they buy.
 * Version: 1.0
 * Author: iskandarsulaili
 * Author URI: https://iskandarsulaili.com
 * Text Domain: first-8-marketing-crm-analytics
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package First_8_Marketing
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * First 8 Marketing CRM Analytics Plugin Initialization Class
 */
final class First_8_Marketing_Init {

	/**
	 * Plugin version
	 */
	const VERSION = '1.0';

	/**
	 * Initialize and load the plugin.
	 */
	public static function init() {
		// Define plugin constants.
		self::define_constants();

		// Load the main plugin class.
		require_once __DIR__ . '/src/class-first-8-marketing.php';

		// Instantiate the plugin class.
		First_8_Marketing::instance();
	}

	/**
	 * Define plugin constants.
	 */
	private static function define_constants() {
		define( 'FIRST_8_MARKETING_DIR', __DIR__ );
		define( 'FIRST_8_MARKETING_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		define( 'FIRST_8_MARKETING_FILE', __FILE__ );
		define( 'FIRST_8_MARKETING_VERSION', self::VERSION );
		define( 'FIRST_8_MARKETING_NAME', 'First 8 Marketing CRM Analytics' );
		define( 'FIRST_8_MARKETING_CAMEL_CASE', 'First8Marketing' );
		define( 'FIRST_8_MARKETING_URL', 'https://app.first8marketing.com' );
		define( 'FIRST_8_MARKETING_API_URL', 'https://beta-api.first8marketing.com' );
		define( 'FIRST_8_MARKETING_SCRAP_API_URL', 'https://tracku.first8.marketing' );
	}
}

// Initialize the plugin.
First_8_Marketing_Init::init();