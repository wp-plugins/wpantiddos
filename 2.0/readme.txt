=== WP AntiDDOS ===
Author: zzmaster
Contributors: zzmaster
Tags: protection, ddos, dos, attack, http flood, password cracking
Requires at least: 3.0
Tested up to: 4.1
Stable tag: 2.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

WP AntiDDOS Plugin prevents DDOS attacks on your website by blocking the frequent requests from one or several related IP addresses.

== Description ==
Plugin WP AntiDDOS prevents DDOS attacks on your website by blocking the frequent requests from one or several related IP addresses. This includes HTTP flood and Password cracking attacks. The plugin stores the data in the MySQL table (MEMORY engine), and that ensures its high performance. We recommend to insert a plugin call into the beginning of the  index.php  file of the WordPress, it will significantly increase its effectiveness, since attack requests will be rejected by plugin  before  WordPress heavy engine connection. You have to do it manually. (see the instructions on the page with plugin's Configuration)

= How it works =
You are setting two main parameters in the Configuration of the plugin - Hits Limit and Seconds Limit. They specify the maximum number of requests from related IP addresses within a certain time. Plugin conciders IP addresses 
 a.b.c.1 
 a.b.c.2 
 a.b.c.3 
 ... 
 a.b.c.255 
 as related, they usually belong to the same LAN, and DDOS attacks are often made from many computers on the local network, because if one computer is infected, the attacker easily can infect others. If the plugin detects that during Seconds Limit from some local network there is more than Hits Limit requests, then excessive requests are blocked - plugin responds by status  503 Service not available  with the code, which reloads the page after a while, which is specified in the plugin Configuration as Delay Time. Limits are specified separately for GET, POST, XHR and Login requests.
 
 = Search engine compatibility =
 Sometimes search engines produce enough frequent queries, which can cause blockage by the WP AntiDDOS plugin. However, the plugin does not affect search engine indexation, because a response with status code  502 Service not available  is not a content of a page, but a technical reports on its currently unavailability. In any case, we recommend to use  Crawl-delay directive in you robots.txt  file.
 
= Custom Query Processing =
WP AntiDDOS plugin can be configured to handling only a certain types of queries. For example, the search queries load the server most significantly, and therefore are often used to attack sites. In the plugin's Configuration in the GET or POST parameters that activate DDOS check up  text field you can enter POST or GET parameters (blank separated) that identify the requests to be processed by plugin and then set the  Process only requests with following GET / POST parameters  to Yes. For example, s pwd parameters, which is defined in the text box since plugin has installed, identifies the Wordpress' search queries and Login attemts. Please, note, that you have separate setting for Login requests that is usefull for preventing Password Cracking attacks.

= The effectiveness of the plugin =
DDOS attacks are very different both by scale and by the used methods. Being a Wordpress plugin, WP AntiDDOS is effective against such attacks as HTTP flood, which affect the Wordpress engine. They are technically simple and most commonly used for disabling Wordpress websites. Usage of the WP AntiDDOS plugin - good practice for basic protection, which is effective in a large number of cases.
== Installation ==

This plugin follows the [standard WordPress installation
method](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins):

1. Upload the `wpantiddos` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.


== Screenshots ==
1. Plugin's Configuration

== Changelog ==
Empty.

= Version 1.x =
Private release.

= Version 2.0 =
Initial public release.