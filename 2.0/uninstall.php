<?
	//if uninstall not called from WordPress exit
	if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
		exit();

    require_once('Wpantiddos_Plugin.php');
    $wp_antiddos_plugin = new Wpantiddos_Plugin();
	$wp_antiddos_plugin->uninstall();