<?php

// Template: Order Form
// Displays the order form template which is to be filled in by an office manager, or any admin user.

/**
 * @global array   $form        The form settings from ZMOF_Forms()->get_order_form_by_id()
 * @global string  $form_id
 * @global string  $custom_classes
 * @global bool    $show_title
 */

// Enqueue download list assets
do_action( 'zm_order_forms/enqueue_scripts' );

// Form data to be used
$form_title = $form['form_title'];
$form_id    = $form['form_id'];
// $send_to    = $form['send_to'];
$locations  = $form['locations'];
$categories = $form['categories']; // {string category_title, string[] products}
$custom_category = $form['custom_category'];

// Get data from previous submission (if validation failed)
$post_data = ZMOF_Forms()->get_post_data();

// Get fields to be filled out by the user
$user_name = '';
$user_email = '';
$user_location = '';

// Get default fields from the user's account, if logged in
$user = new WP_User( get_current_user_id() );

if ( $user->ID > 0 ) {
	$user_name = trim( $user->get('first_name') .' '. $user->get('last_name') );
	if ( ! $user_name ) $user_name = $user->get('display_name');
	
	$user_email = $user->get('user_email');
}

?>
<form action="" method="POST" class="zmof-form">
	
	<?php
	if ( $form_title && $show_title ) {
		echo '<h2>'. esc_html($form_title) .'</h2>';
	}
	?>
	
	<?php
	// Show validation errors from POST (if any)
	$validation_errors = ZMOF_Forms()->get_validation_errors();
	
	// The container is always added, but hidden if there are no errors
	?>
	<div class="zm-validation-errors" <?php if ( ! $validation_errors ) echo 'style="display: none;"'; ?>>
		<h3><?php _e( 'Please correct the following:', 'zm-order-forms' ); ?></h3>
		<ul class="error-list">
			<?php
			if ( $validation_errors ) foreach( $validation_errors as $error ) {
				// $error contains HTML, do not escape
				echo '<li>'. $error .'</li>';
			}
			?>
		</ul>
	</div>
	
	<div class="zmof-section zmof-fields">
		
		<div class="zmof-field field-name">
			<label for="zmof-name"><?php _e( 'Your Name', 'zm-order-forms' ); ?></label>
			<input type="text" name="zmof[name]" id="zmof-name" class="required text" value="<?php echo esc_attr($post_data['name'] ?? $user_name); ?>" required />
		</div>
		
		<div class="zmof-field field-email">
			<label for="zmof-email"><?php _e( 'Your Email', 'zm-order-forms' ); ?></label>
			<input type="email" name="zmof[email]" id="zmof-email" class="required text email" value="<?php echo esc_attr($post_data['email'] ?? $user_email); ?>" required />
		</div>
		
		<?php
		if ( $locations ) {
			?>
			<div class="zmof-field field-location">
				<label for="zmof-location"><?php _e( 'Location', 'zm-order-forms' ); ?></label>
				<select name="zmof[location]" id="zmof-location" class="required" required>
					<option value="">&ndash; Select &ndash;</option>
					<?php
					$selected_location = $post_data['location'] ?? $user_location;
					
					foreach( $locations as $location_name ) {
						$value = $location_name; // could be formatted in future
						$selected = selected( $selected_location, $value, false );
						
						echo '<option value="'. esc_attr($value) .'" '. $selected .'>'. esc_html($location_name) .'</option>';
					}
					?>
				</select>
			</div>
			<?php
		}else{
			?>
			<input type="hidden" value="" name="zmof[location]" id="zmof-location-none">
			<?php
		}
		?>
		
		<div class="zmof-field field-order-notes">
			<label for="zmof-order-notes"><?php _e( 'Order Notes:', 'zm-order-forms' ); ?></label>
			<textarea name="zmof[order_notes]" id="zmof-order-notes" class="text optional" rows="3" maxlength="2500"><?php echo esc_textarea($post_data['order_notes'] ?? ''); ?></textarea>
		</div>
	
	</div>
	
	<div class="zmof-section zmof-category-list">
		<?php
		// Loop through the categories
		foreach ( $categories as $category ) {
			$category_title = $category['category_title'];
			$products       = $category['products'];
			
			$category_slug = sanitize_title_with_dashes( $category_title );
			?>
			<table class="zmof-category-table category-<?php echo esc_attr($category_slug); ?>" data-category-title="<?php echo esc_attr($category_title); ?>">
				
				<thead>
				<tr>
					<th class="col-title" scope="col"><span><?php echo esc_attr($category_title); ?></span></th>
					<th class="col-quantity col-input" scope="col"><span><abbr title="Quantity to order">Qty</abbr></span></th>
					<th class="col-reference-number col-input" scope="col"><span><abbr title="Reference Number. Required if quantity is present.">Ref #</abbr></span></th>
				</tr>
				</thead>
				
				<tbody>
				<?php
				// Loop through the products
				if ( $products ) foreach ( $products as $product_title ) {
					$product_slug = sanitize_title_with_dashes( $product_title );
					
					$id = 'zmof-product-'. $product_slug;
					$name = 'zmof[products]['. $category_title .']['. $product_title .']';
					$value_quantity = $post_data['products'][$category_title][$product_title]['quantity'] ?? '';
					$value_reference_number = $post_data['products'][$category_title][$product_title]['reference_number'] ?? '';
					?>
					<tr class="zmof-product product-<?php echo esc_attr($product_slug); ?>">
						
						<td class="col-title">
							<label for="<?php echo esc_attr($id); ?>-quantity"><?php echo esc_html($product_title); ?></label>
						</td>
						
						<td class="col-quantity col-input">
							<input type="text" name="<?php echo esc_attr($name); ?>[quantity]" id="<?php echo esc_attr($id); ?>-quantity" class="text optional product-quantity" value="<?php echo esc_attr($value_quantity); ?>" />
						</td>
						
						<td class="col-reference-number col-input">
							<input type="text" name="<?php echo esc_attr($name); ?>[reference_number]" id="<?php echo esc_attr($id); ?>-reference-number" class="text optional product-reference-number" value="<?php echo esc_attr($value_reference_number); ?>" />
						</td>
					
					</tr>
					<?php
				}
				?>
				</tbody>
			
			</table>
			<?php
		}
		?>
	</div>
	
	<div class="zmof-section zmof-custom-products">
		<?php
		// The custom product category title is set in the form settings
		$category_title = $custom_category;
		?>
		<div class="zmof-category-list">
			<table class="zmof-category-table" data-category-title="<?php echo esc_attr($category_title); ?>">
				
				<thead>
				<tr>
					<th class="col-title" scope="col"><span><?php echo esc_attr($category_title); ?></span></th>
					<th class="col-quantity col-input" scope="col"><span><abbr title="Quantity to order">Qty</abbr></span></th>
					<th class="col-reference-number col-input" scope="col"><span><abbr title="Reference Number. Required if quantity is present.">Ref #</abbr></span></th>
				</tr>
				</thead>
				
				<tbody>
				<?php
				$custom_products = !empty($post_data['custom_products']) ? $post_data['custom_products'] : array();
				
				// Start with 5 custom products, adding blank ones if needed
				$custom_products = array_pad( $custom_products, 5, array() );
				
				foreach( $custom_products as $i => $c ) {
					$name = 'zmof[custom_products]['. $i .']';
					$id = 'zmof-custom-product-'. $i;
					
					$value_title = $c['title'] ?? '';
					$value_quantity = $c['quantity'] ?? '';
					$value_reference_number = $c['reference_number'] ?? '';
					?>
					<tr class="zmof-product">
						<td class="col-title">
							<label for="<?php echo esc_attr($id); ?>-title" class="screen-reader-text visually-hidden">Product Title</label>
							<input type="text" name="<?php echo esc_attr($name); ?>[title]" id="<?php echo esc_attr($id); ?>-title" class="text optional product-title" value="<?php echo esc_attr($value_title); ?>" />
						</td>
						<td class="col-quantity col-input">
							<label for="<?php echo esc_attr($id); ?>-quantity" class="screen-reader-text visually-hidden">Quantity</label>
							<input type="text" name="<?php echo esc_attr($name); ?>[quantity]" id="<?php echo esc_attr($id); ?>-quantity" class="text optional product-quantity" value="<?php echo esc_attr($value_quantity); ?>" />
						</td>
						<td class="col-reference-number col-input">
							<label for="<?php echo esc_attr($id); ?>-reference-number" class="screen-reader-text visually-hidden">Reference Number</label>
							<input type="text" name="<?php echo esc_attr($name); ?>[reference_number]" id="<?php echo esc_attr($id); ?>-reference-number" class="text optional product-reference-number" value="<?php echo esc_attr($value_reference_number); ?>" />
						</td>
					</tr>
					<?php
				}
				?>
				</tbody>
				
				<tfoot>
				<tr class="add-product-row">
					<td colspan="3">
						<a href="#" class="add-product"><span class="plus">+</span> Add Product</a>
					</td>
				</tr>
				</tfoot>
			
			</table>
		</div>
	
	</div>
	
	<div class="zmof-section zmof-submit">
		<input type="submit" value="Continue" class="button button-primary">
		
		<input type="hidden" name="zmof[action]" value="submit-order">
		<input type="hidden" name="zmof[form_id]" value="<?php echo esc_attr($form_id); ?>">
	</div>

</form>