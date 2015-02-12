<?php
/*
   Plugin Name: WP AntiDDOS
   Plugin URI: http://wordpress.org/
   Version: 2.0
   Author: Klavasoft
   Description: WP AntiDDOS plugin.
   Text Domain: wpantiddos
   License: BSD 3-Clause
  */


$Wpantiddos_minimalRequiredPhpVersion = '5.2';

/**
 * Check the PHP version and give a useful error message if the user's version is less than the required version
 * @return boolean true if version check passed. If false, triggers an error which WP will handle, by displaying
 * an error message on the Admin page
 */
function Wpantiddos_noticePhpVersionWrong() {
    global $Wpantiddos_minimalRequiredPhpVersion;
    echo '<div class="updated fade">' .
      __('Error: plugin "wpantiddos" requires a newer version of PHP to be running.',  'wpantiddos').
            '<br/>' . __('Minimal version of PHP required: ', 'wpantiddos') . '<strong>' . $Wpantiddos_minimalRequiredPhpVersion . '</strong>' .
            '<br/>' . __('Your server\'s PHP version: ', 'wpantiddos') . '<strong>' . phpversion() . '</strong>' .
         '</div>';
}


function Wpantiddos_PhpVersionCheck() {
    global $Wpantiddos_minimalRequiredPhpVersion;
    if (version_compare(phpversion(), $Wpantiddos_minimalRequiredPhpVersion) < 0) {
        add_action('admin_notices', 'Wpantiddos_noticePhpVersionWrong');
        return false;
    }
    return true;
}


/**
 * Initialize internationalization (i18n) for this plugin.
 * References:
 *      http://codex.wordpress.org/I18n_for_WordPress_Developers
 *      http://www.wdmac.com/how-to-create-a-po-language-translation#more-631
 * @return void
 */
function Wpantiddos_i18n_init() {
    $pluginDir = dirname(plugin_basename(__FILE__));
	if (function_exists('load_plugin_textdomain'))
		load_plugin_textdomain('wpantiddos', false, $pluginDir . '/languages/');
}


//////////////////////////////////
// Run initialization
/////////////////////////////////

// First initialize i18n
Wpantiddos_i18n_init();


// Next, run the version check.
// If it is successful, continue with initialization for this plugin
if (Wpantiddos_PhpVersionCheck()) {
    // Only load and run the init function if we know PHP version can parse it
    include_once('wpantiddos_init.php');
    Wpantiddos_init(__FILE__);
}

