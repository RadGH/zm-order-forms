<?php

// Template: Confirm Order Form
// Includes hidden form data that was previously submitted, allowing you to either:
// 1. Confirm and submit the order
// 2. Go back and edit the order

$data = ZMOF_Forms()->get_validated_form_data();
if ( ! $data ) wp_die('Order form submission is not available or could not be validated.');

$form = ZMOF_Forms()->get_order_form_by_id( $data['form_id'] );
if ( ! $form ) wp_die('Could not locate the order form that was submitted.');

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