<div class="row select <?php echo isset($class) ? $class : ''; ?>">
	<label for="gigya_<?php echo $id; ?>"><?php echo $label; ?></label>
	<select id="gigya_<?php echo $id; ?>" name="<?php echo $name ?>">
		<?php foreach ( $options as $key => $option ) : ?>
		<option value="<?php echo $key; ?>"
		<?php if ( $value == $key ) echo ' selected="true"'; ?>><?php echo $option; ?></option>
		<?php endforeach ?>
	</select>
	<?php if ( isset($desc) ): ?>
	<small><?php echo $desc; ?></small>
	<?php endif; ?>
	<?php
		if ( isset($markup) ):
			echo $markup;
		endif;
	?>
</div>