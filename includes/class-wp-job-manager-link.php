<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WP_Job_Link {
    
    function __construct(){
        
        add_filter( 'job_manager_settings', array( $this, 'settings_options' ) );
        add_filter( 'manage_edit-job_listing_columns', array( $this, 'columns' ), 11 );
        
        add_filter( 'job_manager_job_listing_data_fields', array( $this, 'register_field' ) );
        add_action( 'manage_job_listing_posts_custom_column', array( $this, 'custom_columns' ), 2 );

        if ( get_option( 'job_manager_enable_link' ) ) {
            //add_action( 'add_meta_boxes', array( $this, 'metabox_register' ) );
            //add_action( 'job_manager_save_job_listing', array( $this, 'metabox_save' ), 20, 2 );
        }
        
    }
    
    function register_field( $fields ){
        $fields['_job_link'] = array(
                'label'       => __( 'Job Link', 'wp-job-manager' ),
                'placeholder' => __( 'Post ID or URL to this job', 'wp-job-manager' ),
                'value'       => metadata_exists( 'post', $post->ID, '_job_link' ) ? get_post_meta( $post->ID, '_job_link', true ) : null,
                'description' => __( 'Post ID, or external URL', 'wp-job-manager' ),
                'priority'    => 2
        );
        return $fields;
    }
    
    function settings_options( $settings) {
        $settings['job_link'][1][] = array(
            'name'       => 'job_manager_enable_link',
            'std'        => '1',
            'label'      => __( 'Link', 'wp-job-manager' ),
            'cb_label'   => __( 'Enable the link metabox', 'wp-job-manager' ),
            'desc'       => __( 'Choose whether to enable job links. Job links must be setup by an admin to allow users to choose them during submission.', 'wp-job-manager' ),
            'type'       => 'checkbox',
            'attributes' => array()
        );
        return $settings;
    }
    
    /**
     * columns function.
     *
     * @param array $columns
     * @return array
     */
    public function columns( $columns ) {

            if ( ! get_option( 'job_manager_enable_link' ) ) return $columns;

            $columns["job_link"]         = __( "Link", 'wp-job-manager' );

            return $columns;
    }
    
    /**
     * custom_columns function.
     *
     * @access public
     * @param mixed $column
     * @return void
     */
    public function custom_columns( $column ) {
            global $post;

            switch ( $column ) {
                    case "job_link":
                            the_job_link( $post );
                    break;
            }
    }
    
    /**
     * add_meta_boxes function.
     *
     * @access public
     * @return void
     */
    public function metabox_register() {
            global $wp_post_types;
            add_meta_box( 'job_link', sprintf( __( '%s link', 'wp-job-manager' ), $wp_post_types['job_listing']->labels->singular_name ), array( $this, 'metabox_callback' ), 'job_listing', 'side');
    }
    
    function metabox_callback( $post ) {
        global $post, $thepostid;

        $thepostid = $post->ID;
        ?>

        <div class="wp_job_manager_link_meta_data">
            <label><?php _e( 'Job Link', 'wp-job-manager' ); ?></label>
            <input name="job_listing_link" type="text" value="<?php echo get_post_meta($thepostid, "job_listing_link", true); ?>" placeholder="<?php _e( 'Post ID or link', 'wp-job-manager' ); ?>">
            <?php //wp_nonce_field( 'save_meta_data', 'job_manager_link_nonce' ); ?>
        </div>
        <?php
    }
    
    function metabox_save( $post_id, $post ) {

        $job_link = isset($_POST['job_listing_link']) ? $_POST['job_listing_link'] : null;

        if ($job_link !== 0 && $job_link !== null) {
            $job_link = sanitize_text_field( urldecode( $job_link ) );
        }else{
            $job_link = null;
        }

        if ($job_link){
            update_post_meta( $post_id, job_listing_link, $job_link );
        }else{
            delete_post_meta( $post_id, 'job_listing_link' );
        }

    }
    
    
}

/**
 * the_job_link function.
 * @param  boolean $map_link whether or not to link to google maps
 * @return [type]
 */
function the_job_link( $map_link = true, $post = null ) {
    
	$link = get_the_job_link( $post );
        $post_id = url_to_postid($link);
        
        if ($post_id){
            $title = get_the_title($post_id);
            $link = sprintf('<a href="%s">%s</a>',$link,$title);
        }else{
            $link = sprintf('<a href="%s" target="_blank">%s</a>',$link,$link);
        }

	echo $link;
}

/**
 * get_the_job_link function.
 *
 * @access public
 * @param mixed $post (default: null)
 * @return void
 */
function get_the_job_link( $post = null ) {
	$post = get_post( $post );
	if ( $post->post_type !== 'job_listing' ) {
		return;
	}
        
        if (is_numeric($post->_job_link)){
            $link = get_permalink($post->_job_link);
        }else{
            $link = $post->_job_link;
        }
        
	return apply_filters( 'the_job_link', $link, $post );
}

new WP_Job_Link();
