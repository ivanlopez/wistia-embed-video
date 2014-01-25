<?php 

/*
Plugin Name: Wistia Embed Video
Plugin URI: http://IvanLopezDeveloper.com/
Description: Add a Wistia video embed button to the WordPress post editor. Select one of you Wistia videos from the list.
Version: 0.1
Author: Ivan Lopez
Author URI: http://IvanLopezDeveloper.com/
*/

/**
 * Copyright (c) 2014 Ivan Lopez. All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

if( !defined('ABSPATH') ) exit;

define('IL_Wistia_DIR', plugin_dir_path( __FILE__ ) );
define('IL_Wistia_URL', plugin_dir_url( __FILE__ ) );

/**
* Wistia Class
*/
class IL_Wistia_Embed_Video  
{
	/**
	* Plugin Settings
	*
	* @since 1.0
	*/
	private $settings;

	function __construct()
	{
		add_action( 'init', array( $this, 'init_plugin') , 9999 );
		add_action( 'admin_init', array( $this, 'register_api_settings' ) );
		$this->settings = (array) get_option( 'wev_setting' );

		if(isset($this->settings['api_key']))
		{
			add_filter( 'cmb_meta_boxes', array( $this, 'wistia_metaboxes' ));
			add_filter('the_content', array($this, 'load_video_into_post'), 1003);
		}
	}

	/**
	* Init the plugin
	*
	* @since 1.0
	*
	* @return void
	*/
	public function init_plugin()
	{
		if ( ! class_exists( 'cmb_Meta_Box' ) )
			require_once 'includes/init.php';
	}

	/**
	* Register setting fileds in the media page
	*
	* @since 1.0
	*
	* @return void
	*/
	public function register_api_settings()
	{
		add_settings_section(
			'wev_api_section',
			__( 'Wistia API', 'wev' ),
			array( $this, 'generate_api_section' ),
			'media'
		);
	 	
	 	add_settings_field(
			'wev_api_key',
			__( 'API Password', 'wev' ),
			array( $this, 'generate_api_key_field' ),
			'media',
			'wev_api_section'
		);

		register_setting( 'media', 'wev_setting' , array($this, 'sanitize_api_key') );
	}

	/**
	 * Wistia API Section
	 *
	 * @since    1.0.0
	 *
	 * @return string
	 */
	public function generate_api_section() {
	 	echo '<p>'.  __( 'You can generate an API password for your account from the API area in your Account Dashboard.', 'wev' ) .'</p>';
	}

	/**
	 * Register API field
	 *
	 * @since    1.0.0
	 *
	 * @return string
	 */
	public function generate_api_key_field() {
   		$api_key = (isset( $this->settings['api_key'] )) ? esc_attr( $this->settings['api_key'] ) : "";
	 	echo '<input name="wev_setting[api_key]" id="wev_api_key" type="text"  class="regular-text"  value="' . $api_key . '" /> ';
	}

	/**
	 * Sanitize API key
	 *
	 * @since    1.0.0
	 *
	 * @return string
	 */
	public function sanitize_api_key( $input )
	{
		$output = array();  
		      
		foreach( $input as $key => $value ) {  
			if( isset( $input[$key] ) ) 
			    $output[$key] = trim(strip_tags( stripslashes( $input[ $key ] ) ));  
		}
		
		return  $output;  
	}

	/**
	* Genereate Metaboxes for wistia 
	*
	* @since 1.0
	*
	* @return string
	*/
	public function wistia_metaboxes(array $meta_boxes)
	{
		$meta_boxes['wistia'] = array(
			'id'         => 'IL_Wistia_metaboxes',
			'title'      => __( 'Wistia Videos', 'wev' ),
			'pages'      => array( 'post'), 
			'context'    => 'normal',
			'priority'   => 'high',
			'show_names' => true,
			'fields'     => array(
				array(
					'name'    => __( 'Video', 'wev' ),
					'id'      => '_wev_id',
					'type'    => 'select',
					'options' => $this->load_wistia_videos()
				),
				array(
					'name'    => __( 'Placement', 'wev' ),
					'desc'    => __( 'select location to embed video', 'wev' ),
					'id'      => '_wev_placement',
					'type'    => 'radio_inline',
					'options' => array(
						array( 'name' => __( 'Before Content', 'wev' ), 'value' => 'before', ),
						array( 'name' => __( 'After Content', 'wev' ), 'value' => 'after', ),
					),
				),
			)
		);

		return $meta_boxes;
	}

	/**
	* Embed video into post
	*
	* @since 1.0
	*
	* @return string
	*/
	public function load_video_into_post($content)
	{
		global $post;

		if (get_post_type() == "post" && $video_id = get_post_meta($post->ID, '_wev_id', true) ) {

			$placement = get_post_meta($post->ID, '_wev_placement', true);
	
			$video_content = '<div id="wistia_'.$video_id.'" class="wistia_embed" style="width:640px;height:360px;">&nbsp;</div>
						<script charset="ISO-8859-1" src="//fast.wistia.com/assets/external/E-v1.js"></script>
						<script>
							wistiaEmbed = Wistia.embed("'.$video_id.'", {
							  videoFoam: true
							});
						</script>';

			if ($placement == 'before')
				$content = $video_content  . $content ;
			else
				$content .= $video_content ;
		}
		
		return $content;
	}

	/**
	* Load all videos associated to the Wistia API password
	*
	* @since 1.0
	*
	* @return array
	*/
	public function load_wistia_videos()
	{
		$results[] = array( 'name' => 'Select One', 'value' => 0 );
   		$api_key = esc_attr( $this->settings['api_key'] );
		
		if ( false === ( $videos = get_transient( 'wistia_videos' ) ) ) {
			$videos = wp_remote_retrieve_body( wp_remote_get('https://api.wistia.com/v1/medias.json?api_password=' . $api_key ) ); 
			set_transient( 'wistia_videos', $videos,  24 * HOUR_IN_SECONDS  );
		}

		foreach (json_decode($videos) as $video) {
			$results[] = array( 'name' => $video->name, 'value' =>  $video->hashed_id );
		}

		return $results;
	}

}

/**
* Promp user to install and activate dependant plugin
*
* @since 1.0
*
* @return void
*/
function il_check_plugin_installed(){
	global $pagenow;

    if ( $pagenow == 'plugins.php' ) 
    {
	    require_once ABSPATH . '/wp-admin/includes/plugin.php';

		deactivate_plugins( __FILE__ );
		$message = sprintf( __( 'Wistia Embed Video has been deactivated as it requires the <a href="%s">Wistia WordPress Plugin</a>.', 'wev' ), 'http://wordpress.org/plugins/wistia-wordpress-oembed-plugin/' ) . '<br /><br />';
    	
		if ( file_exists( WP_PLUGIN_DIR . '/wistia-wordpress-oembed-plugin/wistia-wordpress-oembed-plugin.php' ) ) {
			$activate_url = wp_nonce_url( 'plugins.php?action=activate&plugin=wistia-wordpress-oembed-plugin/wistia-wordpress-oembed-plugin.php', 'activate-plugin_wistia-wordpress-oembed-plugin/wistia-wordpress-oembed-plugin.php' );
			$message .= sprintf( __( 'It appears to already be installed. <a href="%s">Click here to activate it.</a>', 'wev' ), $activate_url );
		}
		else {
			$install_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=wistia-wordpress-oembed-plugin' ), 'install-plugin_wistia-wordpress-oembed-plugin' );
			$message .= sprintf( __( '<a href="%s">Click here to install it automatically.</a> Then activate it. Once it has been activated, you can activate Wistia Embed Video.', 'wev' ), $install_url );
		}

  		echo '<div class="error"><p>'.$message.'</p></div>';
    }

}

/**
* Check if depended plugin is active and activate plugin
*
* @since 1.0
*
* @return void
*/
function il_load_plugin()
{
	if( !function_exists( 'WistiaAntiMangler' ) && !class_exists( 'WistiaAntiMangler' ) ) 
		add_action('admin_notices', 'il_check_plugin_installed');

	$GLOBALS['il_wistia'] = new IL_Wistia_Embed_Video();
}

add_action( 'plugins_loaded', 'il_load_plugin' );