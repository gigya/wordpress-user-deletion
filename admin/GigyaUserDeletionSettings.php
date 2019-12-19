<?php

class GigyaUserDeletionSettings
{
	private $function = 'userDeletionSettingsForm';
	private $slug = GIGYA_USER_DELETION__SETTINGS;
	private $title = 'SAP Customer Data Cloud User Deletion';
	private $title_short = 'User Deletion';

	public function __construct() {
		wp_enqueue_style( 'gigya_user_deletion_admin_css', GIGYA_USER_DELETION__PLUGIN_URL . 'admin/styles/gigya_user_deletion_admin.css' );

		add_action( 'admin_init', array( $this, 'adminInit' ) );
		add_action( 'admin_menu', array( $this, 'adminMenu' ), 11 );
	}

	/**
	 * Initializes admin settings page
	 */
	public function adminInit() {
		$option_group = $this->slug . '-group';
		add_settings_section( $this->slug, $this->title, $this->function, $this->slug );

		/* Add a validate function to this class for additional form manipulation/validation capabilities */
		register_setting( $option_group, $this->slug, array( $this, 'validate' ) );
	}

	/**
	 * Initializes admin menu option for this plugin.
	 * There are two options: Either the core SAP CDC plugin is installed/enabled, which puts this plugin as a submenu item, or this plugin is standalone, which gives it its own menu item
	 */
	public function adminMenu() {
		require_once GIGYA_USER_DELETION__PLUGIN_DIR . 'admin/forms/' . $this->function . '.php';

		if ( current_user_can( GIGYA_USER_DELETION__PERMISSION_LEVEL ) )
		{
			if ( class_exists( 'GigyaSettings' ) ) /* If SAP CDC core plugin is installed and active */
			{
				add_submenu_page( 'gigya_global_settings', /* SAP CDC core plugin slug */
								  __( $this->title ),
								  __( $this->title_short ),
								  GIGYA_USER_DELETION__PERMISSION_LEVEL,
								  $this->slug,
								  array( $this, 'adminPage' )
				);
			}
			else /* If used as a standalone plugin */
			{
				add_menu_page( __( $this->title ),
							   __( $this->title ),
							   GIGYA_USER_DELETION__PERMISSION_LEVEL,
							   $this->slug,
							   array( $this, 'adminPage' ),
							   GIGYA_USER_DELETION__PLUGIN_URL . 'admin/images/favicon_28px.png',
							   '70.1'
				);
			}
		}
	}

	/**
	 * Initializes admin settings front-end
	 */
	public function adminPage() {
		echo render_template( '/admin/tpl/adminSettings_header.tpl' );

		echo render_settings_form( $this->slug );
	}
}