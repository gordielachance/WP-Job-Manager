<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WP_Job_Manager_Client {
    
    function __construct(){
        
        add_filter( 'job_manager_settings', array( $this, 'settings_options' ) );
        add_filter( 'manage_edit-job_listing_columns', array( $this, 'columns' ), 11 );
        add_action( 'manage_job_listing_posts_custom_column', array( $this, 'custom_columns' ), 2 );
        
        //add_filter( 'job_manager_job_listing_data_fields', array( $this, 'register_field' ) );
        
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

        if ( get_option( 'job_manager_enable_client' ) ) {
                add_action( "restrict_manage_posts", array( $this, "jobs_by_client" ) );
                add_action( 'add_meta_boxes', array( $this, 'metabox_register' ) );
                add_action( 'job_manager_save_job_listing', array( $this, 'metabox_save' ), 20, 2 );
                
        }
        
    }
    
    function settings_options( $settings) {
        $settings['job_listings'][1][] = array(
            'name'       => 'job_manager_enable_client',
            'std'        => '1',
            'label'      => __( 'Client', 'wp-job-manager' ),
            'cb_label'   => __( 'Enable the client box', 'wp-job-manager' ),
            'desc'       => __( 'Choose whether to enable client. Client must be setup by an admin to allow users to choose them during submission.', 'wp-job-manager' ),
            'type'       => 'checkbox',
            'attributes' => array()
        );
        return $settings;
    }
    
    function register_field( $fields ){
        $fields['_job_client'] = array(
                'label'       => __( 'Job Client', 'wp-job-manager' ),
                'placeholder' => __( 'Partner ID', 'wp-job-manager' ),
                'value'       => metadata_exists( 'post', $post->ID, '_job_link' ) ? get_post_meta( $post->ID, '_job_client', true ) : null,
                'description' => __( 'Partner ID which is the final client', 'wp-job-manager' ),
                'priority'    => 2
        );
        return $fields;
    }
    
	/**
	 * admin_enqueue_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		global $wp_scripts;

		$screen = get_current_screen();

		if ( in_array( $screen->id, apply_filters( 'job_manager_admin_screen_ids', array( 'edit-job_listing', 'job_listing', 'job_listing_page_job-manager-settings', 'job_listing_page_job-manager-addons' ) ) ) ) {
			$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';
			wp_enqueue_script( 'job_manager_admin_js', JOB_MANAGER_PLUGIN_URL. '/assets/js/admin-job-client.js', array( 'jquery'), JOB_MANAGER_VERSION, true );

		}

	}
    
    /**
     * columns function.
     *
     * @param array $columns
     * @return array
     */
    public function columns( $columns ) {

            if ( ! get_option( 'job_manager_enable_partners' ) ) return $columns;

            $columns["job_client"]          = __( "Client", 'wp-job-manager' );

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
                    case "job_client":
                            the_job_client( $post );
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
            add_meta_box( 'job_client', sprintf( __( '%s client', 'wp-job-manager' ), $wp_post_types['job_listing']->labels->singular_name ), array( $this, 'metabox_callback' ), 'job_listing', 'side');
    }
    
    function metabox_callback( $post ) {
        global $post, $thepostid;

        $thepostid = $post->ID;
        ?>

        <div class="wp_job_manager_client_meta_data">
            <label><?php _e( 'Partner ID', 'wp-job-manager' ); ?></label>
            <input name="job_listing_client" type="text" value="<?php echo get_post_meta($thepostid, "job_listing_client", true); ?>" placeholder="<?php _e( 'Partner ID', 'wp-job-manager' ); ?>">
            <?php //wp_nonce_field( 'save_meta_data', 'job_manager_link_nonce' ); ?>
        </div>
        <?php
    }
    
    function metabox_save( $post_id, $post ) {

        $client_id = isset($_POST['job_listing_client']) ? $_POST['job_listing_client'] : null;



        if ($client_id !== 0 && $client_id !== null) {
            //exists
        }else{
            $client_id = null;
        }

        if ($client_id){
            update_post_meta( $post_id, 'job_listing_client', $client_id );
        }else{
            delete_post_meta( $post_id, 'job_listing_client' );
        }


    }
    

    
    /**
     * Show client dropdown
     */
    //TO FIX
    /*
    public function jobs_by_client() {
            global $typenow, $wp_query;

        if ( $typenow != 'job_listing' || ! taxonomy_exists( 'job_listing_client' ) ) {
            return;
        }

        include_once( JOB_MANAGER_PLUGIN_DIR . '/includes/class-wp-job-manager-client-walker.php' );

            $r                 = array();
            $r['pad_counts']   = 1;
            $r['hierarchical'] = 1;
            $r['hide_empty']   = 0;
            $r['show_count']   = 1;
            $r['selected']     = ( isset( $wp_query->query['job_listing_client'] ) ) ? $wp_query->query['job_listing_client'] : '';
            $r['menu_order']   = false;
            $terms             = get_terms( 'job_listing_client', $r );
            $walker            = new WP_Job_Manager_Partner_Walker;

            if ( ! $terms ) {
                    return;
            }

            $output  = "<select name='job_listing_client' id='dropdown_job_listing_client'>";
            $output .= '<option value="" ' . selected( isset( $_GET['job_listing_client'] ) ? $_GET['job_listing_client'] : '', '', false ) . '>' . __( 'Select client', 'wp-job-manager' ) . '</option>';
            $output .= $walker->walk( $terms, 0, $r );
            $output .= "</select>";

            echo $output;
    }
    */
    
}

function the_job_client($post = null){
    $client = get_the_job_client($post);
    if (!$client) return;
    echo get_job_partner_link($client);
}

function get_the_job_client($post = null){
    $post = get_post( $post );
    $client_id = get_post_meta($post->ID,"job_listing_client",true);
    
    if (!$client_id) return;
    
    $client = get_term( $client_id, 'job_listing_partner' );
    return $client;
}

new WP_Job_Manager_Client();
