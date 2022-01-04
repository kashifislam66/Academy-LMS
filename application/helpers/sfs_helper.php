<?php
/** 
* RDA General Helper
*
* Helper Functions of Hajj TMS  
* 
* @package 		RDA
* @subpackage 	Helper
* @author 		Muhammad Khalid<muhammad.khalid@pitb.gov.pk>  
* @link 		http://
*/

/** 
* Helper function to display ordinal numbers
* 
* @param int
*/
function ordinal($number)
{
    $ends = array('th','st','nd','rd','th','th','th','th','th','th');
    if ((($number % 100) >= 11) && (($number%100) <= 13))
	{
        return $number. 'th';
    
	}
	else
	{
        return $number. $ends[$number % 10];
	}
}



/** 
* Helper function to concate specific key values
* 
* @param array
* @param string
* @param string
* @return string
*/
function concate_specific_key_values($data = array(), $assoc_key = '', $glue = '')
{
	$concatenated_string = '';
	if(!empty($data))
	{
		foreach($data as $key => $val )
		{
			if($key < count ($data)-1)
			{ 
				$concatenated_string .= $val[$assoc_key].$glue; 
			}
			else
			{
				$concatenated_string .= $val[$assoc_key];
			}
		}
	}
	return $concatenated_string;
}

/** 
* Helper function to
* 
* @param array
* @param bool
* @return array
*/
function filter_data($data = array(), $remove_empty = false)
{
	if(!empty($data))
	{
		foreach($data as $key => $val )
		{
			if($val == NULL)
			{
				$data[$key] = '';
			}
			if($remove_empty && $data[$key] == '')
				unset($data[$key]);
		}
	}
	return $data;
}

/** 
* Helper function to print array in pre-formated form
* 
* @param array
* @param bool
* @return print array
*/
function debug($arr, $exit = false)
{
	print "<pre>";
		print_r($arr);
	print "</pre>";
	if($exit)
		exit;
}

/** 
* Helper function to print string in pre-formated form
* 
* @param string
* @param bool
* @return print string
*/
function echo_str($str, $exit = false)
{
	echo $str;
	echo "<br />";
	if($exit)
		exit;
}


/** 
* Helper function to ramdom letters
* 
* @param Int
* @return string
*/	
function get_rand_letters($length)
{
	if($length>0) 
	{ 
		$rand_id="";
		for($i=1; $i<=$length; $i++)
		{
			mt_srand((double)microtime() * 1000000);
			$num = mt_rand(1,26);
			$rand_id .= assign_rand_value($num);
		}
	}
	return $rand_id;
}

/** 
* Helper function to get random value
* 
* @param Int
* @return string
*/		
function assign_rand_value($num)
{	
	// accepts 1 - 36
	switch($num)
	{
		case "1":
		$rand_value = "a";
		break;
		case "2":
		$rand_value = "b";
		break;
		case "3":
		$rand_value = "c";
		break;
		case "4":
		$rand_value = "d";
		break;
		case "5":
		$rand_value = "e";
		break;
		case "6":
		$rand_value = "f";
		break;
		case "7":
		$rand_value = "g";
		break;
		case "8":
		$rand_value = "h";
		break;
		case "9":
		$rand_value = "i";
		break;
		case "10":
		$rand_value = "j";
		break;
		case "11":
		$rand_value = "k";
		break;
		case "12":
		$rand_value = "l";
		break;
		case "13":
		$rand_value = "m";
		break;
		case "14":
		$rand_value = "n";
		break;
		case "15":
		$rand_value = "o";
		break;
		case "16":
		$rand_value = "p";
		break;
		case "17":
		$rand_value = "q";
		break;
		case "18":
		$rand_value = "r";
		break;
		case "19":
		$rand_value = "s";
		break;
		case "20":
		$rand_value = "t";
		break;
		case "21":
		$rand_value = "u";
		break;
		case "22":
		$rand_value = "v";
		break;
		case "23":
		$rand_value = "w";
		break;
		case "24":
		$rand_value = "x";
		break;
		case "25":
		$rand_value = "y";
		break;
		case "26":
		$rand_value = "z";
		break;
		case "27":
		$rand_value = "0";
		break;
		case "28":
		$rand_value = "1";
		break;
		case "29":
		$rand_value = "2";
		break;
		case "30":
		$rand_value = "3";
		break;
		case "31":
		$rand_value = "4";
		break;
		case "32":
		$rand_value = "5";
		break;
		case "33":
		$rand_value = "6";
		break;
		case "34":
		$rand_value = "7";
		break;
		case "35":
		$rand_value = "8";
		break;
		case "36":
		$rand_value = "9";
		break;
	}
	return $rand_value;
}


/** 
* Helper function to get address from google
* 
* @param str
* @return array
*/	
function parseAddressFromGoogleAPI($csv_latlong)
{
	$addressString = urlencode($csv_latlong);
	$strSubmitURL = "http://maps.googleapis.com/maps/api/geocode/json?latlng=$csv_latlong&sensor=false";
	$ret_result = send_request($strSubmitURL);
	$dataRow = json_decode($ret_result, true);
	//print_array($dataRow, true);
	$address_components = array('city' => '', 'state' => '', 'country' => '', 'postcode' => '' );
	if(isset($dataRow['status']) && $dataRow['status'] == 'OK' && !empty($dataRow['results']))
	{
		foreach ($dataRow["results"] as $result) 
		{
			foreach ($result["address_components"] as $address) {
				if (in_array("route", $address["types"])) {
					$address_components['street'] = $address["long_name"];
				}
				if (in_array("locality", $address["types"])) {
					$address_components['city'] = $address["long_name"];
				}
				if (in_array("administrative_area_level_1", $address["types"])) {
					$address_components['state'] = $address["long_name"];
				}
				if (in_array("country", $address["types"])) {
					$address_components['country'] = $address["long_name"];
				}
				if (in_array("postal_code", $address["types"])) {
					$address_components['postcode'] = $address["long_name"];
				}
				elseif(!is_array($address["types"]) && $address["types"] == 'postal_code')
				{
					$address_components['postcode'] = $address["long_name"];
				}
			}
		}
	}
	return $address_components;
}

/** 
* Helper function to send a curl call
* 
* @param str
* @return str
*/
function send_request($url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0); // no headers in the output
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // output to variable
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 	1);
	$data = curl_exec($ch);
	curl_close($ch);
 	return $data;
} 	

/** 
* Helper function to send email
* 
* @param str
* @param str
* @param str
* @param str
* @param str
* @return int
*/
function send_html_email ($to_email, $from_email, $from_name, $subject, $msg) {
    //split up to email array, if given
    if (is_array($to_email)) {
        $to_email_string = implode(', ', $to_email);
    }
    else {
        $to_email_string = $to_email;
    }
  
    //Assemble headers
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    $headers .= "From: $from_name <$from_email>" . "\r\n";
  
    //send via PHP's mail() function
    return mail($to_email_string, $subject, $msg, $headers);
}

/** 
* Helper function to check file if allowed
* 
* @param array
* @return array
*/
function checkfile($input)
{
   //Check the file is of correct format. function checkfile($input){
  $ext = array('mpg', 'wma', 'mov', 'flv', 'mp4', 'mp3', 'avi', 'qt', 'wmv', 'rm');
  $extfile = substr($input['name'],-4);
  $extfile = explode('.',$extfile);
  $good = array();
  $extfile = $extfile[1];
  if (in_array($extfile, $ext)) 
  {
    $good['safe'] = true;
    $good['ext'] = $extfile;
  }
  else 
  {
    $good['safe'] = false;
  }
  return $good;
}

/** 
* Helper function to make plural string
* 
* @param int
* @return str
*/
if(!function_exists('pluralize'))
{
	function pluralize($num)
	{
		if ((int)$num != 1)
			return "s";
	}
}

/** 
* Helper function to get Relative time
* 
* @param Date Time
* @param bool
* @return print
*/
if(!function_exists('getRelativeTime'))
{
	function getRelativeTime($date)
	{
		$diff = time() - strtotime($date);
	
		if ($diff < 10)
			return "just now";
		else if ($diff < 60)
			return "just now";
			
		$diff = round($diff / 60);
		if ($diff < 60)
			return $diff . " min". pluralize($diff) ." ago";
		$diff = round($diff / 60);
		if ($diff < 24)
			return $diff . " hour" . pluralize($diff) . " ago";
		$diff = round($diff / 24);
		if ($diff == 1)
		{
			return "yesterday";
		}
		elseif($diff < 7 )
		{
			return $diff . " day" . pluralize($diff) . " ago";
		}
		$days = $diff; 
		$diff = round($diff / 7);
		if ($diff < 5)
		{
			return $diff . " week" . pluralize($diff) . " ago";
		}
		if($days < 30)
		{
			return "1 month" . pluralize($diff) . " ago";
		}
		$diff = round($days / 30);
		if($diff < 12 )
		{
			return $diff . " month" . pluralize($diff) . " ago";
		}
		$diff = round($diff / 12);
		return $diff . " year" . pluralize($diff) . " ago";
		
	}
}
/** 
* Helper function to get exercise completion time in mints/hours format
* 
* @param Date Time
* @param bool
* @return print
*/
if(!function_exists('getExerciseCompletionTime'))
{
	function getExerciseCompletionTime($start_time, $end_time)
	{
                $start_time = strtotime($start_time);
                $end_time = strtotime($end_time);
		
                //$diff = time() - strtotime($date);
                $diff = $end_time - $start_time;
	
		if ($diff < 10)
			return "just now";
		else if ($diff < 60)
			return "just now";
			
		$diff = round($diff / 60);
		if ($diff < 60)
			return $diff . " min". pluralize($diff) ." ago";
		$diff = round($diff / 60);
		if ($diff < 24)
			return $diff . " hour" . pluralize($diff) . " ago";
		$diff = round($diff / 24);
		if ($diff == 1)
		{
			return "yesterday";
		}
		elseif($diff < 7 )
		{
			return $diff . " day" . pluralize($diff) . " ago";
		}
		$days = $diff; 
		$diff = round($diff / 7);
		if ($diff < 5)
		{
			return $diff . " week" . pluralize($diff) . " ago";
		}
		if($days < 30)
		{
			return "1 month" . pluralize($diff) . " ago";
		}
		$diff = round($days / 30);
		if($diff < 12 )
		{
			return $diff . " month" . pluralize($diff) . " ago";
		}
		$diff = round($diff / 12);
		return $diff . " year" . pluralize($diff) . " ago";
		
	}
}


/** 
* Helper Function to get current date and time
* 
* @access 	public 
* @return	Date and Time String
*/
function current_date_time()
{
	return date('Y-m-d H:i:s');
}

/** 
* Helper Function to get current date and time
* 
* @access 	public 
* @return	Date and Time String
*/
function formated_date($mysql_date_time = '', $format = 'd M Y')
{
	
	if(!empty($mysql_date_time))
		return date($format, strtotime($mysql_date_time));
	else
		return date('Y-m-d H:i:s');
}

/** 
* Helper Function to get mysql date and time
* 
* @access 	public 
* @return	Date and Time String
*/
function mysql_date_time($date_time)
{
	if(trim($date_time) == '')
	{
		return true;
	}
	$date_elements = explode('/', $date_time);
	if(!isset($date_elements[2]))
		echo $date_time;
	$time_elements = explode(' ', $date_elements[2]);
	if(!isset($time_elements[1]) || empty($time_elements[1]))
		$time_elements[1] = '00:00:00';
	$msql_date_time =trim($time_elements[0]).'-'.$date_elements[1].'-'.$date_elements[0].' '.trim($time_elements[1]);
	return date('Y-m-d H:i:s', strtotime($msql_date_time));
}

/** 
* Helper Function to get extension of file
* 
* @access 	public 
* @return	string
*/
function get_extension($filename)
{
	return $ext = strtolower(array_pop(explode('.',$filename)));
}

/** 
* Helper Function to get mime type of file
* 
* @access 	public 
* @return	string
*/
if(!function_exists('mime_content_type')) {

    function mime_content_type($filename) {

        $mime_types = array(

            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        $ext = get_extension($filename);
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        }
        elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            return $mimetype;
        }
        else {
            return 'application/octet-stream';
        }
    }
}

/** 
* Helper Function to upload file
* 
* @param string
* @param string
* @param array
* @param Int
* @return	array
*/
function sfs_upload_file($file_field_name, $upload_dir, $allowed_types = array(), $max_file_sieze = '10240') 
{
	$that =& get_instance();
	$file_name = $_FILES[$file_field_name]['name'];
	$ext  =  get_extension($file_name);
	//echo $ext; 
	if(!empty($allowed_types) && !in_array($ext, $allowed_types))
	{
		return 'File type you are uploading is not allowed.';
	}
	
	$config['upload_path'] 		= $upload_dir;
	$config['allowed_types'] 	= '*';
	$config['max_size'] 		= $max_file_sieze;
	$that->load->library('upload', $config);
	if (!$that->upload->do_upload($file_field_name))
	{
		return $that->upload->display_errors();
	}
	else
	{
		return $that->upload->data();
	}
}



function create_thumbnail($file_path, $thumb_dir, $width, $height) 
{
	$that =& get_instance();
	$that->load->library('image_lib');
	$config['image_library'] = 'gd2';
	$config['source_image'] = $file_path;       
	$config['create_thumb'] = TRUE;
	$config['maintain_ratio'] = TRUE;
	$config['width'] = $width;
	$config['height'] = $height;
	$config['thumb_marker'] = '';
	$config['new_image'] = $thumb_dir.'/'.basename($file_path);               
	$that->image_lib->initialize($config);
	if(!$that->image_lib->resize())
	{ 
		return $that->image_lib->display_errors();
	} 
	return true;       
}



/** 
* Helper Function to get short description
* 
* @param string
* @param Int
* @param bool
* @param bool
* @return string
*/
function word_wrap($string, $max_length, $end_substitute = null, $html_linebreaks = true) { 

    if($html_linebreaks) $string = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
    $string = strip_tags($string); //gets rid of the HTML

    if(empty($string) || mb_strlen($string) <= $max_length) {
        if($html_linebreaks) $string = nl2br($string);
        return $string;
    }

    if($end_substitute) $max_length -= mb_strlen($end_substitute, 'UTF-8');

    $stack_count = 0;
    while($max_length > 0){
        $char = mb_substr($string, --$max_length, 1, 'UTF-8');
        if(preg_match('#[^\p{L}\p{N}]#iu', $char)) $stack_count++; //only alnum characters
        elseif($stack_count > 0) {
            $max_length++;
            break;
        }
    }
    $string = mb_substr($string, 0, $max_length, 'UTF-8').$end_substitute;
    if($html_linebreaks) $string = nl2br($string);

    return $string;
}

/** 
* Helper Function to get clean Url by string
* 
* @param string
* @return string
*/
function clean_url($string)
{
	$url = str_replace("'", '', $string);
	$url = str_replace('%20', ' ', $url);
	$url = preg_replace('~[^\\pL0-9_]+~u', '-', $url); // substitutes anything but letters, numbers and '_' with separator
	$url = trim($url, "-");
	$url = iconv("utf-8", "us-ascii//TRANSLIT", $url);  // you may opt for your own custom character map for encoding.
	$url = strtolower($url);
	$url = preg_replace('~[^-a-z0-9_]+~', '', $url); // keep only letters, numbers, '_' and separator
	return $url;
}

/** 
* Helper Function to get nmber from string
*	Used where it may it may include % sign with value;
* 
* @param string
* @return string
*/
function extract_numbers($string, $only_int = false)
{
	if($only_int)
		$result = intval($string);
	else
		$result = floatval($string);

   return $result;
}

/** 
* Helper Function to replace shortcodes with actual data
* 
* @param string
* @return string
*/
function render_content($content)
{
	$data = array();
	$regex = "/\[(.*?)\]/";
	preg_match_all($regex, $content, $matches);
	
	if(!empty($matches))
	{
		//debug($matches);
		$short_codes = $matches[1];
		$CI =& get_instance();
		foreach($short_codes as $short_code)
		{
			$poll_id = str_replace(array('sfs_events_poll_'), array(''), $short_code);
			$poll = $CI->polls->get_by('poll_id', $poll_id);
			if(isset($poll) && !empty($poll))
			{
				$poll = $poll[0];
				$poll['poll_options'] = $CI->poll_options->get_by('poll_id', $poll_id);
				$data['page_poll'] = $poll;
				$poll_content = $CI->load->view('front/single_poll', $data, true);
				$content = str_replace('['.$short_code.']', $poll_content, $content);
			}
		}
	}
	return html_entity_decode(htmlspecialchars_decode($content));
}

/** 
* Helper Function to get fund raised for event
* 
* @param string
* @return string
*/
function scrap_fund_amount($organisation)
{
	$fund_raised = 0;
	$query = clean_url($organisation);
	$search_url = "https://www.make-a-donation.org/charity/$query";
	//echo_str($search_url);
	$page_data = send_request($search_url);
	$regex = '/<div\sclass="amountraisedvalue"\>(.+?)\<\/div\>/s';
	preg_match_all($regex, $page_data, $matches);
	if(isset($matches[1]) && !empty($matches[1]))
	{
		//debug($matches[1]);
		$fund_raised = 0;
		
		if(isset($matches[1][0]) && !empty($matches[1][0]))
		{
			if (preg_match_all('/\d+(\.\d+)?/', $matches[1][0], $num_matches))
			{
				if(isset($num_matches[0][0]) && !empty($num_matches[0][0]))
					$fund_raised = $num_matches[0][0];
				
			}
		}
	}
	return $fund_raised; 
}

/** 
* Helper Function to IP address of User
* 
* @param string
* @return string
*/
function get_user_ip()
{
	$ip_obj =  new RemoteAddress;
	return $ip_obj->getIpAddress();
}

class RemoteAddress
{
    /**
     * Whether to use proxy addresses or not.
     *
     * As default this setting is disabled - IP address is mostly needed to increase
     * security. HTTP_* are not reliable since can easily be spoofed. It can be enabled
     * just for more flexibility, but if user uses proxy to connect to trusted services
     * it's his/her own risk, only reliable field for IP address is $_SERVER['REMOTE_ADDR'].
     *
     * @var bool
     */
    protected $useProxy = false;

    /**
     * List of trusted proxy IP addresses
     *
     * @var array
     */
    protected $trustedProxies = array();

    /**
     * HTTP header to introspect for proxies
     *
     * @var string
     */
    protected $proxyHeader = 'HTTP_X_FORWARDED_FOR';

    // [...]

    /**
     * Returns client IP address.
     *
     * @return string IP address.
     */
    public function getIpAddress()
    {
        $ip = $this->getIpAddressFromProxy();
        if ($ip) {
            return $ip;
        }

        // direct IP address
        if (isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return '';
    }

    /**
     * Attempt to get the IP address for a proxied client
     *
     * @see http://tools.ietf.org/html/draft-ietf-appsawg-http-forwarded-10#section-5.2
     * @return false|string
     */
    protected function getIpAddressFromProxy()
    {
        if (!$this->useProxy
            || (isset($_SERVER['REMOTE_ADDR']) && !in_array($_SERVER['REMOTE_ADDR'], $this->trustedProxies))
        ) {
            return false;
        }

        $header = $this->proxyHeader;
        if (!isset($_SERVER[$header]) || empty($_SERVER[$header])) {
            return false;
        }

        // Extract IPs
        $ips = explode(',', $_SERVER[$header]);
        // trim, so we can compare against trusted proxies properly
        $ips = array_map('trim', $ips);
        // remove trusted proxy IPs
        $ips = array_diff($ips, $this->trustedProxies);

        // Any left?
        if (empty($ips)) {
            return false;
        }

        // Since we've removed any known, trusted proxy servers, the right-most
        // address represents the first IP we do not know about -- i.e., we do
        // not know if it is a proxy server, or a client. As such, we treat it
        // as the originating IP.
        // @see http://en.wikipedia.org/wiki/X-Forwarded-For
        $ip = array_pop($ips);
        return $ip;
    }
}
?>