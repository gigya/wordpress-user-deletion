<div class="row text-field <?php echo (isset($class)) ? $class : ''; ?>">
	<?php if (isset($label)): ?>
		<label for="gigya_<?php echo $id; ?>"><?php echo $label; ?></label>
	<?php endif; ?>
	<input type="text" size="<?php echo (isset($size)) ? (string)$size : '60'; ?>"
		   class="<?php echo (isset($subclass)) ? $subclass : 'input-xlarge'; ?>" value="<?php echo $value; ?>"
		   id="gigya_<?php echo $id; ?>" name="<?php echo $name ?>"
			<?php echo (!empty($disabled)) ? 'disabled' : ''; ?>
			<?php echo (!empty($readonly)) ? 'readonly' : ''; ?>
	/>
	<?php
		if ( isset($markup) ):
			echo $markup;
		endif;
	?>
	<br />
	<?php if ( ! empty( $desc ) ): ?>
		<small><?php echo $desc; ?></small>
	<?php endif; ?>
</div>