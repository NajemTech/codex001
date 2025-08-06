<?php
/**
 * Plugin Name: SBYS Zoom Session Credits
 * Description: Manages Zoom session credits via custom post type, REST API, and shortcode.
 * Version: 0.1.0
 * Author: Codex
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register custom post type for zoom credits.
 */
function sbys_register_zoom_credit_cpt() {
    $labels = array(
        'name'               => __( 'Zoom Credits', 'sbys' ),
        'singular_name'      => __( 'Zoom Credit', 'sbys' ),
        'add_new_item'       => __( 'Add New Zoom Credit', 'sbys' ),
        'edit_item'          => __( 'Edit Zoom Credit', 'sbys' ),
        'new_item'           => __( 'New Zoom Credit', 'sbys' ),
        'view_item'          => __( 'View Zoom Credit', 'sbys' ),
        'search_items'       => __( 'Search Zoom Credits', 'sbys' ),
        'not_found'          => __( 'No Zoom Credits found', 'sbys' ),
        'not_found_in_trash' => __( 'No Zoom Credits found in Trash', 'sbys' ),
    );

    $args = array(
        'labels'          => $labels,
        'public'          => false,
        'show_ui'         => true,
        'show_in_menu'    => false,
        'supports'        => array( 'title', 'author' ),
        'has_archive'     => false,
        'rewrite'         => false,
        'capability_type' => 'post',
    );

    register_post_type( 'zoom_credit', $args );
}
add_action( 'init', 'sbys_register_zoom_credit_cpt' );

/**
 * Add submenu under Paid Memberships Pro for managing credits.
 */
function sbys_register_admin_menu() {
    add_submenu_page(
        'pmpro-dashboard',
        __( 'Session Credits', 'sbys' ),
        __( 'Session Credits', 'sbys' ),
        'manage_options',
        'sbys-session-credits',
        'sbys_render_credits_page'
    );
}
add_action( 'admin_menu', 'sbys_register_admin_menu' );

/**
 * Callback to render the credits admin page.
 */
function sbys_render_credits_page() {
    echo '<div class="wrap"><h1>' . esc_html__( 'Session Credits', 'sbys' ) . '</h1>';
    echo '<p>' . esc_html__( 'Manage user session credits here.', 'sbys' ) . '</p></div>';
}

/**
 * Helper function to get a user\'s credit balance.
 *
 * @param int $user_id User ID.
 * @return int Credit count for the user.
 */
function sbys_get_user_credit_balance( $user_id ) {
    $query = new WP_Query( array(
        'post_type'      => 'zoom_credit',
        'post_status'    => 'publish',
        'author'         => $user_id,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ) );

    return intval( $query->found_posts );
}

/**
 * Register REST route for retrieving credit balances.
 */
function sbys_register_rest_routes() {
    register_rest_route(
        'sbys/v1',
        '/credits/(?P<user_id>\d+)',
        array(
            'methods'             => 'GET',
            'callback'            => 'sbys_rest_get_credits',
            'permission_callback' => '__return_true',
            'args'                => array(
                'user_id' => array(
                    'validate_callback' => 'is_numeric',
                ),
            ),
        )
    );
}
add_action( 'rest_api_init', 'sbys_register_rest_routes' );

/**
 * REST callback for user credit balance.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response
 */
function sbys_rest_get_credits( WP_REST_Request $request ) {
    $user_id = intval( $request['user_id'] );

    return rest_ensure_response( array(
        'user_id' => $user_id,
        'credits' => sbys_get_user_credit_balance( $user_id ),
    ) );
}

/**
 * Shortcode to display current user\'s credit balance.
 *
 * @return string
 */
function sbys_credit_balance_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '0';
    }

    $user_id = get_current_user_id();
    return (string) sbys_get_user_credit_balance( $user_id );
}
add_shortcode( 'credit_balance', 'sbys_credit_balance_shortcode' );

