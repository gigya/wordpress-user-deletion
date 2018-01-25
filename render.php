<?php
/**
 * Renders wrapper for admin settings form.
 *
 * @param string $slug
 * @return string
 */
function render_settings_form( $slug ) {
	settings_errors();

	ob_start();

	echo '<form class="gigya-user-deletion-settings" action="options.php" method="post">';
	echo '<input type="hidden" name="action" value="gigya_user_deletion_settings_submit">';

	settings_fields( $slug . '-group' );
	do_settings_sections( $slug );
	submit_button();

	echo '</form>';

	return ob_get_clean();
}

/**
 * Render entire settings form, but without the wrapper (<form> tag etc.)
 *
 * @param array       $form
 * @param string|null $name_prefix
 * @return string
 */
function render_form_elements( $form, $name_prefix = null ) {
	$output = '';

	foreach ( $form as $id => $element )
	{
		if ( empty( $element['type'] ) || $element['type'] == 'markup' )
		{
			$output .= $element['markup'];
		}
		else
		{
			if ( empty( $element['name'] ) )
			{
				if ( ! empty( $name_prefix ) )
				{
					/*
					 * If one wants to POST all the form's fields as one array instead of separate fields,
					 * one may use the $name_prefix parameter, which should probably be the slug for the relevant settings page.
					 */
					$element['name'] = $name_prefix . '[' . $id . ']';
				}
				else
				{
					/* Otherwise the element name is just its ID */
					$element['name'] = $id;
				}
			}

			/* Add the ID value to the array */
			$element['id'] = $id;

			/* Render each element */
			$output .= render_template( 'admin/tpl/formElement_' . $element['type'] . '.tpl', $element );
		}
	}

	return $output;
}

/**
 * Renders a template file
 *
 * @param string $template_file
 * @param array  $template_vars
 * @return string
 */
function render_template( $template_file, $template_vars = array() ) {
	extract( $template_vars, EXTR_SKIP );
	ob_start();
	include GIGYA_USER_DELETION__PLUGIN_DIR . $template_file;

	return ob_get_clean();
}

/**
 * Outputs the value of a given settings from wp_options.
 * Setting $settings is *highly recommended*! Because this function is usually used many times in each form, leaving $settings empty will send a query on every rendered element.
 *
 * @param string            $setting
 * @param object|array|null $settings
 * @return string|null
 */
function render_setting( $setting, $settings = null ) {
	if ( ! $settings or ! is_array( $settings ) )
		$settings = get_option( GIGYA_USER_DELETION__SETTINGS );
	if ( is_object( $settings ) )
		$settings = (array)$settings;

	return ( ! empty( $settings[$setting] ) ? $settings[$setting] : null );
}