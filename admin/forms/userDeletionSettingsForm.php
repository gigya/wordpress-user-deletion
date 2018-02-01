<?php
function render_gigya_user_deletion_setting( $setting, $backup_setting = null ) { /* Does not support multisite! */
	$value = render_setting( $setting, GIGYA_USER_DELETION__SETTINGS );
	if ( ! empty( $value ) or empty( $backup_setting ) )
		return $value;
	else
		return render_setting( $backup_setting, GIGYA_USER_DELETION__SETTINGS );
}

function userDeletionSettingsForm() {
	$form = array();

	$form['enable_cron'] = array(
		'type' => 'checkbox',
		'label' => 'Enable',
		'value' => render_gigya_user_deletion_setting( 'enable_cron' ),
	);

	$form['delete_type'] = array(
		'type' => 'select',
		'options' => array(
			'soft_delete' => __( 'Tag deleted user' ),
			'hard_delete' => __( 'Full user deletion' ),
		),
		'label' => __( 'Delete type' ),
		'value' => render_gigya_user_deletion_setting( 'delete_type' ),
	);

	$form['job_frequency'] = array(
		'type' => 'text',
		'size' => 10,
		'label' => __( 'Job frequency' ),
		'value' => render_gigya_user_deletion_setting( 'job_frequency' ),
		'markup' => __( 'seconds' ),
		'desc' => 'This setting relies on the WordPress cron mechanism.',
	);

	$form['email_on_success'] = array(
		'type' => 'text',
		'label' => __( 'Email on success' ),
		'value' => render_gigya_user_deletion_setting( 'email_on_success' ),
	);

	$form['email_on_failure'] = array(
		'type' => 'text',
		'label' => __( 'Email on failure' ),
		'value' => render_gigya_user_deletion_setting( 'email_on_failure' ),
	);

	$form['amazon_s3_header'] = array(
		'markup' => '<h4>Amazon S3 Settings</h4>',
	);

	$form['aws_access_key'] = array(
		'type' => 'text',
		'label' => __( 'Access key' ),
		'value' => render_gigya_user_deletion_setting( 'aws_access_key' ),
	);

	$form['aws_secret_key'] = array(
		'type' => 'password',
		'label' => __( 'Secret key' ),
		'value' => render_gigya_user_deletion_setting( 'aws_secret_key' ),
	);

	$aws_region = render_gigya_user_deletion_setting( 'aws_region' );
	$aws_region_other = render_gigya_user_deletion_setting( 'aws_region_text' );
	$form['aws_region'] = array(
		'type' => 'select',
		'options' => array(
			'us-east-1' => 'US East (N. Virginia)',
			'us-east-2' => 'US East (Ohio)',
			'us-west-1' => 'US West (N. California)',
			'us-west-2' => 'US West (Oregon)',
			'ca-central-1' => 'Canada (Central)',
			'ap-south-1' => 'Asia-Pacific (Mumbai)',
			'ap-northeast-2' => 'Asia-Pacific (Seoul)',
			'ap-southeast-1' => 'Asia-Pacific (Singapore)',
			'ap-southeast-2' => 'Asia-Pacific (Sydney)',
			'ap-northeast-1' => 'Asia-Pacific (Tokyo)',
			'cn-north-1' => 'China North (Beijing)',
			'cn-northwest-1' => 'China Northwest (Ningxia)',
			'eu-central-1' => 'EU Central (Frankfurt)',
			'eu-west-1' => 'EU West (Ireland)',
			'eu-west-2' => 'EU West (London)',
			'eu-west-3' => 'EU West (Paris)',
			'sa-east-1' => 'South American (São Paulo)',
			'other' => 'Other',
		),
		'label' => __( 'Server / region' ),
	);
	$form['aws_region']['value'] = ( in_array( $aws_region, array_keys( $form['aws_region']['options'] ) ) ) ? $aws_region : 'other';

	$form['aws_region_text'] = array(
		'type' => 'text',
		'readonly' => true,
		'size' => 26,
		'value' => $aws_region_other,
	);

	$form['aws_bucket'] = array(
		'type' => 'text',
		'label' => __( 'Bucket name' ),
		'value' => render_gigya_user_deletion_setting( 'aws_bucket' ),
	);

	$form['aws_directory'] = array(
		'type' => 'text',
		'label' => __( 'Object key prefix (folder)' ),
		'value' => render_gigya_user_deletion_setting( 'aws_directory' ),
	);

	echo render_form_elements( $form, GIGYA_USER_DELETION__SETTINGS );
}