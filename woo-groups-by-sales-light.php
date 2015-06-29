<?php
/**
 * woo-groups-by-sales.php
 *
 * Copyright (c) 2011,2012 Antonio Blanco http://www.blancoleon.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author Antonio Blanco	
 * @package woogroupsbysales
 * @since woogroupsbysales 1.0.0
 *
 * Plugin Name: Woocommerce Groups by Sales - Light
 * Plugin URI: http://www.eggemplo.com/plugins/woocommerce-groups-by-sales
 * Description: Join your customers to groups according to their total sales.
 * Version: 1.0
 * Author: eggemplo
 * Author URI: http://www.eggemplo.com
 * License: GPLv3
 */

define( 'WOO_GROUPS_BY_SALES_DOMAIN', 'woogroupsbysaleslight' );
define( 'WOO_GROUPS_BY_SALES_PLUGIN_NAME', 'woo-groups-by-sales-light' );

define( 'WOO_GROUPS_BY_SALES_FILE', __FILE__ );

if ( !defined( 'WOO_GROUPS_BY_SALES_CORE_DIR' ) ) {
	define( 'WOO_GROUPS_BY_SALES_CORE_DIR', WP_PLUGIN_DIR . '/woo-groups-by-sales-light/core' );
}

define ( 'WOO_GROUPS_BY_SALES_DECIMALS', apply_filters( 'woo_groups_by_sales_num_decimals', 2 ) );

define( 'WOO_GROUPS_BY_SALES_PLUGIN_URL', plugin_dir_url( WOO_GROUPS_BY_SALES_FILE ) );

class WooGroupsBySales_Plugin {

	private static $notices = array();

	public static function init() {

		load_plugin_textdomain( WOO_GROUPS_BY_SALES_DOMAIN, null, WOO_GROUPS_BY_SALES_PLUGIN_NAME . '/languages' );

		register_activation_hook( WOO_GROUPS_BY_SALES_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( WOO_GROUPS_BY_SALES_FILE, array( __CLASS__, 'deactivate' ) );

		register_uninstall_hook( WOO_GROUPS_BY_SALES_FILE, array( __CLASS__, 'uninstall' ) );

		add_action( 'init', array( __CLASS__, 'wp_init' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );

		add_action('admin_head', array( __CLASS__, 'woogroupsbysales_enqueue_scripts' ) );
	}

	public static function wp_init() {

		if ( is_multisite() ) {
			$active_sitewide_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins = array_merge( get_option( 'active_plugins', array() ), $active_sitewide_plugins );
		} else {
			$active_plugins = get_option( 'active_plugins', array() );
		}
		$groups_is_active = in_array( 'groups/groups.php', $active_plugins );
		$woo_is_active = in_array( 'woocommerce/woocommerce.php', $active_plugins );
		$pro_is_active = in_array( 'woo-groups-by-sales/woo-groups-by-sales.php', $active_plugins );
		
		if ( ( !$groups_is_active ) || ( !$woo_is_active ) || ( $pro_is_active ) ) {
			if ( $pro_is_active ) {
				self::$notices[] = "<div class='error'>" . __( 'The <strong>Woocommerce Groups by Sales Pro</strong> version plugin is active.', WOO_GROUPS_BY_SALES_DOMAIN ) . "</div>";
			}
			if ( !$groups_is_active ) {
				self::$notices[] = "<div class='error'>" . __( 'The <strong>Woocommerce Groups by Sales</strong> plugin requires the <a href="http://wordpress.org/extend/plugins/groups" target="_blank">Groups</a> plugin to be activated.', WOO_GROUPS_BY_SALES_DOMAIN ) . "</div>";
			}
			if ( !$woo_is_active ) {
				self::$notices[] = "<div class='error'>" . __( 'The <strong>Woocommerce Groups by Sales</strong> plugin requires the <a href="http://wordpress.org/extend/plugins/woocommerce" target="_blank">Woocommerce</a> plugin to be activated.', WOO_GROUPS_BY_SALES_DOMAIN ) . "</div>";
			}
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			deactivate_plugins( array( __FILE__ ) );
		} else {

			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 40 );
			//call register settings function
			add_action( 'admin_init', array( __CLASS__, 'register_woogroupsbysales_settings' ) );

			if ( !class_exists( "WooGroupsBySales" ) ) {
				include_once 'core/class-woogroupsbysaleslight.php';
			}
		}

	}

	/**
	 * Register settings as woogroupsbysales
	 */
	public static function register_woogroupsbysales_settings() {

	}

	/**
	 * Load scripts.
	 */
	public static function woogroupsbysales_enqueue_scripts() {
		wp_register_style( 'wgbsales-styles', WOO_GROUPS_BY_SALES_PLUGIN_URL . 'css/wgbsales-admin-styles.css' );
		wp_enqueue_style ('wgbsales-styles');
	}

	public static function admin_notices() { 
		if ( !empty( self::$notices ) ) {
			foreach ( self::$notices as $notice ) {
				echo $notice;
			}
		}
	}

	/**
	 * Adds the admin section.
	 */
	public static function admin_menu() {
		$admin_page = add_submenu_page(
				'woocommerce',
				__( 'Groups by Sales Light' ),
				__( 'Groups by Sales Light' ),
				'manage_options',
				'woogroupsbysales',
				array( __CLASS__, 'woogroupsbysales_settings' )
		);
	}

	public static function woogroupsbysales_settings () {
	?>
	<div class="wrap">
	<h2><?php echo __( 'Woocommerce Groups by Sales - Light', WOO_GROUPS_BY_SALES_DOMAIN ); ?></h2>
	<?php 
	$alert = "";
	$groups = WooGroupsBySales::get_all_groups();

	if ( isset( $_POST['submit'] ) ) {
		$alert = __("Options updated", WOO_GROUPS_BY_SALES_DOMAIN);

		for ( $i = 0; $i < 2; $i++ ) {
			if ( isset( $_POST[ "wgbsales-level-" . $i . "-group" ] ) && ( $_POST[ "wgbsales-level-" . $i . "-group" ] !== "" ) ) {
				add_option( "wgbsales-level-" . $i . "-group", $_POST[ "wgbsales-level-" . $i . "-group" ] );
				update_option( "wgbsales-level-" . $i . "-group", $_POST[ "wgbsales-level-" . $i . "-group" ] );
			}
			if ( isset( $_POST[ "wgbsales-level-" . $i . "-value" ] ) && ( $_POST[ "wgbsales-level-" . $i . "-value" ] !== "" ) ) {
				add_option( "wgbsales-level-" . $i . "-value", $_POST[ "wgbsales-level-" . $i . "-value" ] );
				update_option( "wgbsales-level-" . $i . "-value", $_POST[ "wgbsales-level-" . $i . "-value" ] );
			}
		}
	}

	if ($alert != "") {
		echo '<div style="background-color: #ffffe0;border: 1px solid #993;padding: 1em;margin-right: 1em;">' . $alert . '</div>';
	}
	?>
	<div class="wrap" style="border: 1px solid #ccc; padding:10px;">
	<form method="post" action="">
		<table class="form-table">
		<?php 
		for ( $i=0; $i < 2; $i++ ) {
			?>
				<tr valign="top">
				<th scope="row"><?php echo __( "Range " . $i . ":", WOO_GROUPS_BY_SALES_DOMAIN);?></th>
				<td>
				<?php
					if ( $i == 0 ) {
						echo "0 &lt;";
					} else {
						echo "&lt;";
					}
				?>
					<select name="wgbsales-level-<?php echo $i;?>-group">
						<option value=""><?php echo __("Select one", WOO_GROUPS_BY_SALES_DOMAIN);?></option>
						<?php 
						if ( sizeof( $groups ) > 0 ) {
							foreach ( $groups as $group ) {
								$selected = "";
								if ( get_option( "wgbsales-level-" . $i . "-group", false ) == $group->group_id ) {
									$selected = "selected";
								}
								echo '<option value="' . $group->group_id . '" ' . $selected . ' >' . $group->name . '</option>';
							}
						}
						?>
					</select>
				<?php
					if ( $i < ( 1 ) ) {
				?>
					&lt;= <input type="text" name="wgbsales-level-<?php echo $i;?>-value" value="<?php echo get_option( "wgbsales-level-" . $i . "-value" ); ?>" />
				<?php
					}
				?>
				</td>
				</tr>
				<?php
			}
			?>
		</table>

		<?php submit_button( __( "Save", WOO_GROUPS_BY_SALES_DOMAIN ) ); ?>

		<?php settings_fields( 'woogroupsbysales' ); ?>

	</form>
	</div>
	</div>
	<?php 
	}

	/**
	 * Plugin activation work.
	 */
	public static function activate() {
	}

	/**
	 * Plugin deactivation.
	 *
	 */
	public static function deactivate() {
	}

	/**
	 * Plugin uninstall. Delete database table.
	 *
	 */
	public static function uninstall() {
	}

}

WooGroupsBySales_Plugin::init();