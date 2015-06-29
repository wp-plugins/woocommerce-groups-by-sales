<?php
/**
 * class-woogroupsbysales.php
 *
 * Copyright (c) Antonio Blanco http://www.blancoleon.com
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
 */

/**
 * WooGroupsBySales class
 */
class WooGroupsBySales {

	public static function init() {

		add_action ( 'woocommerce_checkout_order_processed', array( __CLASS__, 'woocommerce_checkout_order_processed' ) );

	}
	
	public static function woocommerce_checkout_order_processed( $order_id ) {
	
		$user_id = get_current_user_id();
		
		// this probably means the user is not logged in
		if( !$user_id ) {
			return;
		}
		
		// get the user object
		$user = get_user_by('id', $user_id);
		
		// nope, something went wrong
		if(!$user) {
			return;
		}
		
		// ranges
		$levels = 2;
		$ranges = array();
		if ( $levels > 0 ) {
			
			$max_range = get_option( "wgbsales-level-" . ($levels-1) . "-group", "" );
			for ( $i=0; $i < ($levels-1); $i++ ) {
				if ( ( get_option( "wgbsales-level-" . $i . "-value", 0 ) ) && ( get_option( "wgbsales-level-" . $i . "-value", 0 ) !== "" ) ) {
					$ranges[get_option( "wgbsales-level-" . $i . "-value" )] = get_option( "wgbsales-level-" . $i . "-group", "" );
				}
			}
			// the user is a big spender, lets give him a role
			$spent = self::woocommerce_get_amount_spent($user->data->user_email);

			$result = '0';
			$groups = null;
			foreach ( $ranges as $limit => $group ) {
				if ( $spent <= floatval($limit) ) {
					$groups = $group;
				}
			}

			if ( !$groups || ( $groups == "" ) ) {
				$groups = $max_range;
			}

			if ( $groups !== "" ) {
				if ( $group = Groups_Group::read( $groups ) ) {
					$result = Groups_User_Group::create( array( "user_id"=>$user_id, "group_id"=>$group->group_id ) );
				}
			}
		}
	}

	/**
	 * Gets the amount an email has spent
	 * @param  string $email Users email
	 * @return float
	 */
	public static function woocommerce_get_amount_spent($email) {
	
		$orders = get_posts( array(
				'meta_key'    		=> '_billing_email',
				'meta_value'  		=> $email,
				'post_type'   		=> 'shop_order',
				'post_status' 		=> 'wc-completed',
				'posts_per_page'	=>-1
		) );
	
		$spent = 0;
		foreach ( $orders as $shop_order ) {
			$order = new WC_Order($shop_order->ID);
			$spent += $order->get_total();
		}
	
		return $spent;
	
	}


	public static function get_all_groups (  ) {
		global $wpdb;

		$groups_table = _groups_get_tablename( 'group' );

		return $wpdb->get_results( "SELECT * FROM $groups_table ORDER BY name" );

	}

	/**
	 * Get all user groups.
	 * @param int $user_id
	 * @return array
	 */
	public static function get_user_groups( $user_id ) {
		global $wpdb;
	
		$groups_table = _groups_get_tablename( 'group' );
		$result = array();
		if ( $groups = $wpdb->get_results( "SELECT * FROM $groups_table ORDER BY group_id DESC" ) ) {
			foreach( $groups as $group ) {
				$is_member = Groups_User_Group::read( $user_id, $group->group_id ) ? true : false;
				if ( $is_member ) {
					$result[] = $group;
				}
			}
		}
		return  $result;
	}
}
WooGroupsBySales::init();