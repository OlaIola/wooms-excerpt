<?php

/**
 * Wooms Excerpt: Wooms XT Check
 *
 * @param $plugin_file
 * @param $plugin_data
 * @param $status
 */
function wooms_exrt_plugin_row( $plugin_file, $plugin_data, $status ) {

	if ( class_exists( 'WooMS_Core' ) ) {
		return;
	}

	$base_name = 'wooms-excerpt/wooms-excerpt.php';

	// >= WP 5.5
	$colspan = 4;

	// < WP 5.5
	if( version_compare( $GLOBALS['wp_version'], '5.5', '<' ) ) {
		$colspan = 3;
	}
	?>

	<style>
		.plugins tr[data-plugin='<?php echo $base_name; ?>'] th,
		.plugins tr[data-plugin='<?php echo $base_name; ?>'] td{
			box-shadow:none;
		}
	</style>

	<tr class="plugin-update-tr active">
		<td colspan="<?php echo $colspan; ?>" class="plugin-update colspanchange">
			<div class="update-message notice inline notice-error notice-alt">
				<p><?php _e( 'WooMS Excerpt требуется <a href="https://wpcraft.ru/product/wooms/" target="_blank">Wooms</a> (minimum: 8.1).' ); ?></p>
			</div>
		</td>
	</tr>

	<?php

}

add_action( 'after_plugin_row_wooms-excerpt/wooms-excerpt.php', 'wooms_exrt_plugin_row', 5, 3 );
