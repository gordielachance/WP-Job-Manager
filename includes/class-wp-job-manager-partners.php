<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WP_Job_Manager_Partners {
    
    function __construct(){

        add_action( 'init', array( $this, 'register_taxonomy' ), 0 );
        
        add_filter( 'job_manager_settings', array( $this, 'settings_options' ) );
        
        add_filter( 'manage_edit-job_listing_columns', array( $this, 'columns' ), 11 );
        
        add_action( "job_listing_partner_add_form_fields", array(&$this,'extra_field_url_display' ) );
        add_action( "job_listing_partner_edit_form_fields", array(&$this,'extra_field_url_display' ) );
        add_action( 'edited_job_listing_partner', array(&$this,'extra_fields_save', 10, 2 ) );
        add_action( 'created_job_listing_partner', array(&$this,'extra_fields_save', 10, 2 ) );
        
        add_action( 'single_job_listing_start', 'job_listing_partners_display', 30 );
        
        if ( get_option( 'job_manager_enable_partners' ) ) {
                add_action( "restrict_manage_posts", array( $this, "jobs_by_partner" ) );
        }
        
    }
    
    function settings_options( $settings) {
        
        $settings['job_listings'][1][] = array(
            'name'       => 'job_manager_enable_partners',
            'std'        => '1',
            'label'      => __( 'Partners', 'wp-job-manager' ),
            'cb_label'   => __( 'Enable the partners taxonomy', 'wp-job-manager' ),
            'desc'       => __( 'Choose whether to enable partners. Partners must be setup by an admin to allow users to choose them during submission.', 'wp-job-manager' ),
            'type'       => 'checkbox',
            'attributes' => array()
        );
        
        return $settings;
    }
    
    /**
     * register_post_types function.
     *
     * @access public
     * @return void
     */
    public function register_taxonomy() {
        
        if ( post_type_exists( "job_listing" ) ) return;
        if ( !get_option( 'job_manager_enable_partners' ) ) return;

        $admin_capability = 'manage_job_listings';

        $singular  = __( 'Job partner', 'wp-job-manager' );
        $plural    = __( 'Job partners', 'wp-job-manager' );

        if ( current_theme_supports( 'job-manager-templates' ) ) {
                $rewrite   = array(
                        'slug'         => _x( 'job-client', 'Job partner slug - resave permalinks after changing this', 'wp-job-manager' ),
                        'with_front'   => false,
                        'hierarchical' => false
                );
                $public    = true;
        } else {
                $rewrite   = false;
                $public    = false;
        }

        register_taxonomy( "job_listing_partner",
            apply_filters( 'register_taxonomy_job_listing_partner_object_type', array( 'job_listing' ) ),
            apply_filters( 'register_taxonomy_job_listing_partner_args', array(
                'hierarchical' 			=> true,
                'update_count_callback' => '_update_post_term_count',
                'label' 				=> $plural,
                'labels' => array(
                                    'name'              => $plural,
                                    'singular_name'     => $singular,
                                    'menu_name'         => ucwords( $plural ),
                                    'search_items'      => sprintf( __( 'Search %s', 'wp-job-manager' ), $plural ),
                                    'all_items'         => sprintf( __( 'All %s', 'wp-job-manager' ), $plural ),
                                    'parent_item'       => sprintf( __( 'Parent %s', 'wp-job-manager' ), $singular ),
                                    'parent_item_colon' => sprintf( __( 'Parent %s:', 'wp-job-manager' ), $singular ),
                                    'edit_item'         => sprintf( __( 'Edit %s', 'wp-job-manager' ), $singular ),
                                    'update_item'       => sprintf( __( 'Update %s', 'wp-job-manager' ), $singular ),
                                    'add_new_item'      => sprintf( __( 'Add New %s', 'wp-job-manager' ), $singular ),
                                    'new_item_name'     => sprintf( __( 'New %s Name', 'wp-job-manager' ),  $singular )
            ),
                'show_ui' 				=> true,
                'public' 	     		=> $public,
                'capabilities'			=> array(
                    'manage_terms' 		=> $admin_capability,
                    'edit_terms' 		=> $admin_capability,
                    'delete_terms' 		=> $admin_capability,
                    'assign_terms' 		=> $admin_capability,
                ),
                'rewrite'                       => $rewrite,
            ) )
        );

    }
    
    function extra_field_url_display( $client ) {

        $partner_url = get_the_partner_url($client);

        ?>
        <tr class="form-field term-description-wrap">
            <th scope="row">
                <label for="partner_url"><?php _e( 'Partner URL', 'wp-job-manager' );?></label>
            </th>
            <td>
                <input name="term_meta[partner-url]" id="partner_url" type="text" value="<?php echo esc_url($partner_url); ?>" size="40" />
            </td>
        </tr>   
        <?php
    }

    function extra_fields_save( $term_id ) {

        if ( !isset( $_POST['term_meta'] ) ) return;

        foreach ( $_POST['term_meta'] as $slug => $value){

            switch($slug){
                default:
                break;
            }

            update_term_meta( $term_id, $slug, $value );
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

            $columns["job_listing_partner"] = __( "Partners", 'wp-job-manager' );

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
                case "job_listing_partner":
                        the_job_partners_list( $post );
                break;
            }
    }
    
    /**
     * Show client dropdown
     */
    //TO FIX
    /*
    public function jobs_by_partner() {
            global $typenow, $wp_query;

        if ( $typenow != 'job_listing' || ! taxonomy_exists( 'job_listing_partner' ) ) {
            return;
        }

        include_once( JOB_MANAGER_PLUGIN_DIR . '/includes/class-wp-job-manager-client-walker.php' );

            $r                 = array();
            $r['pad_counts']   = 1;
            $r['hierarchical'] = 1;
            $r['hide_empty']   = 0;
            $r['show_count']   = 1;
            $r['selected']     = ( isset( $wp_query->query['job_listing_partner'] ) ) ? $wp_query->query['job_listing_partner'] : '';
            $r['menu_order']   = false;
            $terms             = get_terms( 'job_listing_partner', $r );
            $walker            = new WP_Job_Manager_Partner_Walker;

            if ( ! $terms ) {
                    return;
            }

            $output  = "<select name='job_listing_partner' id='dropdown_job_listing_partner'>";
            $output .= '<option value="" ' . selected( isset( $_GET['job_listing_partner'] ) ? $_GET['job_listing_partner'] : '', '', false ) . '>' . __( 'Select client', 'wp-job-manager' ) . '</option>';
            $output .= $walker->walk( $terms, 0, $r );
            $output .= "</select>";

            echo $output;
    }
    */
    
}

function get_the_partner_url($client){
    return get_term_meta( $client->term_id, 'partner-url', true );
}

function get_the_job_partners($post = null){
    $post = get_post( $post );

    $partners = wp_get_post_terms( $post->ID, 'job_listing_partner', array(
            'orderby'    => 'count',
            'hide_empty' => 0
        )   
    );    
    
    //exclude client from partners list
    if ( $client = get_the_job_client($post) ){
        $partners = wp_list_filter( $partners, array('term_id'=>$client->term_id),'NOT');
    }
 
    return $partners;
    
}

function the_job_partners_list($post = null,$separator = ','){
    
    $partners = get_the_job_partners($post);
    if (!$partners) return;

    $links = array();
    
    foreach($partners as $partner){
        $links[] = get_job_partner_link($partner);
    }
    
    echo implode($separator,$links);
    
}

function get_job_partner_link($partner){
    $partner_name = $partner->name;

    if ($partner_url = get_the_partner_url($partner)){
        $item = sprintf('<a class="job_partner" href="%s" itemscope itemtype="http://data-vocabulary.org/Organization">%s</a>',$partner_url,$partner_name);
    }else{
        $item = sprintf('<span class="job_partner" itemscope itemtype="http://data-vocabulary.org/Organization">%s</span>',$partner_name);
    }

    print_r($item);
    echo"<br/>";
}

/**
 * Displays job company data on the single job page
 */
function job_listing_partners_display() {
	get_job_manager_template( 'content-single-job_listing-partners.php', array() );
}

new WP_Job_Manager_Partners();
