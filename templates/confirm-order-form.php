<?php

// Template: Confirm Order Form
// Includes hidden form data that was previously submitted, allowing you to either:
// 1. Confirm and submit the order
// 2. Go back and edit the order

/**
 * @global array $data The form data that was submitted.
 *                     If using a block, the block settings are relayed through the shortcode.
 * @global array $form The form settings from ZMOF_Forms()->get_order_form_by_id()
 */

// Remove the action from the submitted data, to avoid confusion
unset( $data['action'] );

$form_id       = $form['form_id'];
$form_data_str = json_encode( $data );

?>
<div class="zm-confirm-order">
<form action="" method="POST">
	<div class="zmof-section zmof-submit">
		<button type="submit" name="zmof[submit]" value="finalize" class="button button-primary">Submit</button>
		<button type="submit" name="zmof[submit]" value="cancel" class="button button-secondary">Edit</button>
		<input type="hidden" name="zmof[form_data]" value="<?php echo esc_attr($form_data_str); ?>">
		<input type="hidden" name="zmof[action]" value="finalize-order">
	</div>
</form>
</div>