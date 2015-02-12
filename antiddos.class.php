<?

define('wpadtiddos_seconds_limit_GET',1);
define('wpadtiddos_seconds_limit_XHR',3);

class wp_antiddos
{
	var $enable = true;
	var $hits_limit_GET = 3; // hits limit for GET requests (per wpadtiddos_seconds_limit_GET second)
	var $hits_limit_XHR = 3; // hits limit for XHR requests (per wpadtiddos_seconds_limit_XHR second)
	var $seconds_limit_POST = 3; // seconds limit for POST requests
	var $seconds_limit_AUTH = 3; // seconds limit for AUTH (Password) requests
	var $visitor; // status of visitor = raw|cool|warm|hot
	var $warm_level; // number of hits for last $seconds_limit seconds that cause visitor`s status turn to warm
	var $auto = true; // block visitors by AntiDDOS
	var $delay_time = 30; // seconds of delay of blocked visitors
	var $block_cnet = true; // block all C class net.
	var $cloudflare = true; // convert Cloudflare HTTP_CF_CONNECTING_IP to REMOTE_ADDR
	var $send_header = false; // send "WP_AntiDDOS: yes" header for debug purposes
	var $only_params_enabled = false; // Only Params feature enabled
	var $only_params = 's'; // the only GET/POST params that trigger checkup
	var $status, $error_msg;
	var $conn; // mysql connection
	var $hits = false; // actual hits number for current IP
	var $cookie = ''; // wpantiddos cookie value that prevents anti DDOS processing
	var $table_name = '';
	var $pass_param = 'pwd'; // name of POST parameter that indentify Login (AUTH) request
	var $delay_message = 'Our server is currently overloaded, your request will be repeated automatically in %s seconds';
	var $delay_message_auth = 'Our server is currently overloaded, your request will be repeated automatically in %s seconds';

	public function __construct()
	{
		if (isset($GLOBALS['wp_antiddos_instance']))
			return;
		else
			$GLOBALS['wp_antiddos_instance'] = &$this;

		if ($_SERVER['REMOTE_ADDR']=='127.0.0.1')
			return;

		$this->conn = mysqli_connect(DB_HOST,DB_USER,DB_PASSWORD);
		$ok = mysqli_select_db($this->conn,DB_NAME);
		if (!$ok) return false;
		
		$this->get_options();

		// plugin disabled
		if (!$this->enable) return;

		// current request is admin's one
		if (isset($_COOKIE['wpantiddos']) && $_COOKIE['wpantiddos']==$this->cookie) return;

		if ($this->only_params_enabled)
			if (!$this->only_param_detected())
				return;

		// detect request type and limits
		if ($this->xhr_request())
		{
			if ($this->hits_limit_XHR=='ANY') return;
			$request_type = 'xhr';
			$hits_limit = $this->hits_limit_XHR;
			$seconds_limit = wpadtiddos_seconds_limit_XHR;
		}
		elseif ($_POST && isset($_POST[$this->pass_param]) )
		{
			if ($this->seconds_limit_AUTH=='ANY') return;
			$request_type = 'auth';
			$seconds_limit = $this->seconds_limit_AUTH;
			$this->only_params .= ' '.$this->pass_param;
			$hits_limit = 1;
		}
		elseif ($_POST)
		{
			if ($this->seconds_limit_POST=='ANY') return;
			$request_type = 'post';	
			$hits_limit = 1;
			$seconds_limit = $this->seconds_limit_POST;
		}
		else
		{
			if ($this->hits_limit_GET=='ANY') return;	
			$request_type = 'get';
			$hits_limit = $this->hits_limit_GET;
			$seconds_limit = wpadtiddos_seconds_limit_GET;
		}

		if ($this->send_header)
			header("WP_AntiDDOS: yes");

		if ($this->cloudflare)
		{
			if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
				$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
		}
		$this->ip = $_SERVER['REMOTE_ADDR'];
		
		if ($this->block_cnet)
			$this->ip = substr($this->ip,0,strrpos($this->ip,'.')+1);

		$this->warm_level = ceil($hits_limit/2);
		try
		{
			$res = mysqli_query($this->conn,"SELECT count(*) kount FROM $this->table_name WHERE ip='".addslashes($this->ip)."' AND tstamp>".(time()-$seconds_limit)." AND type='$request_type'");
			$row = mysqli_fetch_assoc($res);

			if (!$row)
				$this->error_msg = 'Error detected';
			$this->hits = @$row['kount']+1; // consider current request too

			if ($this->hits==0) // if no hits from this IP
				$this->visitor = "new";
			elseif ($this->hits>$hits_limit)
				$this->visitor = "hot";
			elseif ($this->hits>=$this->warm_level)
				$this->visitor = "warm";
			else
				$this->visitor = "cool";

			// add current hit
			mysqli_query($this->conn,"INSERT INTO $this->table_name SET ip='$this->ip', type='$request_type', tstamp=".time());
			// cleanup  ip list
			$clear_time = max($this->delay_time,$seconds_limit);
			mysqli_query($this->conn,"DELETE FROM $this->table_name WHERE tstamp<".(time()-$clear_time));
		}
		catch(Exception $e)
		{
			$this->error_msg = $e->getString();
			$this->status = 'error';
			mysqli_close($this->conn);
			return;
		}
		mysqli_close($this->conn);
		if (!empty($this->error_msg) )
		{
			$this->status = 'error';
		}
		if ($this->auto && $this->visitor=='hot')
		{
			header('HTTP/1.0 503 Service Unavailable');
			header('Status: 503 Service Unavailable');
			header("Retry-After: ".($this->delay_time+1)); // submit form first if POST request
			if (!$_POST)
				print "<html><meta http-equiv='refresh' content='$this->delay_time'><body>";
			else
			{
				$inputs = $this->array_to_fields($_POST);
				print '<html><meta charset="utf-8"> <body><form method="post" id="form" accept-charset="UTF-8">
					<input id="submit" type="submit" style="visibility:hidden" />'.
					$inputs.
					'</form>
					<script>
						setTimeout(function(){ 
							var button = document.getElementById("submit");
							button.click();
						},'.$this->delay_time.'000);
					</script>';
			}
			if ($request_type=='auth')
				printf("<h2>$this->delay_message_auth</h2></body></html>",$this->delay_time);
			else
				printf("<h2>$this->delay_message</h2></body></html>",$this->delay_time);
			die();
		}

	}

	function xhr_request()
	{
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']))
		{
			if ($_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
				return true;
		}
		else
		{
			$headers = getallheaders();
			if (isset($headers['X-Requested-With']) && $headers['X-Requested-With']=='XMLHttpRequest')
				return true;
		}
		return false;
	}

	public function get_options()
	{
		global $table_prefix;
		$result = mysqli_query($this->conn,"SELECT option_name, option_value FROM {$table_prefix}options WHERE option_name LIKE 'Wpantiddos_Plugin_%'");

	    while ($row = mysqli_fetch_assoc($result)) {
			$name = str_replace('Wpantiddos_Plugin_','',$row['option_name']);
			$value = $row['option_value'];
			if ($value==='Yes') $value = true;
			if ($value==='No') $value = false;
			if (isset($this->$name))
				$this->$name = $value;
		}
	}

	public function only_param_detected()
	{
		$acual = array_merge(array_keys($_GET),array_keys($_POST));
		$found = array_intersect($acual,explode(' ',trim($this->only_params)));
		return $found;
	}
	
	function array_to_fields($fields, $prefix = '') {
		$form_html = '';

		foreach ($fields as $name => $value) {
			if ( ! is_array($value)) {
				if ( ! empty($prefix)) {
					$name = $prefix . '[' . $name . ']';
				}
				// generate the hidden field
				$form_html .= "<input type=\"hidden\" name=\"$name\" value=\"".$value."\" />\n";
			} else {
				if ( ! empty($prefix)) {
					$subprefix = $prefix . '[' . $name . ']';
				} else {
					$subprefix = $name;
				}
				$form_html .= array_to_fields($value, $subprefix);
			}
		}

		return $form_html; 
	}	
	
}


?>