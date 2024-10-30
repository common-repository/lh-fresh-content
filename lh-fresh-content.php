<?php
/**
 * Plugin Name: LH Fresh Content
 * Description: Let your visitors know they are viewing stale content
 * Plugin URI: https://lhero.org/portfolio/lh-fresh-content/
 * Version: 1.11
 * Author: Peter Shaw
 * Author URI: https://shawfactor.com
 * License: GPLv2 or later
 * Text Domain: lh_fresh_content
 * Domain Path: /languages
*/



if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
* LH Fresh Content plugin class
*/


if (!class_exists('LH_Fresh_content_plugin')) {

class LH_Fresh_content_plugin {

    private static $instance;

    static function return_plugin_namespace(){

        return 'lh_fresh_content';

    }
    
    
    static function return_opt_name(){
        
        return self::return_plugin_namespace().'-options';    
        
    }

    static function return_selector_name(){
        
        return self::return_plugin_namespace().'-selector';      
        
    }

    static function return_message_name(){
        
        return self::return_plugin_namespace().'-message';    
        
    }
    
    static function get_post_ids(){
    
        global $wp_query;

        $post_ids = wp_list_pluck( $wp_query->posts, 'ID' );

        if (!empty($post_ids)){
    
            return  $post_ids;   

        } else {
    
            return false;
    
        }
    
    }

    static function curpageurl() {
        
    	$pageURL = 'http';
    
    	if ((isset($_SERVER["HTTPS"])) && ($_SERVER["HTTPS"] == "on")){
    	    
    		$pageURL .= "s";
    		
        }
    
    	$pageURL .= "://";
    
    	if (($_SERVER["SERVER_PORT"] != "80") and ($_SERVER["SERVER_PORT"] != "443")){
    	    
    		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    		
    
    	} else {
    	    
    		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    
        }
    
    	return $pageURL;
    }

    static function isValidURL($url){
        
        if (empty($url)){
            
            return false;
            
        }  else {
    
            return (bool)parse_url($url);
            
        }
        
    }
    
    static function return_url_hash(){
        
        return apply_filters(self::return_plugin_namespace().'_return_url_hash', false);
        
    }

    static function setup_crons(){
        
        wp_clear_scheduled_hook( self::return_plugin_namespace().'_initial' );
    
    
        if (! wp_next_scheduled( self::return_plugin_namespace().'_initial' )) {
            
            //schedule a new event to be fired asap
            wp_schedule_single_event( time() + + wp_rand( 0, 100 ), self::return_plugin_namespace().'_initial'  );
            
        }
    
    }

    static function is_wplogin(){
        
        $ABSPATH_MY = str_replace(array('\\','/'), DIRECTORY_SEPARATOR, ABSPATH);
        $simple_login_url = remove_query_arg(array_keys($_GET),wp_login_url());
        $simple_current_url = remove_query_arg(array_keys($_GET), self::curpageurl());
        
        
        if ((in_array($ABSPATH_MY.'wp-login.php', get_included_files()) || in_array($ABSPATH_MY.'wp-register.php', get_included_files()) ) || (isset($_GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'wp-login.php') || $_SERVER['PHP_SELF']== '/wp-login.php'){
            
            return true;
            
        } elseif ($simple_current_url == $simple_login_url){
            
            return true;
            
        } else {
            
            return false;
        }
        
    }

    static function do_last_modified_meta_tag(){
    
        if (is_singular()){
    
            $the_date = apply_filters(self::return_plugin_namespace().'_return_the_date_singular', get_the_modified_date("c"));
    
        } else {
    
            if ($post_ids = self::get_post_ids()){
    
                $post_ids_imploded = implode(',' , $post_ids );
        
                global $wpdb;
    
                $the_var = $wpdb->get_var("SELECT GREATEST(post_modified_gmt, post_date_gmt) d FROM $wpdb->posts WHERE ID in (".$post_ids_imploded.") ORDER BY d DESC LIMIT 1");
    
                $the_date = apply_filters(self::return_plugin_namespace().'_return_the_date_archive', mysql2date("c", $the_var, 0), $post_ids);
    
            }

        }

        if (!empty($the_date)){
    
            return '<meta http-equiv="last-modified" content="'.$the_date.'" />'."\n";

        } else {
    
            return false;    
    
        }
    
    }


    static function do_etag_meta_tag(){

        if (self::return_url_hash()){
        
            return '<meta http-equiv="ETag" content="'.self::return_url_hash().'" />'."\n";
        
        } else {
    
            return false;    
    
        }

    }
    
    static function check_string_contains_class($string){
        
        libxml_use_internal_errors(true);    
        $dom = new DOMDocument;
    
        $return = false;
    
        // load the HTML into the DomDocument object (this would be your source HTML)
        $dom->loadHTML('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>'.$string.'</body></html>');    
            
        //Find all anchors
        $anchors = $dom->getElementsByTagName('a'); 
        
         //Iterate though images
        foreach ($anchors AS $anchor) {
        
            $class = $anchor->getAttribute('class');
        
            if (!empty($class) && str_contains( $class , 'lh_fresh_content-refresh' ) ){
                
                $return = true;
                
            }
        
        }
        
        libxml_clear_errors();   
        
        return $return;
        
    }

    static function return_default_styles(){
    
        $styles =  '
        #lh_fresh_content-notify {
        position: relative;
        }
        #lh_fresh_content-notify button.'.self::return_plugin_namespace().'-dismiss_button {
        	background: none!important;
        	color: inherit;
        	border: none;
        	padding: 0;
        	font: inherit;
        	cursor: pointer;
        	outline: inherit;
        	position: absolute;
        	top: 0px;
            right: 0px;
        }
        
        #lh_fresh_content-notify span.'.self::return_plugin_namespace().'-dismiss_span {
        	display: none;
        }
        
        #lh_fresh_content-notify button.'.self::return_plugin_namespace().'-dismiss_button::before {
        content: \'x\';
        }
        
        
        ';    
            
        return apply_filters(self::return_plugin_namespace().'_return_default_styles', $styles);    

    }

    static function return_refresh_selector(){
        
        $options = get_option( self::return_opt_name() );        
        
        if (!empty($options[ self::return_selector_name() ]) ){
        
            $selector = $options[ self::return_selector_name() ];
        
        } else {
        
        $selector = 'main';
        
        }    
    
        return $selector;    
        
    }

    static function return_refresh_message(){
        
        $options = get_option( self::return_opt_name() );    
        
        if (!empty($options[ self::return_message_name() ]) && self::check_string_contains_class($options[ self::return_message_name() ])){
            
            $message = $options[ self::return_message_name() ];
            
        } else {
            
            $message = '<p>';
            $message .= __('This page might be out of date. You can try ', self::return_plugin_namespace());
            $message .= '<a class="'.self::return_plugin_namespace().'-refresh" href="">';
            $message .= __('refreshing', self::return_plugin_namespace());
            $message .= '</a>.</p>';
            
        }    
            
        return $message;
        
    }

    static function return_main_div_classes(){
    
        $classes = array();
            
        return apply_filters( self::return_plugin_namespace().'_return_main_div_classes', $classes);
        
    }

    public function initial_cron(){
        
        $options = get_option( self::return_opt_name() );    
    
        if (empty($options)){
            
            $options[ self::return_message_name() ] = self::return_refresh_message();
                
            update_option( self::return_opt_name(), $options );    
            
        }
        
    }

    public function register_core_scripts() {
        
        if (!class_exists('LH_Register_file_class')) {
             
            include_once('includes/lh-register-file-class.php');
            
        }
    
        if (!is_admin() && !wp_doing_ajax() && !wp_is_json_request()){
    
            $add_array = array();
            $add_array['defer']  = 'defer';
            $add_array['id'] = self::return_plugin_namespace().'-script';
            $add_array['data-refresh_selector'] = urlencode(self::return_refresh_selector());
            $add_array['data-refresh_message'] = urlencode(self::return_refresh_message());
            $add_array['data-refresh_div_styles']  = urlencode(self::return_default_styles());
            $add_array['data-refresh_div_classes'] = urlencode(implode(" ", self::return_main_div_classes()));
            $add_array['data-current_user_id'] = get_current_user_id();
        
    
            $lh_fresh_content_core_script = new LH_Register_file_class(  self::return_plugin_namespace().'-script', plugin_dir_path( __FILE__ ).'scripts/lh-fresh-content.js',plugins_url( '/scripts/lh-fresh-content.js', __FILE__ ), true, array(), true, $add_array);
    
            unset($add_array);
    
            $add_array = array();
            $add_array['defer']  = 'defer';
            $add_array['id'] = 'tinyicon';
    
            //$lh_fresh_content_tinycon_script = new LH_Register_file_class(  'tinyicon', plugin_dir_path( __FILE__ ).'scripts/tinycon.js',plugins_url( '/scripts/tinycon.js', __FILE__ ), true, array(), true, $add_array);
    
            unset($add_array);
    
        }
    
    }

    public function render_selector_field($args){
        
        $options = get_option( self::return_opt_name() );    
            
        if (!empty($options[ $args[0] ])){
            
            $value = $options[ $args[0] ];
            
        } else {
            
            $value = false;
            
        }
        
        ?><input type="text" name="<?php echo self::return_opt_name().'['.$args[0].']'; ?>" id="<?php echo $args[0]; ?>" value="<?php echo $value; ?>" size="50" /><?php
        
    }

    public function render_message_editor($args) {
        
        $options = get_option( self::return_opt_name() );
    
    
        if (!empty($options[ $args[0] ])){
        
            $value = $options[ $args[0] ];
        
        } else {
        
            $value = false;
        
        }    
    
        $has_valid_value = self::check_string_contains_class($value);
    
        if (!empty($has_valid_value)){
            
            _e('The Fresh Content message is valid you are right to proceed', self::return_plugin_namespace());
            
        } else {
            
            _e('The Fresh Content message is missing an anchor with a class containing "lh_fresh_content-refresh", this message is being ignored and the default values are being used', self::return_plugin_namespace());    
            
        }
    
    
        $settings = array(
            'media_buttons' => false,
            'textarea_name' => self::return_opt_name().'['.$args[0].']',
        );
            
        wp_editor( $value, self::return_message_name(), $settings);
    
    }

    public function validate_options( $input ) { 
    
        $output = $input;
    
        // Return the array processing any additional functions filtered by this action
        return apply_filters( self::return_plugin_namespace().'_validate_options', $output, $input );
    
    }

    public function reading_setting_callback($arguments){
        
        
        
    }

    public function add_configuration_section(){
        
        add_settings_field( // Option 1
            self::return_selector_name(), // Option ID
            __('Fresh Content Selector', self::return_plugin_namespace()), // Label
            array($this, 'render_selector_field'), // !important - This is where the args go!
            'reading', // Page it will be displayed (Reading Settings)
            self::return_opt_name(), // Name of our section
            array( // The $args
                self::return_selector_name(), // Should match Option ID
            )  
        ); 
        
        add_settings_field( // Option 2
            self::return_message_name(), // Option ID
            __('Fresh Content Message', self::return_plugin_namespace()), // Label
            array($this, 'render_message_editor'), // !important - This is where the args go!
            'reading', // Page it will be displayed (Reading Settings)
            self::return_opt_name(), // Name of our section
            array( // The $args
                self::return_message_name(), // Should match Option ID
            )  
        ); 
        
        
        add_settings_section(  
            self::return_opt_name(), // Section ID 
            __('Fresh Content Settings', self::return_plugin_namespace()), // Section Title
            array($this, 'reading_setting_callback'), // Callback
            'reading' // What Page?  This makes the section show up on the General Settings Page
        );
    
        register_setting('reading',self::return_opt_name(), array($this, 'validate_options'));
        
    }



    public function add_meta_to_head() {
        
        if (!self::is_wplogin() && !is_admin() && (($modified_tag = self::do_last_modified_meta_tag()) or ($etag = self::do_etag_meta_tag()) ) ){
        
            echo "\n<!-- Start LH Fresh Content Meta -->\n";
            if (!empty($modified_tag)){ echo $modified_tag; }
            if (!empty($etag)){ echo $etag; }
            echo "<!-- End LH Fresh Content Meta -->\n\n";
            
            wp_enqueue_script( self::return_plugin_namespace().'-script'); 
            //wp_enqueue_script( 'tinyicon');
        
        }
    
    }

    
    public function plugin_init(){
        
        //load translations
        load_plugin_textdomain( self::return_plugin_namespace(), false, basename( dirname( __FILE__ ) ) . '/languages' );
        
        //add processing to the innitial cron job
        add_action(self::return_plugin_namespace().'_initial',  array($this, 'initial_cron'));
        
        //register the core scripts
        add_action( 'wp_loaded', array($this, 'register_core_scripts'), 10 );  
        
        //add a section to the reading settings
        add_action('admin_init', array($this,'add_configuration_section')); 
        
        //add the meta tags to the head of the document
        add_action('wp_head', array($this,'add_meta_to_head'),1);
    
    }

    
    /**
     * Gets an instance of our plugin.
     *
     * using the singleton pattern
     */
    public static function get_instance(){
        
        if (null === self::$instance) {
            
            self::$instance = new self();
            
        }
 
        return self::$instance;
        
    }
    
    static function on_activate($network_wide) {
	    
        if ( is_multisite() && $network_wide ) { 

            $args = array('number' => 500, 'fields' => 'ids');
        
            $sites = get_sites($args);
    
            foreach ($sites as $blog_id) {

                switch_to_blog($blog_id);
                self::setup_crons();
                restore_current_blog();
                
            } 

        } else {

           self::setup_crons();

        }
	    
	}



    public function __construct() {
    
        //run whatever on plugins loaded
        add_action( 'plugins_loaded', array($this,'plugin_init'));
    
    }

    
}

$lh_fresh_content_instance = LH_Fresh_content_plugin::get_instance();
register_activation_hook(__FILE__, array('LH_Fresh_content_plugin', 'on_activate'));

}


?>