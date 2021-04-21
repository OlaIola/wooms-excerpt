<?php
/**
 * Plugin Name: WooMS Excerpt (extension)
 * Description: Краткое описание товара в МойСклад в дополнительном поле сохраняется в excerpt товара
 * Plugin URI: https://github.com/wpcraft-ru/wooms/issues/400
 * Version: 1.0
 * Author: OGlekler
 * Author URI: https://github.com/OlaIola/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * WooMS tested up to: 8.2.0
 * Version: 1.0
 *
 * @package WooMS Excerpt
 */

namespace WooMS;

defined( 'ABSPATH' ) || exit;

/**
 * Add excerpt from custom field in MoySklad
 */
class ProductExcerpt {

	/**
	 * The Init
	 */
	public static function init() {

		add_action(
			'plugins_loaded',
			function () {

				if ( ! class_exists( 'WooMS_Core' ) ) {

					add_action( 'after_plugin_row_' . plugin_basename( __FILE__ ), array( __CLASS__, 'plugin_row' ), 5, 3 );

					return;
				}

				/**
				 * Depending on the version of Wooms the 'wooms_add_settings' hook can be absent
				 */
				add_action( 'wooms_add_settings', array( __CLASS__, 'add_settings' ), 20 );

				/**
				 * Action in case of absence of the 'wooms_add_settings' hook. It will be removed in the case where hook exist
				 */
				add_action( 'admin_init', array( __CLASS__, 'add_settings' ), 20 );

				/**
				 * If checked "Использовать краткое описание продуктов из дополнительного поля в МойСклад"
				 */
				if ( get_option( 'wooms_excerpt' ) ) {

					add_filter( 'wooms_product_save', array( __CLASS__, 'update_custom_excerpt' ), 20, 2 );
				}

				/**
				 * Preventing Wooms XT (Extra) from saving field with a short description like product attribute 
				 * in case where "Включить синхронизацию доп. полей как атрибутов" (wooms_attr_enabled) is checked
				 */
				if ( get_option( 'wooms_excerpt_name' ) ) {

					add_filter( 'wooms_attributes', array( __CLASS__, 'remove_short_description_from_product_attributes' ), 15, 3 );
				}
			}
		);
	}


	/**
	 * Wooms Excerpt: Wooms Check
	 *
	 * @param $plugin_file
	 * @param $plugin_data
	 * @param $status
	 */
	public static function plugin_row( $plugin_file, $plugin_data, $status ) {

		$base_name = plugin_basename( $plugin_file );

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

	/**
	 * Add Settings (Options)
	 */
	public static function add_settings() {

		self::add_setting_custom_excerpt();

		remove_action( 'admin_init', array( __CLASS__, 'add_settings'), 20 );
	}

	/**
	 * Settings (options) fields
	 */
	public static function add_setting_custom_excerpt()	{

		/**
		 * Checkmark to enable sync of custom excerpt
		 */
		$option_name = 'wooms_excerpt';

		register_setting( 'mss-settings', $option_name );

		add_settings_field(
			$id       = $option_name,
			$title    = 'Использовать краткое описание продуктов из дополнительного поля в МойСклад',
			$callback = function ( $args ) {

				printf(
					'<input type="checkbox" name="%s" value="1" %s />',
					$args['key'],
					checked( 1, $args['value'], false )
				);

				printf(
					'<p>%s</p>',
					'Подробнее: <a href="https://github.com/wpcraft-ru/wooms/issues/400">https://github.com/wpcraft-ru/wooms/issues/400</a>'
				);
			},
			$page     = 'mss-settings',
			$section  = 'woomss_section_other',
			$args     = array(
				'key'   => $option_name,
				'value' => get_option( $option_name, 0 ),
			)
		);

		/**
		 * Name of the field with custom excerpt
		 */
		$option_name = 'wooms_excerpt_name';

		register_setting( 'mss-settings', $option_name );

		add_settings_field(
			$id	      = $option_name,
			$title	  = 'Краткое описание товара',
			$callback = function ( $args ) {
				printf( '<input type="text" name="%s" value="%s" />', $args['key'], $args['value'] );
				echo '<p><small>Укажите наименование поля с кратким описание товара в МойСклад. <br>Если дополнительное поле с таким названием будет заполнено у товара, оно будет сохраняться в Краткое описание товара на сайте.</small></p>';
			},
			$page     = 'mss-settings',
			$section  = 'woomss_section_other',
			$args     = array(
				'key'   => $option_name,
				'value' => sanitize_text_field( get_option( $option_name ) ),
			)
		);
	}

	/**
	 * Update excerpt according to a custom field from MoySklad
	 *
	 * @param object $product — instance of a WC Product.
	 * @param object $item — data from MoySklad.
	 * @return object
	 */
	public static function update_custom_excerpt( $product, $item ) {

		/**
		 * Get name of the field with custom excerpt
		 */
		$field_name = get_option( 'wooms_excerpt_name' );

		if ( $field_name ) {

			$short_description_exist = 0;

			if ( ! empty( $item['attributes'] ) ) {

				foreach ( $item['attributes'] as $attribute ) {

					if ( empty( $attribute['name'] ) ) {
						continue;
					}

					if ( $attribute['name'] == $field_name ) {

						$short_description_exist = 1;

						/**
						 * If checked "Использовать краткое описание продуктов вместо полного" - swap them
						 * */
						if ( ! get_option( 'wooms_short_description' ) ) {

							$product->set_short_description( $attribute['value'] );
						} else {

							$product->set_description( $attribute['value'] );
						}
						break;
					}
				}
			}

			/**
			 * If the short description isn't exist on MoySclad — clean excerpt
			 * */
			if ( ! $short_description_exist ) {

				if ( ! get_option( 'wooms_short_description' ) ) {

					$product->set_short_description( '' );
				} else {

					$product->set_description( '' );
				}
			}
		}

		return $product;
	}

	/**
	 * To prevent Wooms XT (Extra) from saving field with a short description like product attribute
	 *
	 * @param  array $product_attributes — with instances of WC_Product_Attribute.
	 * @param  int   $product_id — data from MoySklad.
	 * @param  array $item — data from MoySklad.
	 * @return array
	 */
	public static function remove_short_description_from_product_attributes( $product_attributes, $product_id, $item ) {

		$field_name = get_option( 'wooms_excerpt_name' );

		foreach ( $product_attributes as $slug => $attribute ) {

			if ( $attribute->get_name() == $field_name ) {

				unset( $product_attributes[ $slug ] );

				break;
			}
		}

		return $product_attributes;
	}
}

ProductExcerpt::init();
