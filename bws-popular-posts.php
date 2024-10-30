<?php
/*
Plugin Name: Popular Posts by BestWebSoft
Plugin URI: https://bestwebsoft.com/products/wordpress/plugins/relevant/
Description: Track views, comments and add most popular posts to Wordpress widgets.
Author: BestWebSoft
Text Domain: bws-popular-posts
Domain Path: /languages
Version: 1.0.6
Author URI: https://bestwebsoft.com/
License: GPLv3 or later
*/

/*  @ Copyright 2017  BestWebSoft  ( https://support.bestwebsoft.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Add option page in admin menu */
if ( ! function_exists( 'pplrpsts_admin_menu' ) ) {
	function pplrpsts_admin_menu() {
		bws_general_menu();
		$settings = add_submenu_page( 'bws_panel', __( 'Popular Posts Settings', 'bws-popular-posts' ), 'Popular Posts', 'manage_options', "popular-posts.php", 'pplrpsts_settings_page' );
		add_action( 'load-' . $settings, 'pplrpsts_add_tabs' );
	}
}

/**
 * Internationalization
 */
if ( ! function_exists( 'pplrpsts_plugins_loaded' ) ) {
	function pplrpsts_plugins_loaded() {
		load_plugin_textdomain( 'bws-popular-posts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

/* Plugin initialization - add internationalization and size for image*/
if ( ! function_exists ( 'pplrpsts_init' ) ) {
	function pplrpsts_init() {
		global $pplrpsts_plugin_info;

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );

		if ( empty( $pplrpsts_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) )
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$pplrpsts_plugin_info = get_plugin_data( __FILE__ );
		}

		/* Function check if plugin is compatible with current WP version  */
		bws_wp_min_version_check( plugin_basename( __FILE__ ), $pplrpsts_plugin_info, '3.9' );

		add_image_size( 'popular-post-featured-image', 60, 60, true );
	}
}

/* Plugin initialization for admin page */
if ( ! function_exists ( 'pplrpsts_admin_init' ) ) {
	function pplrpsts_admin_init() {
		global $bws_plugin_info, $pplrpsts_plugin_info, $pagenow;

		if ( empty( $bws_plugin_info ) )
			$bws_plugin_info = array( 'id' => '177', 'version' => $pplrpsts_plugin_info["Version"] );

		/* Call register settings function */
		$admin_pages = array( 'widgets.php', 'plugins.php' );
		if ( in_array( $pagenow, $admin_pages ) || ( isset( $_GET['page'] ) && "popular-posts.php" == $_GET['page'] ) )
			pplrpsts_set_options();
	}
}

/* Setting options */
if ( ! function_exists( 'pplrpsts_set_options' ) ) {
	function pplrpsts_set_options() {
		global $pplrpsts_options, $pplrpsts_plugin_info, $pplrpsts_options_defaults;

		$pplrpsts_options_defaults	=	array(
			'plugin_option_version'		=>	$pplrpsts_plugin_info["Version"],
			'widget_title'				=>	__( 'Popular Posts', 'bws-popular-posts' ),
			'count'						=>	'5',
			'excerpt_length'			=>	'10',
			'excerpt_more'				=>	'...',
			'no_preview_img'			=>	plugins_url( 'images/no_preview.jpg', __FILE__ ),
			'order_by'					=>	'comment_count',
			'display_settings_notice'	=>	1,
			'show_views'				=>	1,
			'show_date'					=>	1,
			'show_author'				=>	1,
			'show_image'				=>	1,
			'suggest_feature_banner'	=>	1,
			'use_category'				=>	1,
			'min_count'					=>	0,
			'display_not_supported_notice' => 1,
		);

		if ( ! get_option( 'pplrpsts_options' ) )
			add_option( 'pplrpsts_options', $pplrpsts_options_defaults );

		$pplrpsts_options = get_option( 'pplrpsts_options' );

		/* Array merge incase this version has added new options */
		if ( ! isset( $pplrpsts_options['plugin_option_version'] ) || $pplrpsts_options['plugin_option_version'] != $pplrpsts_plugin_info["Version"] ) {
			$pplrpsts_options = array_merge( $pplrpsts_options_defaults, $pplrpsts_options );
			$pplrpsts_options['plugin_option_version'] = $pplrpsts_plugin_info["Version"];
			$pplrpsts_options['display_not_supported_notice'] = 1;
			update_option( 'pplrpsts_options', $pplrpsts_options );
		}
	}
}

/* Function for display popular_posts settings page in the admin area */
if ( ! function_exists( 'pplrpsts_settings_page' ) ) {
	function pplrpsts_settings_page() {
		global $pplrpsts_options, $pplrpsts_plugin_info, $pplrpsts_options_defaults;
		$error = $message = "";

		/* Save data for settings page */
		if ( isset( $_REQUEST['pplrpsts_form_submit'] ) && check_admin_referer( plugin_basename(__FILE__), 'pplrpsts_nonce_name' ) ) {

			$pplrpsts_options['widget_title']	= ( ! empty( $_POST['pplrpsts_widget_title'] ) ) ? stripslashes( esc_html( $_POST['pplrpsts_widget_title'] ) ) : null;
			$pplrpsts_options['count']			= ( ! empty( $_POST['pplrpsts_count'] ) ) ? absint( $_POST['pplrpsts_count'] ) : 2;
			$pplrpsts_options['excerpt_length']	= ( ! empty( $_POST['pplrpsts_excerpt_length'] ) ) ? absint( $_POST['pplrpsts_excerpt_length'] ) : 10;
			$pplrpsts_options['excerpt_more']	= ( ! empty( $_POST['pplrpsts_excerpt_more'] ) ) ? stripslashes( esc_html( $_POST['pplrpsts_excerpt_more'] ) ) : '...';
			$pplrpsts_options['min_count']		= ( ! empty( $_POST['pplrpsts_min_count'] ) ) ? absint( $_POST['pplrpsts_min_count'] ) : 0;
			$pplrpsts_options["use_category"]	= isset( $_POST["pplrpsts_use_category"] ) ? 1 : 0;

			$show_options = array( 'views', 'date', 'author', 'image' );
			foreach ( $show_options as $item )
				$pplrpsts_options["show_{$item}"] = isset( $_POST["pplrpsts_show_{$item}"] ) ? 1 : 0;
			if ( ! empty( $_POST['pplrpsts_no_preview_img'] ) && pplrpsts_is_200( $_POST['pplrpsts_no_preview_img'] ) && getimagesize( $_POST['pplrpsts_no_preview_img'] ) )
				$pplrpsts_options['no_preview_img'] = $_POST['pplrpsts_no_preview_img'];
			else
				$pplrpsts_options['no_preview_img'] = plugins_url( 'images/no_preview.jpg', __FILE__ );
			$pplrpsts_options['order_by'] 		= ( ! empty( $_POST['pplrpsts_order_by'] ) ) ? $_POST['pplrpsts_order_by'] : 'comment_count';

			if ( "" == $error ) {
				/* Update options in the database */
				update_option( 'pplrpsts_options', $pplrpsts_options );
				$message = __( "Settings saved.", 'bws-popular-posts' );
			}
		}

		/* Add restore function */
		if ( isset( $_REQUEST['bws_restore_confirm'] ) && check_admin_referer( plugin_basename(__FILE__), 'bws_settings_nonce_name' ) ) {
			$pplrpsts_options = $pplrpsts_options_defaults;
			update_option( 'pplrpsts_options', $pplrpsts_options );
			$message = __( 'All plugin settings were restored.', 'bws-popular-posts' );
		} /* end */

		/* Display form on the setting page */ ?>
		<div class="wrap">
			<h1><?php _e( 'Popular Posts Settings', 'bws-popular-posts' ); ?></h1>
			<h2 class="nav-tab-wrapper">
				<a class="nav-tab<?php if ( ! isset( $_GET['action'] ) ) echo ' nav-tab-active'; ?>" href="admin.php?page=popular-posts.php"><?php _e( 'Settings', 'bws-popular-posts' ); ?></a>
				<a class="nav-tab <?php if ( isset( $_GET['action'] ) && 'custom_code' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=popular-posts.php&amp;action=custom_code"><?php _e( 'Custom code', 'bws-popular-posts' ); ?></a>
			</h2>
			<?php bws_show_settings_notice(); ?>
			<div class="updated fade below-h2" <?php if ( $message == "" || "" != $error ) echo "style=\"display:none\""; ?>><p><strong><?php echo $message; ?></strong></p></div>
			<div class="error below-h2" <?php if ( "" == $error ) echo "style=\"display:none\""; ?>><p><?php echo $error; ?></p></div>
			<?php if ( ! isset( $_GET['action'] ) ) {
				if ( isset( $_REQUEST['bws_restore_default'] ) && check_admin_referer( plugin_basename(__FILE__), 'bws_settings_nonce_name' ) ) {
					bws_form_restore_default_confirm( plugin_basename(__file__) );
				} else { ?>
					<form class="bws_form" method="post" action="admin.php?page=popular-posts.php">
						<p><?php _e( 'If you would like to display popular posts with a widget, you need to add the widget "Popular Posts Widget" in the', 'bws-popular-posts' ); ?>
							&nbsp;<a href="<?php echo self_admin_url( 'widgets.php' ); ?>"><?php _e( 'Widgets' ); ?></a>&nbsp; <?php _e( 'tab', 'bws-popular-posts' ); ?></p>
						<table class="form-table">
							<tr valign="top">
								<th scope="row"><?php _e( 'Widget title', 'bws-popular-posts' ); ?></th>
								<td>
									<input name="pplrpsts_widget_title" type="text" maxlength="250" value="<?php echo $pplrpsts_options['widget_title']; ?>"/>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e( 'Number of posts', 'bws-popular-posts' ); ?></th>
								<td>
									<input name="pplrpsts_count" type="number" min="1" max="10000" value="<?php echo $pplrpsts_options['count']; ?>"/>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e( 'Do not display the block if posts number is less than', 'bws-popular-posts' ); ?></th>
								<td>
									<label><input name="pplrpsts_min_count" type="number" min="0" max="9999" step="1" value="<?php echo $pplrpsts_options['min_count']; ?>"/></label>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e( 'Excerpt length', 'bws-popular-posts' ); ?></th>
								<td>
									<input name="pplrpsts_excerpt_length" type="number" min="1" max="10000" value="<?php echo $pplrpsts_options['excerpt_length']; ?>"/>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e( '"Read more" text', 'bws-popular-posts' ); ?></th>
								<td>
									<input name="pplrpsts_excerpt_more" type="text" maxlength="250" value="<?php echo $pplrpsts_options['excerpt_more']; ?>"/>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e( 'Default image (full URL), if no featured image is available', 'bws-popular-posts' ); ?></th>
								<td>
									<input name="pplrpsts_no_preview_img" type="text" maxlength="250" value="<?php echo $pplrpsts_options['no_preview_img']; ?>"/>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e( 'Display', 'bws-popular-posts' ); ?></th>
								<td><fieldset>
									<?php $show_options = array(
										'views'		=> __( 'views number', 'bws-popular-posts' ),
										'date'		=> __( 'post date', 'bws-popular-posts' ),
										'author'	=> __( 'author', 'bws-popular-posts' ),
										'image'		=> __( 'featured image', 'bws-popular-posts' )
									);
									foreach( $show_options as $item => $label ) {
										$checked	= 1 == $pplrpsts_options["show_{$item}"] ? ' checked="checked"' : '';
										$attr		= "pplrpsts_show_{$item}"; ?>
										<label for="<?php echo $attr; ?>">
											<input id="<?php echo $attr; ?>" name="<?php echo $attr; ?>" type="checkbox" value="1"<?php echo $checked; ?> /><?php echo $label; ?>
										</label><br />
									<?php } ?>
								</fieldset></td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e( 'Order by number of', 'bws-popular-posts' ); ?></th>
								<td><fieldset>
									<label><input name="pplrpsts_order_by" type="radio" value="comment_count" <?php if ( 'comment_count' == $pplrpsts_options['order_by'] ) echo 'checked="checked"'; ?> /> <?php _e( 'comments', 'bws-popular-posts' ); ?></label><br />
									<label><input name="pplrpsts_order_by" type="radio" value="views_count" <?php if ( 'views_count' == $pplrpsts_options['order_by'] ) echo 'checked="checked"'; ?> /> <?php _e( 'views', 'bws-popular-posts' ); ?></label>
								</fieldset></td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e( 'Display popular posts in accordance with current category', 'bws-popular-posts' ); ?></th>
								<td>
									<input id="pplrpsts_use_category" name="pplrpsts_use_category" type="checkbox" value="1" <?php if ( ( isset( $pplrpsts_options ) && 1 == $pplrpsts_options["use_category"] ) ) echo 'checked="checked"'; ?>/>
								</td>
							</tr>
						</table>
						<p class="submit">
							<input id="bws-submit-button" type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'bws-popular-posts' ); ?>" />
							<input type="hidden" name="pplrpsts_form_submit" value="submit" />
							<?php wp_nonce_field( plugin_basename(__FILE__), 'pplrpsts_nonce_name' ); ?>
						</p>
					</form>
					<?php bws_form_restore_default_settings( plugin_basename(__file__) );
				}
			} elseif ( 'custom_code' == $_GET['action'] ) {
				bws_custom_code_tab();
			} ?>
		</div>
	<?php }
}

/* Create widget for plugin */
if ( ! class_exists( 'PopularPosts' ) ) {
	class PopularPosts extends WP_Widget {

		function __construct() {
			/* Instantiate the parent object */
			parent::__construct(
				'pplrpsts_popular_posts_widget',
				__( 'Popular Posts Widget', 'bws-popular-posts' ),
				array( 'description' => __( 'Widget for displaying Popular Posts by comments or views count.', 'bws-popular-posts' ) )
			);
		}

		/* Outputs the content of the widget */
		function widget( $args, $instance ) {
			global $post, $pplrpsts_excerpt_length, $pplrpsts_excerpt_more, $pplrpsts_options;
			if ( empty( $pplrpsts_options ) )
				$pplrpsts_options = get_option( 'pplrpsts_options' );
			$widget_title		= ( ! empty( $instance['widget_title'] ) ) ? apply_filters( 'widget_title', $instance['widget_title'], $instance, $this->id_base ) : $pplrpsts_options['widget_title'];
			$count				= isset( $instance['count'] ) ? intval( $instance['count'] ) : $pplrpsts_options['count'];
			$excerpt_length		= $pplrpsts_excerpt_length = isset( $instance['excerpt_length'] ) ? intval( $instance['excerpt_length'] ) : $pplrpsts_options['excerpt_length'];
			$excerpt_more		= $pplrpsts_excerpt_more = isset( $instance['excerpt_more'] ) ? stripslashes( esc_html( $instance['excerpt_more'] ) ) : $pplrpsts_options['excerpt_more'];
			$no_preview_img		= isset( $instance['no_preview_img'] ) ? $instance['no_preview_img'] : $pplrpsts_options['no_preview_img'];
			$order_by			= isset( $instance['order_by'] ) ? $instance['order_by'] : $pplrpsts_options['order_by'];
			$min_count			= isset( $instance['min_count'] ) ? intval( $instance['min_count'] ) : $pplrpsts_options['min_count'];
			$show_views			= isset( $instance['show_views'] ) ? $instance['show_views'] : 1;
			$show_date			= isset( $instance['show_date'] ) ? $instance['show_date'] : 1;
			$show_author		= isset( $instance['show_author'] ) ? $instance['show_author'] : 1;
			$show_image			= isset( $instance['show_image'] ) ? $instance['show_image'] : 1;
			$use_category		= isset( $instance['use_category'] ) ? $instance['use_category'] : 1;
			if ( 'comment_count' == $order_by )
				$query_args = array(
					'post_type'				=> 'post',
					'post_status'			=> 'publish',
					'meta_key'				=> 'pplrpsts_post_views_count',
					'orderby'				=> 'comment_count',
					'order'					=> 'DESC',
					'posts_per_page'		=> $count,
					'ignore_sticky_posts' 	=> 1
				);
			else
				$query_args = array(
					'post_type'				=> 'post',
					'post_status'			=> 'publish',
					'meta_key'				=> 'pplrpsts_post_views_count',
					'orderby'				=> 'meta_value_num',
					'order'					=> 'DESC',
					'posts_per_page'		=> $count,
					'ignore_sticky_posts' 	=> 1
				);

			/* Exclude current post from the list */
			if ( is_singular() )
				$query_args['post__not_in'] = array( $post->ID );

			if ( ! empty( $use_category ) && ( is_category() || is_singular() ) ) {

				/* We get post category */
				$cat_ids = array();
				if ( is_singular() ) {
					$categories = get_the_category( $post->ID );
					if ( ! empty( $categories ) ) {
						foreach( $categories as $category )
							$cat_ids[] = $category->cat_ID;
					}
				} elseif ( is_category() ) {
					$category = get_category( get_query_var( 'cat' ) );
					$cat_ids[] = $category->cat_ID;
				}
				if ( ! empty( $cat_ids ) )
					$query_args['category__in'] = $cat_ids;
			}
			if ( ! function_exists ( 'is_plugin_active' ) )
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

			if ( is_plugin_active( 'custom-fields-search-pro/custom-fields-search-pro.php' ) || is_plugin_active( 'custom-fields-search/custom-fields-search.php' ) ) {
				$cstmfldssrch_is_active = true;
				remove_filter( 'posts_join', 'cstmfldssrch_join' );
				remove_filter( 'posts_where', 'cstmfldssrch_request' );
			}

			$the_query = new WP_Query( $query_args );

			/* The Loop */
			if ( $the_query->have_posts() && absint( $the_query->found_posts ) >= absint( $min_count ) ) {
				add_filter( 'excerpt_length', 'pplrpsts_popular_posts_excerpt_length' );
				add_filter( 'excerpt_more', 'pplrpsts_popular_posts_excerpt_more' );
				echo $args['before_widget'];
				if ( ! empty( $widget_title ) )
					echo $args['before_title'] . $widget_title . $args['after_title'];
				$post_title_tag = $this->get_post_title_tag( $args['before_title'] ); ?>
				<div class="pplrpsts-popular-posts">
				<?php while ( $the_query->have_posts() ) {
					$the_query->the_post();  ?>
					<div class="clear"></div>
					<article class="post type-post format-standard">
						<header class="entry-header">
							<?php echo "<{$post_title_tag} class=\"pplrpsts_posts_title\">"; ?>
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							<?php echo "</{$post_title_tag}>";
							if ( $show_date || $show_author ) { ?>
								<div class="entry-meta">
									<?php echo __( 'Posted', 'bws-popular-posts' ) . '&nbsp;';
									if ( 1 == $show_date ) {
										_e( 'on', 'bws-popular-posts' ); ?>
										<a href="<?php the_permalink(); ?>" title="<?php the_time('g:i a'); ?>"><span class="entry-date"><?php the_time( 'd F, Y' ); ?></span></a>
									<?php }
									if ( 1 == $show_author ) {
										_e( 'by', 'bws-popular-posts' ) ?>
										<span class="author vcard">
											<a class="url fn n" rel="author" href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>">
												<?php echo get_the_author(); ?>
											</a>
										</span>
									<?php }
									if ( 1 == $show_views ) {
										$views_count = get_post_meta( $post->ID, 'pplrpsts_post_views_count' );
										$views_count = $views_count ? $views_count[0] : 0; ?>
										<br /><span class="pplrpsts_post_count"><?php printf( _n( 'one view', '%s views', $views_count, 'bws-popular-posts' ), $views_count ); ?></span>
									<?php } ?>
								</div><!-- .entry-meta -->
							<?php } ?>
						</header>
						<div class="entry-content">
							<?php if ( 1 == $show_image ) { ?>
								<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>">
									<?php if ( '' == get_the_post_thumbnail() ) { ?>
										<img width="60" height="60" class="attachment-popular-post-featured-image wp-post-image" src="<?php echo $no_preview_img; ?>" />
									<?php } else {
										$check_size = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'popular-post-featured-image' );
										if ( true === $check_size[3] )
											echo get_the_post_thumbnail( $post->ID, 'popular-post-featured-image' );
										else
											echo get_the_post_thumbnail( $post->ID, array( 60, 60 ) );
									} ?>
								</a>
							<?php }
							the_excerpt(); ?>
						</div><!-- .entry-content -->
					</article><!-- .post -->
				<?php } ?>
				</div><!-- .pplrpsts-popular-posts -->
				<?php echo $args['after_widget'];
				remove_filter( 'excerpt_length', 'pplrpsts_popular_posts_excerpt_length' );
				remove_filter( 'excerpt_more', 'pplrpsts_popular_posts_excerpt_more' );
			}
			/* Restore original Post Data */
			wp_reset_postdata();
			if ( isset( $cstmfldssrch_is_active ) ) {
				add_filter( 'posts_join', 'cstmfldssrch_join' );
				add_filter( 'posts_where', 'cstmfldssrch_request' );
			}
		}

		/* Outputs the options form on admin */
		function form( $instance ) {
			global $pplrpsts_excerpt_length, $pplrpsts_excerpt_more, $pplrpsts_options;
			if ( empty( $pplrpsts_options ) )
				$pplrpsts_options = get_option( 'pplrpsts_options' );
			$widget_title	= isset( $instance['widget_title'] ) ? stripslashes( esc_html( $instance['widget_title'] ) ) : $pplrpsts_options['widget_title'];
			$count			= isset( $instance['count'] ) ? intval( $instance['count'] ) : $pplrpsts_options['count'];
			$min_count		= isset( $instance['min_count'] ) ? absint( $instance['min_count'] ) : $pplrpsts_options['min_count'];
			$excerpt_length = $pplrpsts_excerpt_length = isset( $instance['excerpt_length'] ) ? intval( $instance['excerpt_length'] ) : $pplrpsts_options['excerpt_length'];
			$excerpt_more 	= $pplrpsts_excerpt_more = isset( $instance['excerpt_more'] ) ? stripslashes( esc_html( $instance['excerpt_more'] ) ) : $pplrpsts_options['excerpt_more'];
			$no_preview_img = isset( $instance['no_preview_img'] ) ? $instance['no_preview_img'] : $pplrpsts_options['no_preview_img'];
			$order_by		= isset( $instance['order_by'] ) ? $instance['order_by'] : $pplrpsts_options['order_by'];
			$show_views		= isset( $instance['show_views'] ) ? $instance['show_views'] : $pplrpsts_options['show_views'];
			$show_date		= isset( $instance['show_date'] ) ? $instance['show_date'] : $pplrpsts_options['show_date'];
			$show_author	= isset( $instance['show_author'] ) ? $instance['show_author'] : $pplrpsts_options['show_author'];
			$show_image		= isset( $instance['show_image'] ) ? $instance['show_image'] : $pplrpsts_options['show_image'];
			$use_category	= isset( $instance['use_category'] ) ? $instance['use_category'] : $pplrpsts_options['use_category']; ?>
			<p>
				<label for="<?php echo $this->get_field_id( 'widget_title' ); ?>"><?php _e( 'Widget title', 'bws-popular-posts' ); ?>: </label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'widget_title' ); ?>" name="<?php echo $this->get_field_name( 'widget_title' ); ?>" type="text" maxlength="250" value="<?php echo esc_attr( $widget_title ); ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Number of posts', 'bws-popular-posts' ); ?>: </label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" type="number" min="1" max="10000" value="<?php echo esc_attr( $count ); ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'min_count' ); ?>"><?php _e( 'Do not display the block if posts number is less than', 'bws-popular-posts' ); ?>: </label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'min_count' ); ?>" name="<?php echo $this->get_field_name( 'min_count' ); ?>" type="number" min="0" max="9999" value="<?php echo esc_attr( $min_count ); ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'excerpt_length' ); ?>"><?php _e( 'Excerpt length', 'bws-popular-posts' ); ?>: </label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'excerpt_length' ); ?>" name="<?php echo $this->get_field_name( 'excerpt_length' ); ?>" type="number" min="1" max="10000" value="<?php echo esc_attr( $excerpt_length ); ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'excerpt_more' ); ?>"><?php _e( '"Read more" text', 'bws-popular-posts' ); ?>: </label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'excerpt_more' ); ?>" name="<?php echo $this->get_field_name( 'excerpt_more' ); ?>" type="text" maxlength="250" value="<?php echo esc_attr( $excerpt_more ); ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'no_preview_img' ); ?>"><?php _e( 'Default image (full URL), if no featured image is available', 'bws-popular-posts' ); ?>: </label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'no_preview_img' ); ?>" name="<?php echo $this->get_field_name( 'no_preview_img' ); ?>" type="text" maxlength="250" value="<?php echo esc_attr( $no_preview_img ); ?>"/>
			</p>
			<p>
				<?php _e( 'Display', 'bws-popular-posts' ); ?>:<br />
				<label for="<?php echo $this->get_field_id( 'show_views' ); ?>">
					<input id="<?php echo $this->get_field_id( 'show_views' ); ?>" name="<?php echo $this->get_field_name( 'show_views' ); ?>" type="checkbox" value="1"<?php if ( 1 == $show_views ) echo ' checked="checked"'; ?> />
					<?php _e( 'views number', 'bws-popular-posts' ); ?>
				</label><br />
				<label for="<?php echo $this->get_field_id( 'show_date' ); ?>">
					<input id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" type="checkbox" value="1"<?php if ( 1 == $show_date ) echo ' checked="checked"'; ?> />
					<?php _e( 'post date', 'bws-popular-posts' ); ?>
				</label><br />
				<label for="<?php echo $this->get_field_id( 'show_author' ); ?>">
					<input id="<?php echo $this->get_field_id( 'show_author' ); ?>" name="<?php echo $this->get_field_name( 'show_author' ); ?>" type="checkbox" value="1"<?php if ( 1 == $show_author ) echo ' checked="checked"'; ?> />
					<?php _e( 'author', 'bws-popular-posts' ); ?>
				</label><br />
				<label for="<?php echo $this->get_field_id( 'show_image' ); ?>">
					<input id="<?php echo $this->get_field_id( 'show_image' ); ?>" name="<?php echo $this->get_field_name( 'show_image' ); ?>" type="checkbox" value="1"<?php if ( 1 == $show_image ) echo ' checked="checked"'; ?> />
					<?php _e( 'featured image', 'bws-popular-posts' ); ?>
				</label>
			</p>
			<p>
				<?php _e( 'Order by number of', 'bws-popular-posts' ); ?>:<br />
				<label><input name="<?php echo $this->get_field_name( 'order_by' ); ?>" type="radio" value="comment_count" <?php if ( 'comment_count' == esc_attr( $order_by ) ) echo 'checked="checked"'; ?> /> <?php _e( 'comments', 'bws-popular-posts' ); ?></label><br />
				<label><input name="<?php echo $this->get_field_name( 'order_by' ); ?>" type="radio" value="views_count" <?php if ( 'views_count' == esc_attr( $order_by ) ) echo 'checked="checked"'; ?> /> <?php _e( 'views', 'bws-popular-posts' ); ?></label>
			</p>
			<p>
				<?php _e( 'Display popular posts in accordance with current category', 'bws-popular-posts' ); ?>:<br />
				<label for="<?php echo $this->get_field_id( 'use_category' ); ?>">
					<input id="<?php echo $this->get_field_id( 'use_category' ); ?>" name="<?php echo $this->get_field_name( 'use_category' ); ?>" type="checkbox" value="1"<?php if ( 1 == $use_category ) echo ' checked="checked"'; ?> />
				</label>
			</p>
		<?php }

		/* Processing widget options on save */
		function update( $new_instance, $old_instance ) {
			global $pplrpsts_options;
			if ( empty( $pplrpsts_options ) )
				$pplrpsts_options = get_option( 'pplrpsts_options' );
			$instance = array();
			$instance['widget_title']	= ( isset( $new_instance['widget_title'] ) ) ? stripslashes( esc_html( $new_instance['widget_title'] ) ) : $pplrpsts_options['widget_title'];
			$instance['count']			= ( ! empty( $new_instance['count'] ) ) ? intval( $new_instance['count'] ) : $pplrpsts_options['count'];
			$instance['min_count']		= ( ! empty( $new_instance['min_count'] ) ) ? intval( $new_instance['min_count'] ) : $pplrpsts_options['min_count'];
			$instance['excerpt_length'] = ( ! empty( $new_instance['excerpt_length'] ) ) ? intval( $new_instance['excerpt_length'] ) : $pplrpsts_options['excerpt_length'];
			$instance['excerpt_more']	= ( ! empty( $new_instance['excerpt_more'] ) ) ? stripslashes( esc_html( $new_instance['excerpt_more'] ) ) : $pplrpsts_options['excerpt_more'];
			$instance["use_category"]	= isset( $new_instance["use_category"] ) ? absint( $new_instance["use_category"] ) : 0;

			$show_options = array( 'views', 'date', 'author', 'image' );
			foreach ( $show_options as $item )
				$instance["show_{$item}"] = isset( $new_instance["show_{$item}"] ) ? absint( $new_instance["show_{$item}"] ) : 0;

			if ( ! empty( $new_instance['no_preview_img'] ) && pplrpsts_is_200( $new_instance['no_preview_img'] ) && getimagesize( $new_instance['no_preview_img'] ) )
				$instance['no_preview_img'] = $new_instance['no_preview_img'];
			else
				$instance['no_preview_img'] = $pplrpsts_options['no_preview_img'];
			$instance['order_by'] 		= ( ! empty( $new_instance['order_by'] ) ) ? $new_instance['order_by'] : $pplrpsts_options['order_by'];
			return $instance;
		}

		function get_post_title_tag( $widget_tag ) {
			preg_match( '/h[1-5]{1}/', $widget_tag, $matches );

			if ( empty( $matches ) )
				return 'h1';

			$number = absint( preg_replace( '/h/', '', $matches[0] ) );
			$number ++;
			return "h{$number}";
			return 1;
		}
	}
}

/* Filter the number of words in an excerpt */
if ( ! function_exists ( 'pplrpsts_popular_posts_excerpt_length' ) ) {
	function pplrpsts_popular_posts_excerpt_length( $length ) {
		global $pplrpsts_excerpt_length;
		return $pplrpsts_excerpt_length;
	}
}

/* Filter the string in the "more" link displayed after a trimmed excerpt */
if ( ! function_exists ( 'pplrpsts_popular_posts_excerpt_more' ) ) {
	function pplrpsts_popular_posts_excerpt_more( $more ) {
		global $pplrpsts_excerpt_more;
		return $pplrpsts_excerpt_more;
	}
}

if ( ! function_exists( 'pplrpsts_admin_scripts' ) ) {
	function pplrpsts_admin_scripts() {
		if ( isset( $_REQUEST['page'] ) && 'popular-posts.php' == $_REQUEST['page'] ) {
			bws_enqueue_settings_scripts();
			bws_plugins_include_codemirror();
		}
	}
}

/* Proper way to enqueue scripts and styles */
if ( ! function_exists ( 'pplrpsts_wp_head' ) ) {
	function pplrpsts_wp_head() {
		wp_enqueue_style( 'pplrpsts_stylesheet', plugins_url( 'css/style.css', __FILE__ ) );
	}
}

/* Function to handle action links */
if ( ! function_exists( 'pplrpsts_plugin_action_links' ) ) {
	function pplrpsts_plugin_action_links( $links, $file ) {
		if ( ! is_network_admin() ) {
			/* Static so we don't call plugin_basename on every plugin row. */
			static $this_plugin;
			if ( ! $this_plugin )
				$this_plugin = plugin_basename(__FILE__);

			if ( $file == $this_plugin ) {
				$settings_link = '<a href="admin.php?page=popular-posts.php">' . __( 'Settings', 'bws-popular-posts' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
		}
		return $links;
	}
}

/* Add costom links for plugin in the Plugins list table */
if ( ! function_exists ( 'pplrpsts_register_plugin_links' ) ) {
	function pplrpsts_register_plugin_links( $links, $file ) {
		$base = plugin_basename(__FILE__);
		if ( $file == $base ) {
			if ( ! is_network_admin() )
				$links[] = '<a href="admin.php?page=popular-posts.php">' . __( 'Settings', 'bws-popular-posts' ) . '</a>';
			$links[] = '<a href="https://support.bestwebsoft.com" target="_blank">' . __( 'FAQ', 'bws-popular-posts' ) . '</a>';
			$links[] = '<a href="https://support.bestwebsoft.com">' . __( 'Support', 'bws-popular-posts' ) . '</a>';
		}
		return $links;
	}
}

/* Register a widget */
if ( ! function_exists ( 'pplrpsts_register_widgets' ) ) {
	function pplrpsts_register_widgets() {
		register_widget( 'PopularPosts' );
	}
}

/* Function for to gather information about viewing posts */
if ( ! function_exists ( 'pplrpsts_set_post_views' ) ) {
	function pplrpsts_set_post_views( $pplrpsts_post_ID ) {
		global $post;

		if ( empty( $pplrpsts_post_ID ) && ! empty( $post ) ) {
			$pplrpsts_post_ID = $post->ID;
		}

		/* Check post type */
		if ( @get_post_type( $pplrpsts_post_ID ) != 'post' )
			return;

		$pplrpsts_count = get_post_meta( $pplrpsts_post_ID, 'pplrpsts_post_views_count', true );
		if ( $pplrpsts_count == '' ) {
			delete_post_meta( $pplrpsts_post_ID, 'pplrpsts_post_views_count' );
			add_post_meta( $pplrpsts_post_ID, 'pplrpsts_post_views_count', '1' );
		} else {
			$pplrpsts_count++;
			update_post_meta( $pplrpsts_post_ID, 'pplrpsts_post_views_count', $pplrpsts_count );
		}
	}
}

/* Check if image status = 200 */
if ( ! function_exists ( 'pplrpsts_is_200' ) ) {
	function pplrpsts_is_200( $url ) {
		if ( filter_var( $url, FILTER_VALIDATE_URL ) === FALSE )
			return false;

		$options['http'] = array(
				'method' => "HEAD",
				'ignore_errors' => 1,
				'max_redirects' => 0
		);
		$body = file_get_contents( $url, NULL, stream_context_create( $options ) );
		sscanf( $http_response_header[0], 'HTTP/%*d.%*d %d', $code );
		return $code === 200;
	}
}

/* add help tab  */
if ( ! function_exists( 'pplrpsts_add_tabs' ) ) {
	function pplrpsts_add_tabs() {
		$screen = get_current_screen();
		$args = array(
			'id' 			=> 'pplrpsts',
			'section' 		=> ''
		);
		bws_help_tab( $screen, $args );
	}
}

/* add admin notices */
if ( ! function_exists ( 'pplrpsts_admin_notices' ) ) {
	function pplrpsts_admin_notices() {
		global $hook_suffix, $pplrpsts_options;
		
		$admin_pages = array( 'widgets.php', 'plugins.php', 'update-core.php' );
		if ( in_array( $hook_suffix, $admin_pages ) || ( isset( $_GET['page'] ) && "popular-posts.php" == $_GET['page'] ) ) {
			if ( empty( $pplrpsts_options ) )
				pplrpsts_set_options();

			if ( ! $pplrpsts_options['display_not_supported_notice'] )
				return;

			if ( isset( $_POST['bws_hide_not_supported_notice'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'bws_settings_nonce_name' ) ) {
				$pplrpsts_options['display_not_supported_notice'] = 0;
				update_option( 'pplrpsts_options', $pplrpsts_options );
				return;
			} ?>
			<div class="updated" style="padding: 0; margin: 0; border: none; background: none;">
				<div class="bws_banner_on_plugin_page bws_banner_to_settings">
					<div class="icon">
						<img title="" src="<?php echo plugins_url( 'images/popular-posts-icon.png', __FILE__ ); ?>" alt="" />
					</div>
					<div class="text">
						<strong><?php printf( __( '%s becomes %s plugin', 'bestwebsoft' ), 'Popular Posts', 'Relevant' ); ?></strong>
						<br />
						<?php printf( __( '%s plugin is now a part of %s plugin. It will be no longer supported (updates will be unavailable) starting from July 2017. Install %s plugin now to automatically apply your current settings and get new amazing features.', 'bestwebsoft' ), 'Popular Posts', 'Relevant – Related, Featured, Latest, and Popular Posts', 'Relevant' ); ?>
						<br />
						<a href="https://wordpress.org/plugins/relevant/" target="_blank"><?php _e( 'Install Now', 'bestwebsoft' ); ?></a>
					</div>
					<form action="" method="post">
						<button class="notice-dismiss bws_hide_settings_notice" title="<?php _e( 'Close notice', 'bestwebsoft' ); ?>"></button>
						<input type="hidden" name="bws_hide_not_supported_notice" value="hide" />
						<?php wp_nonce_field( plugin_basename( __FILE__ ), 'bws_settings_nonce_name' ); ?>
					</form>
				</div>
			</div>
		<?php }
	}
}

/**
 * Delete plugin options
 */
if ( ! function_exists( 'pplrpsts_plugin_uninstall' ) ) {
	function pplrpsts_plugin_uninstall() {
		global $wpdb;
		/* Delete options */
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$old_blog = $wpdb->blogid;

			/* Get all blog ids */
			$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				$allposts = get_posts( 'meta_key=pplrpsts_post_views_count' );

				foreach( $allposts as $postinfo ) {
					delete_post_meta( $postinfo->ID, 'pplrpsts_post_views_count' );
				}
				delete_option( 'pplrpsts_options' );
			}
			switch_to_blog( $old_blog );
		} else {
			$allposts = get_posts( 'meta_key=pplrpsts_post_views_count' );

			foreach( $allposts as $postinfo ) {
				delete_post_meta( $postinfo->ID, 'pplrpsts_post_views_count' );
			}
			delete_option( 'pplrpsts_options' );
		}

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );
		bws_delete_plugin( plugin_basename( __FILE__ ) );
	}
}

/* Add option page in admin menu */
add_action( 'admin_menu', 'pplrpsts_admin_menu' );

/* Plugin initialization */
add_action( 'init', 'pplrpsts_init' );
/* Register a widget */
add_action( 'widgets_init', 'pplrpsts_register_widgets' );
/* Plugin initialization for admin page */
add_action( 'admin_init', 'pplrpsts_admin_init' );
add_action( 'plugins_loaded', 'pplrpsts_plugins_loaded' );

/* Additional links on the plugin page */
add_filter( 'plugin_action_links', 'pplrpsts_plugin_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'pplrpsts_register_plugin_links', 10, 2 );
add_action( 'admin_enqueue_scripts', 'pplrpsts_admin_scripts' );
add_action( 'wp_enqueue_scripts', 'pplrpsts_wp_head' );
/* add admin notices */
add_action( 'admin_notices', 'pplrpsts_admin_notices' );
/* Function for to gather information about viewing posts */
add_action( 'wp_head', 'pplrpsts_set_post_views' );

register_uninstall_hook( __FILE__, 'pplrpsts_plugin_uninstall' );