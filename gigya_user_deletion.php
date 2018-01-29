<?php
/**
 * Plugin Name: Gigya - User Deletion
 * Plugin URI: http://gigya.com
 * Description: Auxiliary plugin for Gigya – Social Infrastructure, allowing the batch deletion of users based on a CSV. Can also be used independently of Gigya.
 * Version: 1.1
 * Author: Gigya
 * Author URI: http://gigya.com
 * License: GPL2+
 */

define( 'GIGYA_USER_DELETION__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GIGYA_USER_DELETION__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GIGYA_USER_DELETION__PERMISSION_LEVEL', 'manage_options' );
define( 'GIGYA_USER_DELETION__VERSION', '1.1' );
define( 'GIGYA_USER_DELETION__SETTINGS', 'gigya_user_deletion_settings' );
define( 'GIGYA_USER_DELETION__RUN_OPTION', 'gigya_user_deletion_last_run' );
define( 'GIGYA_USER_DELETION__QUEUE', 'gigya_user_deletion_queue' );

add_action( 'admin_action_update', 'on_admin_form_update' );
add_action( 'gigya_user_deletion_cron', 'do_user_deletion_job' );
add_action( 'wp_enqueue_scripts', 'enqueue_gigya_js' );
add_filter( 'cron_schedules', 'get_gigya_cron_schedules' );

require_once GIGYA_USER_DELETION__PLUGIN_DIR . 'vendor/autoload.php';
require_once GIGYA_USER_DELETION__PLUGIN_DIR . 'render.php';
require_once GIGYA_USER_DELETION__PLUGIN_DIR . 'classes/UserDeletion.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

/* Let's get started */
init();

/**
 * Initializes basic plugin functionality
 */
function init() {
	if ( is_admin() )
	{
		/* Loads requirements for the admin settings section */
		require_once GIGYA_USER_DELETION__PLUGIN_DIR . 'admin/GigyaUserDeletionSettings.php';
		do_action( 'wp_enqueue_scripts' );
		new GigyaUserDeletionSettings;
	}
}

function on_admin_form_update() {
	$data = $_POST['gigya_user_deletion_settings'];

	/* Form post-processing */
	$_POST['gigya_user_deletion_settings']['aws_region'] = $_POST['gigya_user_deletion_settings']['aws_region_text'];

	/* Form validation */
	try
	{
		$s3_client = new \Aws\S3\S3Client(
			array(
				'region' => $data['aws_region'],
				'version' => 'latest',
				'credentials' => array(
					'key' => $data['aws_access_key'],
					'secret' => $data['aws_secret_key'],
				),
			)
		);

		$s3_client->listBuckets();
	}
	catch ( \Aws\S3\Exception\S3Exception $e )
	{
		add_settings_error( 'gigya_user_deletion_settings', 'api_validate', 'Error connecting to Amazon S3. Please check the WordPress debug log for more information. Your new S3 details have not been saved.', 'error' );
		$existing_options = get_option( GIGYA_USER_DELETION__SETTINGS );

		$_POST['gigya_user_deletion_settings']['aws_access_key'] = $existing_options['aws_access_key'];
		$_POST['gigya_user_deletion_settings']['aws_secret_key'] = $existing_options['aws_secret_key'];
		$_POST['gigya_user_deletion_settings']['aws_region'] = $existing_options['aws_region'];
	}

	/*
	 * Deletes cron and re-enables it. This way it's possible to change the cron's interval, and prevents from scheduling duplicates
	 * (WP doesn't overwrite a cron even if it has the same name. Instead, it creates a new one).
	 */
	$cron_name = 'gigya_user_deletion_cron';
	wp_clear_scheduled_hook( $cron_name );
	if ( $data['enable_cron'] )
	{
		wp_schedule_event( time(), 'custom', $cron_name );
	}
}

function do_user_deletion_job() {
	$deleted_users = array();
	$failed_users = array();
	$job_failed = false;

	$user_deletion = new UserDeletion;
	$user_deletion->start();
	$files = $user_deletion->getS3FileList();

	if ( is_array( $files ) )
	{
		foreach ( $files as $file )
		{
			if ( ! $job_failed )
			{
				$csv = $user_deletion->getS3FileContents( $file );
				$users = $user_deletion->getUsers( $csv );
				$deleted_users = $user_deletion->deleteUsers( 'gigya', $users, $failed_users );
				if ( ! empty( $users ) and ( ! is_array( $deleted_users ) or empty( $deleted_users ) ) )
					$job_failed = true;
			}
		}
	}
	else
		$job_failed = true;

	$user_deletion->sendEmail( $deleted_users, $failed_users );
	$user_deletion->finish( ! $job_failed );
}

function get_gigya_cron_schedules( $schedules ) {
	$schedules['every_five_seconds'] = array(
		'interval' => 5,
		'display' => __( 'Every five seconds' ),
	);

	$schedules['every_thirty_seconds'] = array(
		'interval' => 30,
		'display' => __( 'Every thirty seconds' ),
	);

	$schedules['every_minute'] = array(
		'interval' => 60,
		'display' => __( 'Every minute' ),
	);

	$schedules['every_two_hours'] = array(
		'interval' => 7200,
		'display' => __( 'Every two hours' ),
	);

	$settings = get_option( GIGYA_USER_DELETION__SETTINGS );
	$schedules['custom'] = array(
		'interval' => ( ! empty( $settings['job_frequency'] ) ) ? $settings['job_frequency'] : 3600,
		'display' => __( 'Custom' ),
	);

	return $schedules;
}

function enqueue_gigya_js() {
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'gigya_user_deletion_admin_js', GIGYA_USER_DELETION__PLUGIN_URL . 'admin/js/gigya_user_deletion_admin.js' );
}