<?php

class UserDeletion
{
	private $settings;
	private $last_successful_run;
	private $logging_options;
	private $date_format;
	private $user_deletion_cron_string;

	public function __construct() {
		$this->settings = get_option( GIGYA_USER_DELETION__SETTINGS );
		$this->last_successful_run = intval( get_option( GIGYA_USER_DELETION__RUN_OPTION ) );

		$this->user_deletion_cron_string = __( 'Gigya user deletion cron' );
		$this->date_format = 'Y-m-d H:i:s';

		$this->logging_options = array( //// TODO: Convert to WP options
										'log_start'          => true,
										'log_file_start'     => true,
										'log_delete_success' => true,
										'log_user_skip'      => true,
										'log_user_not_found' => true,
										'log_delete_failure' => true,
										'log_end'            => true,
		);
	}

	/**
	 * Indicates cron start
	 */
	public function start() {
		if ( $this->logging_options['log_start'] )
			error_log( __( 'Gigya cron started' ) . ': ' . date( $this->date_format ) );
	}

	/**
	 * Indicates cron end
	 *
	 * @param boolean $success_status Whether the cron ended successfully
	 */
	public function finish( $success_status ) {
		if ( $success_status )
		{
			update_option( GIGYA_USER_DELETION__RUN_OPTION, time() );

			if ( $this->logging_options['log_end'] )
				error_log( __( 'Gigya cron finished successfully' ) . ': ' . date( $this->date_format ) );
		}
		else
		{
			error_log( __( 'Gigya cron failed' ) . ': ' . date( $this->date_format ) );
		}
	}

	/**
	 * Gets the file list from Amazon S3
	 *
	 * @return array|false File list containing file names from Amazon
	 */
	public function getS3FileList() {
		$files = array();
		try
		{
			$s3_client = new \Aws\S3\S3Client(
				array(
					'region'      => $this->settings['aws_region'],
					'version'     => 'latest',
					'credentials' => array(
						'key'    => $this->settings['aws_access_key'],
						'secret' => $this->settings['aws_secret_key'],
					),
				)
			);

			/* Works up to 1000 objects! */
			$aws_object_list = $s3_client->listObjects( array(
				'Bucket' => $this->settings['aws_bucket'],
				'Prefix' => $this->settings['aws_directory'],
			) );
			foreach ( $aws_object_list as $key => $object_list )
			{
				if ( $key == 'Contents' )
				{
					foreach ( $object_list as $object )
					{
						/* If last successful run is unknown, or if known take only the files modified after that last run */
						if ( ! $this->last_successful_run or ( $object['LastModified']->getTimestamp() > $this->last_successful_run ) )
						{
							if ( pathinfo( $object['Key'] )['extension'] === 'csv' ) /* PHP 5.4+ */
							{
								$files[] = $object['Key'];
							}
						}
					}
				}
			}
		}
		catch ( Exception $e )
		{
			error_log( 'Error connecting Gigya user deletion to AWS A3 on Get File List: ' . $e->getMessage() . '. Please check your credentials.' );
			return false;
		}

		return $files;
	}

	public function getS3FileContents( $file ) {
		try
		{
			$s3_client = new \Aws\S3\S3Client(
				array(
					'region'      => $this->settings['aws_region'],
					'version'     => 'latest',
					'credentials' => array(
						'key'    => $this->settings['aws_access_key'],
						'secret' => $this->settings['aws_secret_key'],
					),
				)
			);

			$s3_client->getObject( array(
				'Bucket' => $this->settings['aws_bucket'],
				'Key'    => $file,
				'SaveAs' => 'gigya_user_deletion.tmp',
			) );

			$csv_contents = file_get_contents( 'gigya_user_deletion.tmp' );
			if ( file_exists( 'gigya_user_deletion.tmp' ) )
				unlink( 'gigya_user_deletion.tmp' );

			if ( $this->logging_options['log_file_start'] )
				error_log( $this->user_deletion_cron_string . ': processing file ' . $file . ' started.' );
		}
		catch ( Exception $e )
		{
			error_log( 'Error connecting Gigya user deletion to AWS A3 on Get File Contents: ' . $e->getMessage() . '. Please check your credentials.' );
			return false;
		}

		return $csv_contents;
	}

	public function getUsers( $user_csv_string ) {
		$csv_array = ( ! empty( $user_csv_string ) ) ? array_map( 'trim', explode( "\n", $user_csv_string ) ) : array();
		array_shift( $csv_array );

		return array_values( $csv_array );
	}

	/**
	 * Gets a WordPress user ID by Gigya UID
	 *
	 * @param $gigya_uid
	 * @return int|false
	 */
	private function getWordPressIdByGigyaUid( $gigya_uid ) {
		$wp_user = get_users( array(
			'meta_key'   => 'gigya_uid',
			'meta_value' => $gigya_uid,
		) );

		if ( ! empty( $wp_user ) )
			$wp_user = $wp_user[0];

		return ( ! empty( $wp_user ) ) ? intval( $wp_user->ID ) : false;
	}

	/**
	 * @param string $uid_type     ENUM: Can be 'gigya' or 'wordpress'
	 * @param array  $uid_list     List of UIDs to delete
	 * @param array  $failed_users List of UIDs that weren't found in the DB
	 *
	 * @return array
	 */
	public function deleteUsers( $uid_type, $uid_list, &$failed_users = array() ) {
		$delete_type = $this->settings['delete_type'];
		$deleted_users = array();
		$uid_list_assoc = array();

		/* Retrieves WP UIDs from Gigya UIDs if necessary */
		foreach ( $uid_list as $uid )
		{
			if ( $uid_type === 'gigya' )
			{
				$wp_uid = $this->getWordPressIdByGigyaUid( $uid );
				if ( $wp_uid )
					$uid_list_assoc[$wp_uid] = $uid;
				else
				{
					$failed_users[] = $uid;
					if ( $this->logging_options['log_user_not_found'] )
						error_log( $this->user_deletion_cron_string . ': user ' . $uid . ' not found in WordPress' );
				}
			}
			elseif ( $uid_type === 'wordpress' )
			{
				if ( ! empty( get_user_by( 'id', $uid ) ) )
					$uid_list_assoc[$uid] = $uid;
				else
				{
					$failed_users[] = $uid;
					if ( $this->logging_options['log_user_not_found'] )
						error_log( $this->user_deletion_cron_string . ': user ' . $uid . ' not found in WordPress' );
				}
			}
		}

		foreach ( $uid_list_assoc as $wp_uid => $csv_uid )
		{
			if ( apply_filters( 'gigya_pre_delete_user', $wp_uid ) )
			{
				if ( $delete_type === 'soft_delete' )
				{
					if ( /* If soft-delete succeeded, write to the deleted users array. Note: There is an OR here so that if a previous deletion of the same user was botched, it should succeed on this retry. Put AND if you don't need this failover. */
						add_user_meta( $wp_uid, 'is_deleted', 1, true ) and
						add_user_meta( $wp_uid, 'deleted_date', time(), true )
					)
					{
						$deleted_users[] = $csv_uid;
						do_action( 'gigya_on_tag_user_deletion', $wp_uid );
						if ( $this->logging_options['log_delete_success'] )
							error_log( $this->user_deletion_cron_string . ': user ' . $csv_uid . ' deleted' );
					}
					else
					{
						$failed_users[] = $csv_uid;
						if ( $this->logging_options['log_delete_failure'] )
							error_log( $this->user_deletion_cron_string . ': user ' . $csv_uid . ' deletion failed!' );
					}
				}
				elseif ( $delete_type === 'hard_delete' ) /* Completely delete the user */
				{
					if ( wp_delete_user( $wp_uid ) )
					{
						$deleted_users[] = $csv_uid;
						if ( $this->logging_options['log_delete_success'] )
							error_log( $this->user_deletion_cron_string . ': user ' . $csv_uid . ' deleted (total delete!)' );
					}
					else
					{
						$failed_users[] = $csv_uid;
						if ( $this->logging_options['log_delete_failure'] )
							error_log( $this->user_deletion_cron_string . ': user ' . $csv_uid . ' deletion failed' );
					}
				}
			}
			elseif ( $this->logging_options['log_user_skip'] )
				error_log( $this->user_deletion_cron_string . ': user ' . $csv_uid . ' Gigya deletion skipped via custom hook' );
		}

		return $deleted_users;
	}

	public function sendEmail( $uids_deleted, $uids_failed ) {
		$deleted_user_count = count( $uids_deleted );
		$failed_user_count = count( $uids_failed );
		$total_user_count = $deleted_user_count + $failed_user_count;
		if ( $deleted_user_count > 0 and $failed_user_count == 0 )
			$success_type_string = 'finished successfully';
		elseif ( $deleted_user_count > 0 and $failed_user_count > 0 )
			$success_type_string = 'finished with errors';
		else
			$success_type_string = 'failed';

		$email_subject = __( 'Gigya User Deletion Cron Job Completed' );
		$email_body = "Gigya's user deletion cron job has " . $success_type_string . ".\r\n\r\n" .
			"In total, {$deleted_user_count} out of {$total_user_count} users queued were deleted.\r\n\r\n";
		/* Uncomment the following to add actual UID log to email (not recommended for large work loads)
		 *
		 * "Deleted users:\r\n" .
		implode( "\r\n", $uids_deleted ) . "\r\n" .
		"Failed users:\r\n" .
		implode( "\r\n", $uids_failed ); */

		if ( $deleted_user_count > 0 )
			wp_mail( $this->settings['email_on_success'], $email_subject, $email_body );
		else
			wp_mail( $this->settings['email_on_failure'], $email_subject, $email_body );
	}
}