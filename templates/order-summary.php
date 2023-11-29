<?php

// Template: Order Summary
// Displays a table summarizing the order form that was submitted.
// The table can be used in an email, and uses many inline styles in order to do so.

/**
 * @global array $data The form data that was submitted.
 *                     If using a block, the block settings are relayed through the shortcode.
 * @global array $form The form settings from ZMOF_Forms()->get_order_form_by_id()
 */

// Combine predefined and custom products into one array:
// [category_title][] = array( title, quantity, reference_number )
$product_lists = array();

// 1. Add predefined products
if ( $data['products'] ) foreach( $data['products'] as $category_title => $c ) {
	$product_lists[$category_title] = array();
	
	if ( $c ) foreach( $c as $product_title => $p ) {
		$product_lists[$category_title][] = array(
			'title' => $product_title,
			'quantity' => $p['quantity'],
			'reference_number' => $p['reference_number'],
		);
	}
}

// 2. Add custom products
if ( $data['custom_products'] ) {
	$category_title = $form['custom_category'];
	foreach( $data['custom_products'] as $c ) {
		$product_lists[$category_title][] = array(
			'title' => $c['title'],
			'quantity' => $c['quantity'],
			'reference_number' => $c['reference_number'],
		);
	}
}

// Capture the output to apply inline CSS styles (for email)
ob_start();
?>

<table class="zm-order-summary">
	<tbody>
	
		<tr class="field-row">
			<th class="field-title">Location:</th>
			<td class="field-value"><?php echo esc_html($data['location']); ?></td>
		</tr>
		
		<tr class="field-row">
			<th class="field-title">Ordering Person:</th>
			<td class="field-value"><?php echo esc_html($data['name']); ?></td>
		</tr>
		
		<tr class="field-row">
			<th class="field-title">Ordering Email:</th>
			<td class="field-value"><?php echo esc_html($data['email']); ?></td>
		</tr>
		
		<tr class="field-row">
			<th class="field-title">Order Notes:</th>
			<td class="field-value"><?php echo nl2br(esc_html($data['order_notes'])); ?></td>
		</tr>
	</tbody>
</table>

<table class="zm-products-table">
	<tbody>
		<?php
		// Display products and categories
		foreach( $product_lists as $category_title => $products ) {
			?>
			<tr class="category-row">
				<th class="category-title"><?php echo esc_html($category_title); ?></th>
				<td class="category-gap"></td>
				<th class="category-qty" colspan="2">Qty</th>
				<th class="category-ref">Ref #</th>
			
				<?php if ( ZMOF_Forms()->is_sending_email() ) { ?>
					<th class="category-ordered">Ordered</th>
					<th class="category-received">Received</th>
				<?php } ?>
			</tr>
			<?php
			
			foreach( $products as $p ) {
				?>
				<tr class="product-row">
					<td class="product-title"><?php echo esc_html($p['title']); ?></td>
					<td class="product-gap"></td>
					<td class="product-qty" colspan="2"><?php echo esc_html($p['quantity']); ?></td>
					<td class="product-ref"><?php echo esc_html($p['reference_number']); ?></td>
					
					<?php if ( ZMOF_Forms()->is_sending_email() ) { ?>
						<td class="product-ordered"></td>
						<td class="product-received"></td>
					<?php } ?>
				</tr>
				<?php
			}
		}
		?>
	</tbody>
</table>
<?php
$html = ob_get_clean();

$table = 'margin: 20px 0; padding: 0; border-spacing: 0; border-collapse: collapse;';

$th = 'padding: 4px 6px; text-align: left; font-weight: 700; vertical-align: top; white-space: nowrap;';
$td = 'padding: 4px 6px; ';

$gap = 'background-color: #aaa; border: 1px solid #888;';
$border = 'border: 1px solid #888; ';

if ( ZMOF_Forms()->is_sending_email() ) {
	$title_w = 'width: 25%; min-width: 200px; ';
	$gap_w = 'width: 5%; min-width: 25px; ';
	$qty_w = 'width: 10%; min-width: 75px; ';
	$ref_w = 'width: 10%; min-width: 75px; ';
	$ordered_w = 'width: 25%; min-width: 200px; ';
	$received_w = 'width: 25%; min-width: 200px; ';
}else{
	$title_w = 'width: 45%; min-width: 200px; ';
	$gap_w = 'width: 5%; min-width: 25px; ';
	$qty_w = 'width: 25%; min-width: 75px; ';
	$ref_w = 'width: 25%; min-width: 75px; ';
	$ordered_w = '';
	$received_w = '';
}


// Apply inline CSS in order to support email clients
$replacements = array(
	'<table class="zm-order-summary"' => '<table class="zm-order-summary" style="'. $table .'"',
	'<table class="zm-products-table"' => '<table class="zm-products-table" style="table-layout: fixed; width: 100%;'. $table .'"',
	
	'<th class="field-title"'       => '<th class="field-title"    style="'. $th .'"',
	'<td class="field-value"'       => '<td class="field-value"    style="'. $td .'"',
	
	'<th class="category-title"'    => '<th class="category-title" style="'. $title_w . $th . $border .'"',
	'<td class="category-gap"'      => '<td class="category-gap"   style="'. $gap_w . $td . $gap .'"',
	'<th class="category-qty"'      => '<th class="category-qty"   style="'. $qty_w . $th . $border .'"',
	'<th class="category-ref"'      => '<th class="category-ref"   style="'. $ref_w . $th . $border .'"',
	'<th class="category-ordered"'  => '<th class="category-ref"   style="'. $ordered_w . $th . $border .'"',
	'<th class="category-received"' => '<th class="category-ref"   style="'. $received_w . $th . $border .'"',
	
	'<td class="product-title"'     => '<td class="product-title"  style="'. $title_w . $td . $border .'"',
	'<td class="product-gap"'       => '<td class="product-gap"    style="'. $gap_w . $td . $gap .'"',
	'<td class="product-qty"'       => '<td class="product-qty"    style="'. $qty_w . $td . $border .'"',
	'<td class="product-ref"'       => '<td class="product-ref"    style="'. $ref_w . $td . $border .'"',
	'<td class="product-ordered"'   => '<td class="product-ref"    style="'. $ordered_w . $td . $border .'"',
	'<td class="product-received"'  => '<td class="product-ref"    style="'. $received_w . $td . $border .'"',
);

$s = array_keys( $replacements );
$r = array_values( $replacements );

$html = str_replace( $s, $r, $html );

echo $html;