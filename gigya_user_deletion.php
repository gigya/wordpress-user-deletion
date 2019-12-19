<?php
/**
 * Plugin Name: SAP Customer Data Cloud - User Deletion
 * Plugin URI: http://gigya.com
 * Description: Auxiliary plugin for SAP Customer Data Cloud, allowing the batch deletion of users based on a CSV. Can also be used independently of SAP Customer Data Cloud.
 * Version: 1.2
 * Author: SAP SE
 * Author URI: http://gigya.com
 * License: GPL2+
 */

define( 'GIGYA_USER_DELETION', 'gigya_user_deletion' );
define( 'GIGYA_USER_DELETION__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GIGYA_USER_DELETION__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GIGYA_USER_DELETION__PERMISSION_LEVEL', 'manage_options' );
define( 'GIGYA_USER_DELETION__VERSION', '1.1' );
define( 'GIGYA_USER_DELETION__SETTINGS', GIGYA_USER_DELETION . '_settings' );
define( 'GIGYA_USER_DELETION__RUN_OPTION', GIGYA_USER_DELETION . '_last_run' );
define( 'GIGYA_USER_DELETION__QUEUE', GIGYA_USER_DELETION . '_queue' );

add_action( 'admin_action_update', 'on_admin_form_update' );
add_action( 'gigya_user_deletion_cron', 'do_user_deletion_job' );
add_action( 'wp_enqueue_scripts', 'enqueue_gigya_js' );
add_filter( 'cron_schedules', 'get_gigya_cron_schedules' );

require_once GIGYA_USER_DELETION__PLUGIN_DIR . 'vendor/autoload.php';
require_once GIGYA_USER_DELETION__PLUGIN_DIR . 'render.php';
require_once GIGYA_USER_DELETION__PLUGIN_DIR . 'classes/UserDeletion.php';
require_once GIGYA_USER_DELETION__PLUGIN_DIR . 'classes/UserDeletionHelper.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

/**
 * Register activation hook
 */
register_activation_hook( __FILE__, 'gigyaUserDeletionActivationHook' );
function gigyaUserDeletionActivationHook() {
	require_once GIGYA_USER_DELETION__PLUGIN_DIR . 'install.php';
	$install = new GigyaUserDeletionInstall();
	$install->init();
}

/**
 * Let's get started
 */
gigya_user_deletion_init();

/**
 * Initializes basic plugin functionality
 */
function gigya_user_deletion_init() {
	if ( is_admin() )
	{
		/* Loads requirements for the admin settings section */
		require_once GIGYA_USER_DELETION__PLUGIN_DIR . 'admin/GigyaUserDeletionSettings.php';
		do_action( 'wp_enqueue_scripts' );
		new GigyaUserDeletionSettings;
	}
}

function on_admin_form_update() {
	if (!empty($_POST['gigya_user_deletion_settings']))
	{
		$data = $_POST['gigya_user_deletion_settings'];

		/* Form post-processing */
		$user_deletion_helper = new UserDeletionHelper();
		$_POST['gigya_user_deletion_settings']['aws_region'] = $_POST['gigya_user_deletion_settings']['aws_region_text'];
		if ( ! $data['aws_secret_key'] ) {
			if ( is_multisite() ) {
				$options = get_blog_option( 1, GIGYA_USER_DELETION__SETTINGS );
			} else {
				$options = get_option( GIGYA_USER_DELETION__SETTINGS );
			}
			$data['aws_secret_key'] = $user_deletion_helper::decrypt($options['aws_secret_key'], SECURE_AUTH_KEY);
			$_POST['gigya_user_deletion_settings']['aws_secret_key'] = $options['aws_secret_key'];
		}
		else
		{
			$_POST['gigya_user_deletion_settings']['aws_secret_key'] = $user_deletion_helper::encrypt($data['aws_secret_key'], SECURE_AUTH_KEY);
		}

		/* Form validation */
		if ( $data['aws_region'] == 'other' ) {
			$data['aws_region'] = $data['aws_region_text'];
		}
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

			$s3_client->listObjects( array(
				'Bucket' => $data['aws_bucket'],
				'Prefix' => $data['aws_directory'],
			) );
		}
		catch ( \Aws\S3\Exception\S3Exception $e )
		{
			add_settings_error( 'gigya_user_deletion_settings', 'api_validate', 'Error connecting to Amazon S3. Please check the WordPress debug log for more information. Your new S3 details have not been saved.', 'error' );
			$existing_options = get_option( GIGYA_USER_DELETION__SETTINGS );

			$_POST['gigya_user_deletion_settings']['aws_access_key'] = $existing_options['aws_access_key'];
			$_POST['gigya_user_deletion_settings']['aws_secret_key'] = $existing_options['aws_secret_key'];
			$_POST['gigya_user_deletion_settings']['aws_region'] = $existing_options['aws_region'];
			$_POST['gigya_user_deletion_settings']['aws_region_text'] = $existing_options['aws_region_text'];
		}

		/*
		 * Deletes cron and re-enables it. This way it's possible to change the cron's interval, and prevents from scheduling duplicates
		 * (WP doesn't overwrite a cron even if it has the same name. Instead, it creates a new one).
		 */
		$cron_name = 'gigya_user_deletion_cron';
		wp_clear_scheduled_hook( $cron_name );
		if ( $data['enable_cron'] ) {
			wp_schedule_event( time(), 'gigya_user_deletion_custom', $cron_name );
		}
	}
}

function do_user_deletion_job() {
	global $wpdb;

	$deleted_users             = array();
	$failed_users              = array();
	$job_failed                = true;
	$gigya_user_deletion_table = $wpdb->prefix . GIGYA_USER_DELETION;

	$user_deletion = new UserDeletion;
	$user_deletion->start();
	$files = $user_deletion->getS3FileList();

	$file_count = 0;
	$failed_count = 0;

	if ( is_array( $files ) ) {
		/* Get only the files that have not been processed */
		if ( count( $files ) > 0 ) {
			$query = $wpdb->prepare( "SELECT * FROM {$gigya_user_deletion_table} WHERE filename IN (" . implode( ', ', array_fill( 0, count( $files ), '%s' ) ) . ")", $files );
			$files = array_diff( $files, array_column( $wpdb->get_results( $query, ARRAY_A ), 'filename' ) );
			if ( ( $file_count = count( $files ) ) === 0 ) {
				$job_failed = false;
			}
		} else {
			$job_failed = false;
		}

		foreach ( $files as $file ) {
			$csv           = $user_deletion->getS3FileContents( $file );
			$users         = $user_deletion->getUsers( $csv );
			$deleted_users = $user_deletion->deleteUsers( 'gigya', $users, $failed_users );

			if ( $csv === false or ! empty( $users ) and ( ! is_array( $deleted_users ) or empty( $deleted_users ) ) ) {
				$failed_count++;
			} else /* Job succeeded or succeeded with errors */ {
				$job_failed = false;

				/* Mark file as processed */
				$wpdb->insert( $gigya_user_deletion_table, array(
					'filename'       => $file,
					'time_processed' => time(),
				) );
			}
		}
	} else {
		$job_failed = false;
	}

	$user_deletion->sendEmail( $deleted_users, $failed_users );
	$user_deletion->finish( ! $job_failed, $file_count, $failed_count );
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
	$schedules['gigya_user_deletion_custom'] = array(
		'interval' => ( ! empty( $settings['job_frequency'] ) ) ? $settings['job_frequency'] : 3600,
		'display' => __( 'Custom' ),
	);

	return $schedules;
}

function enqueue_gigya_js() {
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'gigya_user_deletion_admin_js', GIGYA_USER_DELETION__PLUGIN_URL . 'admin/js/gigya_user_deletion_admin.js' );
}