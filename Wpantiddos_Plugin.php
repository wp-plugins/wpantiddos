<?php

include_once('Wpantiddos_LifeCycle.php');

class Wpantiddos_Plugin extends Wpantiddos_LifeCycle {

	var $tableName = '';
	
	public function __construct()
	{
		global $wpdb;
		//parent::__construct();
		$this->tableName = $wpdb->prefix.'antiddos';
	}
    /**
     * See: http://plugin.michael-simpson.com/?page_id=31
     * @return array of option meta data.
     */
    public function getOptionMetaData() {
        //  http://plugin.michael-simpson.com/?page_id=31
        return array(
            'enable' => array('Turn DDOS protection ON','Yes','No'),
			'hits_limit_GET' => array('Maximal Hits count for <b>GET</b> requests (per '.wpadtiddos_seconds_limit_GET.' seconds)',1,2,3,4,5,6,7,8,9,10,15,20,25,'ANY'),
			'hits_limit_XHR' => array('Maximal Hits count for <b>XHR</b> requests (per '.wpadtiddos_seconds_limit_XHR.' seconds)',1,2,3,4,5,6,7,8,9,10,15,20,25,'ANY'),
			'seconds_limit_POST' => array('Minimal Seconds timeout between <b>POST</b> requests (seconds)','ANY',1,2,3,4,5,7,8,9,10,15,20,25),
			'seconds_limit_AUTH' => array('Minimal Seconds timeout between <b>Login</b> requests (seconds) <br /><small>Considering Login request as POST request having <u>pwd</u> parameter','ANY',1,2,3,4,5,10,11,12,13,14,15,30),
			'delay_time' => array('Delay Time (seconds)',5,10,20,30,60,90,120),
			'delay_message' => array('Delay message'),
			'delay_message_auth' => array('Delay Login message'),
			'only_params_enabled' => array('Process only requests with following GET/POST parameters','Yes', 'No'),
			'only_params' => array('GET or POST parameters that activate DDOS check up<br><small>applying to GET, POST, XHR & Password requests</small>'),
        );
    }

//    protected function getOptionValueI18nString($optionValue) {
//        $i18nValue = parent::getOptionValueI18nString($optionValue);
//        return $i18nValue;
//    }

    /**
     * Display a notice to the admin that they need to configure the plugin
     * @return void
     */
    function notice_setup() {
        $notice_name = 'wp_antiddos_hide_setup_notice';

        // User has hidden the notice or we've already setup the plugin
        if (
            get_user_meta($GLOBALS['current_user']->ID, $notice_name )
            || $this->getOption('token_name' )
        ) return;

        echo '
        <div class="updated">
            <p>
                <strong>' . sprintf( __( 'WP AntiDDOS plugin has been installed and already protects You against DDOS attacks. Please take a look at %sconfiguration page%s.' ), '<a href="' . admin_url( 'plugins.php?page=Wpantiddos_PluginSettings' ) . '">', '</a>' ) . '</strong>
                <a href="' . add_query_arg( $notice_name, true ) . '">' . __( 'Hide this message.' ) . '</a>
            </p>
        </div>
        ';
    }

    /**
     * Handler for hiding the plugin setup notice
     * @return void
     */
    function notice_setup_hide() {
        $notice_name = 'wp_antiddos_hide_setup_notice';
        if ( ! empty( $_GET[ $notice_name ]) ) {
            add_user_meta( $GLOBALS['current_user']->ID, $notice_name, true, true);
            wp_safe_redirect( remove_query_arg( $notice_name ) );
            exit;
        }
    }


    protected function initOptions() {
        $options = $this->getOptionMetaData();
        if (!empty($options)) {
            foreach ($options as $key => $arr) {
                if (is_array($arr) && count($arr > 1)) {
                    $this->addOption($key, $arr[1]);
                }
            }
        }
    }

    public function getPluginDisplayName() {
        return 'wpantiddos';
    }

    protected function getMainPluginFileName() {
        return 'wpantiddos.php';
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Called by install() to create any database tables if needed.
     * Best Practice:
     * (1) Prefix all table names with $wpdb->prefix
     * (2) make table names lower case only
     * @return void
     */
    public function installDatabaseTables() {
                global $wpdb;

                $wpdb->query("DROP TABLE IF EXISTS `$this->tableName`");
				$wpdb->query("CREATE TABLE IF NOT EXISTS `$this->tableName` (
  `ip` varchar(40) NOT NULL,
  `tstamp` int(11) NOT NULL,
  `type` varchar(5) NOT NULL,  
  KEY `ip` (`ip`),
  KEY `type` (`type`),  
  KEY `tstamp` (`tstamp`)
) ENGINE=MEMORY;");
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Drop plugin-created tables on uninstall.
     * @return void
     */
    public function unInstallDatabaseTables() {
                global $wpdb;
                $wpdb->query("DROP TABLE IF EXISTS `$this->tableName`;");
    }


    /**
     * Perform actions when upgrading from version X to version Y
     * See: http://plugin.michael-simpson.com/?page_id=35
     * @return void
     */
    public function upgrade() {
    }

    public function addActionsAndFilters() {

        // Add options administration page
        // http://plugin.michael-simpson.com/?page_id=47
		add_action('plugins_loaded', array(&$this, 'doDDOSCheck'));
        add_action('admin_menu', array(&$this, 'addSettingsSubMenuPage'));
		
		add_action( 'admin_init', array( &$this, 'notice_setup_hide' ) );
		add_action( 'admin_notices', array( &$this, 'notice_setup' ) );

        // Example adding a script & style just for the options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        //        if (strpos($_SERVER['REQUEST_URI'], $this->getSettingsSlug()) !== false) {
        //            wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));
        //            wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        }


        // Add Actions & Filters
        // http://plugin.michael-simpson.com/?page_id=37


        // Adding scripts & styles to all pages
        // Examples:
        //        wp_enqueue_script('jquery');
        //        wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));


        // Register short codes
        // http://plugin.michael-simpson.com/?page_id=39


        // Register AJAX hooks
        // http://plugin.michael-simpson.com/?page_id=41

    }

	public function doDDOSCheck()
	{
		if (!defined('wpantiddos_done'))
		{
			include_once ABSPATH.'wp-content/plugins/'.$this->getPluginDisplayName().'/antiddos.class.php';
			if (class_exists('wp_antiddos'))
			{
				new wp_antiddos();
			}
		}
	}

	public function histNow()
	{
		global $wpdb;
		$hits = $wpdb->get_results("SELECT ip, ".time()."-max(tstamp) age, count(*) kount FROM wp_antiddos GROUP BY ip");
		return $hits;
	}

	public function pluginForced()
	{
		$txt = file_get_contents(ABSPATH."wp-config.php");
		return strpos($txt,$this->getPluginDisplayName());
	}
	
	function cookieValue()
	{
		return $this->getOption('cookie');
	}
}
