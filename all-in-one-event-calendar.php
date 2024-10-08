<?php
/**
 * Plugin Name: All-in-One Event Calendar CBDC mod
 * Plugin URI: https://wordpress.org/plugins/all-in-one-event-calendar/
 * Description: A calendar system with month, week, day, agenda views, upcoming events widget, color-coded categories, recurrence, and import/export of .ics feeds.
 * Author: Time.ly Network Inc. & CBDC
 * Author URI: https://github.com/Chicago-Barn-Dance-Company/all-in-one-event-calendar
 * Version: 3.0.1
 * Text Domain: all-in-one-event-calendar
 * Domain Path: /language
 * Requires PHP: 7.2
 */

/* check database exists to identify old users*/
global $wpdb;
$ai1ec_base_dir = dirname( __FILE__ );

if( $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ai1ec_events'") ) {
	$ai1ec_base_url = plugins_url( '', __FILE__ );

	$ai1ec_config_path = $ai1ec_base_dir . DIRECTORY_SEPARATOR . 'app' .
		DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;

// Include configuration files and initiate global constants as they are used
// By the error/exception handler too.
	foreach ( array( 'constants-local.php', 'constants.php' ) as $file ) {
		if ( is_file( $ai1ec_config_path . $file ) ) {
			require_once $ai1ec_config_path . $file;
		}
	}

	if ( ! function_exists( 'ai1ec_initiate_constants' ) ) {
		throw new Ai1ec_Exception(
			'No constant file was found.'
		);
	}
	ai1ec_initiate_constants( $ai1ec_base_dir, $ai1ec_base_url );

	require $ai1ec_base_dir . DIRECTORY_SEPARATOR . 'lib' .
		DIRECTORY_SEPARATOR . 'exception' . DIRECTORY_SEPARATOR . 'ai1ec.php';
	require $ai1ec_base_dir . DIRECTORY_SEPARATOR . 'lib' .
		DIRECTORY_SEPARATOR . 'exception' . DIRECTORY_SEPARATOR . 'error.php';
	require $ai1ec_base_dir . DIRECTORY_SEPARATOR . 'lib' .
		DIRECTORY_SEPARATOR . 'exception' . DIRECTORY_SEPARATOR . 'handler.php';
	require $ai1ec_base_dir . DIRECTORY_SEPARATOR . 'lib' .
		DIRECTORY_SEPARATOR . 'http' . DIRECTORY_SEPARATOR . 'response' .
		DIRECTORY_SEPARATOR . 'helper.php';
	$ai1ec_exception_handler = new Ai1ec_Exception_Handler(
		'Ai1ec_Exception',
		'Ai1ec_Error_Exception'
	);


// if the user clicked the link to reactivate the plugin
	if ( isset( $_GET[Ai1ec_Exception_Handler::DB_REACTIVATE_PLUGIN] ) ) {
		$ai1ec_exception_handler->reactivate_plugin();
	}
	$soft_disable_message = $ai1ec_exception_handler->get_disabled_message();
	if ( false !== $soft_disable_message ) {
		return $ai1ec_exception_handler->show_notices( $soft_disable_message );
	}

	$prev_er_handler = set_error_handler(
		array( $ai1ec_exception_handler, 'handle_error' )
	);
	$prev_ex_handler = set_exception_handler(
		array( $ai1ec_exception_handler, 'handle_exception' )
	);
	$ai1ec_exception_handler->set_prev_er_handler( $prev_er_handler );
	$ai1ec_exception_handler->set_prev_ex_handler( $prev_ex_handler );

// Regular startup sequence starts here

	require $ai1ec_base_dir . DIRECTORY_SEPARATOR . 'lib' .
		DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'loader.php';

	require $ai1ec_base_dir . DIRECTORY_SEPARATOR . 'lib' .
		DIRECTORY_SEPARATOR . 'global-functions.php';

	require $ai1ec_base_dir . DIRECTORY_SEPARATOR . 'lib' .
		DIRECTORY_SEPARATOR . 'iCal' . DIRECTORY_SEPARATOR . 'iCalcreator-2.26.9' . DIRECTORY_SEPARATOR . 'autoload.php';

	require $ai1ec_base_dir . DIRECTORY_SEPARATOR . 'app' .
		DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'extension.php';

	require $ai1ec_base_dir . DIRECTORY_SEPARATOR . 'app' .
		DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'extension-license.php';

	$ai1ec_loader = new Ai1ec_Loader( $ai1ec_base_dir );
	@ini_set( 'unserialize_callback_func', 'spl_autoload_call' );
	spl_autoload_register( array( $ai1ec_loader, 'load' ) );

	$ai1ec_front_controller = new Ai1ec_Front_Controller();
	$ai1ec_front_controller->initialize( $ai1ec_loader );
} else {
	// Include new features for new users or users that doesn't have any event created
	require_once $ai1ec_base_dir . '/apiki/timely.php';
	$inputs = filter_input_array( INPUT_GET );

	if ( isset( $inputs['post_type'] ) && $inputs['post_type'] === 'ai1ec_event' ) {
		require_once( ABSPATH . 'wp-includes/pluggable.php' );
		set_transient( 'timely_update_message', true );

		if ( wp_safe_redirect( get_admin_url() . 'admin.php?page=timely_signin' ) ) {
			exit;
		}
	}
}
