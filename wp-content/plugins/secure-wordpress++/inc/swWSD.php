<?php if (!defined('ABSPATH')) { exit; }
/**
 * Facilitates the login/register with websitedefender.com for website scanner.
 *
 * @author WebsiteDefender
 * $rev #1 07/16/2011 {c}$
 * $rev #2 07/21/2011 {c}$
 */
class swWSD
{
    const WSD_URL = 'https://dashboard.websitedefender.com/';
    const WSD_URL_RPC = 'https://dashboard.websitedefender.com/jsrpc.php';
    const WSD_URL_DOWN = 'https://dashboard.websitedefender.com/download.php';

    
    const WSD_SOURCE = 3;
    //error codes
    const WSD_ERROR_LIMITATION = 0x27;
    const WSD_ERROR_WPP_SERVICE_DOWN = 0x50;
    const WSD_ERROR_WPP_ERROR_INVALID_URL = 0x51;
    const WSD_ERROR_WPP_URL_REGISTERED = 0x52;
    const WSD_WSD_ERROR_WPP_NEWUSR_PARAM = 0x53;
    const WSD_ERROR_WPP_INVALID_CAPTCHA =0x54 ;
    const WSD_ERROR_WPP_USER_EXIST = 0x55;
    const WSD_ERROR_WPP_URL_EXIST = 0x56;
    //http status
    const HTTP_STATUS = 0;
    const HTTP_HEADERS = 1;
    const HTTP_BODY = 2;
    const HTTP_CHUNK_HEADER = 3;
    const HTTP_CHUNK_BODY = 4;
    
    
    // constructor
    public function __construct() {}

    
    function wsd_site_url(){return get_option( 'siteurl' ).'/';}

    function wsd_parseUrl($url)
    {
        $result = parse_url($url);
        if($result === null) { return array("error"=>"Invalid URL."); }
        $result["error"] = null;
        if(!array_key_exists("port", $result)) {$result["port"] = 80;}
        if(!array_key_exists("scheme", $result)) {$result["scheme"] = "http";}
        if(!array_key_exists("query", $result)) {$result["query"] = "";}
        else {$result["query"] = "?" . $result["query"];}
        if(array_key_exists("host", $result))
        {
            if(!array_key_exists("path", $result)) $result["path"] = "";
        }
        else
        {
            if(array_key_exists("path", $result))
            {
                $dirs = explode("/", $result["path"], 2);
                $result["host"] = $dirs[0];
                if(count($dirs)>1) {
                    $result["path"] = "/".$dirs[1];
                }
                else {$result["path"] = "/";}
            }
            else {return array("error"=>"Invalid URL (no host).");}
        }

        if($result["host"] == "") {return array("error"=>"Invalid URL (no host).");}

        $scheme = "http";
        if(array_key_exists("scheme", $result)) {$scheme = $result["scheme"];}

        if((strcasecmp($scheme,"http")!=0) && (strcasecmp($scheme,"https")!=0)) {return array("error"=>"Invalid URL (unknown scheme).");}

      if(strcasecmp($scheme,"https")==0) $result["port"] = 443;

        $userPass = "";
        if(array_key_exists("user", $result) && array_key_exists("pass", $result)) {
            $userPass = $result["user"].":".$result["pass"]."@";
        }

        $port = "";
        if(array_key_exists("port", $result)) {$port = ":".$result["port"];}

        $result["all"] = $scheme."://".$userPass.$result["host"].$port;
        
        return $result;
    }

    function wsd_httpRequest($verb, $url, $body="", $headers=array(), $timeout = 10)
    {
      $e = error_reporting(0);

        $result = array();
        $result["cookie"] = null;
        $result["body"] = "";
        $result["length"] = null;
        $result["error"] = null;

        $now = time();
        $url = $this->wsd_parseUrl($url);

        if($url["error"] !== null) {return $url;}

        $scheme = $url["scheme"]=="https" ? "ssl://" : "";

        $fp = fsockopen($scheme.$url["host"], $url["port"] , $errno, $errstr, $timeout);	

      if (!$fp)
      {
        if($scheme == "ssl://")
        {
          $fp = fsockopen($url["host"], 80 , $errno, $errstr, $timeout);
          if (!$fp)
          {
            error_reporting($e);
            return array("error"=>"Can't connect to server [$errno]");
          }
        }
        else
        {
          error_reporting($e);
          return array("error"=>"Can't connect to server [$errno].");
        }
      }

      $out  = $verb." ".$url["path"].$url["query"]." HTTP/1.1\r\n";
      $out .= "Host: ". $url["host"] . "\r\n";
      $out .= "Connection: Close\r\n";
      $out .= "Accept-Encoding: identity\r\n"; 
      if($verb == "POST") {$out .= "Content-Length: " . strlen($body) . "\r\n"; }   
      foreach ($headers as $name => $value) {$out .= $name .": " . $value . "\r\n";}    
      $out .= "\r\n";    
      if($verb == "POST") {$out .= $body;}    
      fwrite($fp, $out);
      fflush($fp);

      //print "<br>".str_replace("\r\n", "<br>", $out)."<br>";

      $status = self::HTTP_STATUS;
      $chunked = false;
      $lastChunk = "";
      $chunkLength = 0;

      while (!feof($fp))
      {
        $remaining = $timeout - (time() - $now);
        if($remaining < 0) {return array("error"=>"Request timed out [1].");}

        stream_set_timeout($fp, $remaining + 1);
        $data = fgets($fp, 4096);
        $info = stream_get_meta_data($fp);

        if ($info["timed_out"])
        {
          error_reporting($e);
          return array("error"=>"Request timed out [2].");
        }

        //print($data."<br>");

        if($status == self::HTTP_STATUS)
        {
          //TODO: check status for 200, error on rest, eventually work arround 302 303
          $resultStatus = trim($data);
          $status = self::HTTP_HEADERS;
          continue;
        }

        if($status == self::HTTP_HEADERS)
        {
          if($data == "\r\n")
          {
            if($chunked) {
              $status = self::HTTP_CHUNK_HEADER;
            }
            else {$status = self::HTTP_BODY;}
            
            continue;
          }

          $data = trim($data);    		
          $separator = strpos($data, ": ");

          if(($separator === false)||($separator == 0) || ($separator >= (strlen($data) -2))) {
            return array("error"=>"Invalid HTTP response header.");
          }

          $name = substr($data, 0, $separator);
          $value  = substr($data, $separator + 2);
          if(strcasecmp("Set-Cookie", $name) == 0)
          {
            $result["cookie"] = $value;
            continue;
          }
          if(strcasecmp("Content-Length", $name) == 0)
          {
            $result["length"] = $value + 0;
            continue;
          }
          if((strcasecmp("Transfer-Encoding", $name) == 0) && (strpos($value, 'chunked') !== false) )
          {
            $chunked = true;
            continue;
          }
          continue;
        }

        if($status == self::HTTP_CHUNK_HEADER)
        {
          $data = trim($data);
          $sc = strpos($data, ';');
          if($sc !== false) {$data = substr($data, 0, $sc);}
          $chunkLength = hexdec($data);
          if($chunkLength == 0) {
            break;
          }
          $lastChunk = "";
          $status = self::HTTP_CHUNK_BODY;
          continue;
        }

        if($status == self::HTTP_CHUNK_BODY)
        {
          $lastChunk .= $data;
          if(strlen($lastChunk) >= $chunkLength)
          {
            $result["body"] .= substr($lastChunk, 0, $chunkLength);
            $status = self::HTTP_CHUNK_HEADER;
          }
          continue;
        }

        if($status == self::HTTP_BODY)
        {
          $result["body"] .= $data;
          if(($result["length"] !== null) && (strlen($result["body"]) >= $result["length"])) {
            break;
          }
          continue;
        }
      }
      fclose($fp);

      if(($result["length"] !== null) && (strlen($result["body"]) != $result["length"])) {
        array("error"=>"Invalid HTTP body length.");
      }

      error_reporting($e);
      return $result;
    }

    function wsd_jsonHttpRequest($url, $data, $timeout = 10)
    {
        $body = json_encode($data);
        $headers = array("Content-type" => "application/json");

      $cookie = '';
      $option_cookie = get_option("WSD-COOKIE");
      if($option_cookie !== false) {$cookie = $option_cookie;}

      $token = get_option("WSD-TOKEN");
      if($token !== false)
      {
        if($cookie != ''){ $cookie .= '; ';}
        $cookie .= "token=".$token;
      }

      if($cookie != '') {
        $headers["Cookie"] = $cookie;
      }

        $result = $this->wsd_httpRequest("POST", $url, $body, $headers, $timeout);

      if($result["cookie"] !== null)
      {
        if($option_cookie === false) {
          add_option("WSD-COOKIE", $result["cookie"]);
        }
        else {update_option("WSD-COOKIE", $result["cookie"]);}
      }

      if($result["error"] === null)
      {
        $decoded = json_decode($result["body"], true);
        if($decoded == null) {$result["error"] = "Invalid JSON response.".$result["body"];}
        $result["body"] = $decoded;
      }
        return $result;
    }

    function wsd_jsonRPC($url, $method, $params, $timeout = 10)
    {
      $GLOBALS['wsd_last_err'] = array('code'=>0, 'message'=>'');
      $id = rand(1,100);

      $token = get_option("WSD-TOKEN");
      if($token === false) {
        $request = array("jsonrpc"=>"2.0", "id"=>$id, "method"=>$method, "params"=>$params);
      }
      else {$request = array("jsonrpc"=>"2.0", "id"=>$id, "method"=>$method, "params"=>$params, "token"=>$token);}

        $response = $this->wsd_jsonHttpRequest($url, $request, $timeout);

      //print("request:");print_r($request); print("<hr>"); print("response:");print_r($response); print("<hr>");

      if($response["error"] !== null)
      {
        $GLOBALS['wsd_last_err'] = array("code" => 0, "message" => $response["error"]);
        return null;
      }

      if((! array_key_exists("id", $response["body"])) || ($response["body"]["id"] != $id) )
      {
        $GLOBALS['wsd_last_err'] = array("code" => 0, "message" => "Invalid JSONRPC response [0].");
        return null;
      }

      if( array_key_exists("token", $response["body"]))
      {
        if($token === false) {add_option("WSD-TOKEN", $response["body"]['token']);}
        else {update_option("WSD-TOKEN", $response["body"]['token']);}
      }

      if(array_key_exists("error", $response["body"]))
      {
        $GLOBALS['wsd_last_err'] = $response["body"]["error"];
        return null;
      }

      if(! array_key_exists("result", $response["body"]))
      {
        $GLOBALS['wsd_last_err'] = array("code" => 0, "message" => "Invalid JSONRPC response [1].");
        return null;
      }

      return $response["body"]["result"];
    }

    // ========================= RENDER UI ===========================================================

    function wsd_render_error($custom_message = null)
    {
      $html = '';
      if ($custom_message === null) {
        $html = '<p class="wsd-error-summary">' . $GLOBALS['wsd_last_err']['message'];
      }
      else {$html = '<p class="wsd-error-summary">' . $custom_message;}
      $html .= '<br /><span class="wsd-error-summary-detail">If the problem persists please continue at <a href="https://dashboard.websitedefender.com" target="_blank">Website Defender</a>.</span></p>';
      echo $html;
    }

    function wsd_render_agent_install_issues($message)
    {
      //echo "wsd_render_agent_install_issues<br>";
      $html = '<p class="wsd-error-summary">' . $message;
      $html .= '<br /><span class="wsd-error-summary-detail">It has to be installed manually from the <a href="https://dashboard.websitedefender.com" target="_blank">WebsiteDefender dashboard</a>.</span></p>';
      echo $html;
    }

    function wsd_render_user_login($error = '')
    {
      if($error !== '') {$this->wsd_render_error($error);}
      ?>

    <?php if(!empty($error)) { ?>
        <div class="wsd-inside">
    <?php } ?>

            <p class="wsd-login-notice">Login here if you already have a WSD account.</p>
        <form action="" method="post" id="sw_wsd_login_form" name="sw_wsd_login_form">
          <div>
              <div class="wsd-login-section">
                <label for="wsd_login_form_email">Email:</label>
                    <input type="text" name="wsd_login_form_email" id="wsd_login_form_email" value="<?php echo get_option("admin_email"); ?>" />
              </div>
              <div class="wsd-login-section">
                <label for="wsd_login_form_password">Password:</label>
                    <input type="password" name="wsd_login_form_password" id="wsd_login_form_password" />
              </div>
            <input type="submit" name="wsd-login" id="wsd-login" value="Login">
          </div>
        </form>

    <?php if(!empty($error)) { ?>
        </div>
    <?php } ?>

      <?php
    }

    function wsd_render_new_user($error = '')
    {
      //print "wsd_render_new_user $error<br>";

      $form = $this->wsd_jsonRPC(self::WSD_URL_RPC, "cPlugin.getfrm", $this->wsd_site_url());
      if ($form === null)
      {
          $this->wsd_render_error();
          return;
      }
      $recaptcha_publickey = $form['captcha'];
      if(empty($recaptcha_publickey))
      {
        $this->wsd_render_error('Invalid server response.');
        return;
      }

      //intro text
      echo '<p class="wsd-inside" style="margin-top: 0px;">';
        _e('WebsiteDefender.com is based upon web application scanning technology from <a href="http://www.acunetix.com/" target="_blank">Acunetix</a>; a pioneer in website security. <a href="http://www.websitedefender.com" target="_blank">WebsiteDefender</a> requires no installation, no learning curve and no maintenance. Above all, there is no impact on site performance! WebsiteDefender regularly scans and monitors your WordPress website/blog effortlessly, efficient, easily and is available for Free! Start scanning your WordPress website/blog against malware and hackers, absolutely free!', FB_SWP_TEXTDOMAIN);
      echo "</p>";

      ?>
      <div class="wsd-inside">
        <?php    
        $this->wsd_render_user_login();
        ?>

            <h4><?php _e('Register here to use all the WebsiteDefender.com advanced features', FB_SWP_TEXTDOMAIN)?></h4>
            <p><?php _e('WebsiteDefender is an online service that protects your website from any hacker activity by monitoring and auditing the security of your website, giving you easy to understand solutions to keep your website safe, always! WebsiteDefender\'s enhanced WordPress Security Checks allow it to optimise any threats on a blog or site powered by WordPress.',  FB_SWP_TEXTDOMAIN)?></p>
            <p><?php _e('<strong>With WebsiteDefender you can:</strong>',  FB_SWP_TEXTDOMAIN)?></p>
            <ul class="wsd_commonList">
                <li><span>Detect Malware present on your website</span></li>
                <li><span>Audit your website for security issues</span></li>
                <li><span>Avoid getting blacklisted by Google</span></li>
                <li><span>Keep your website content and data safe</span></li>
                <li><span>Get alerted to suspicious hacker activity</span></li>
            </ul>

            <p><?php _e('WebsiteDefender.com does all this an more via an easy-to-understand web-based dashboard, which gives step by step solutions on how to make sure your website stays secure!',  FB_SWP_TEXTDOMAIN)?></p>

            <h4><?php _e('Sign up for your FREE account here',  FB_SWP_TEXTDOMAIN)?></h4>

        <?php
          if($error !== '') {$this->wsd_render_error($error);}
        ?>

        <form action="#em" method="post" id="sw_wsd_new_user_form" name="sw_wsd_new_user_form">
            <div id="em" class="wsd-new-user-section">
                <label for="wsd_new_user_email">Email:</label>
                <input type="text" name="wsd_new_user_email" id="wsd_new_user_email" value="<?php echo get_option("admin_email"); ?>" />
            </div>
            <div class="wsd-new-user-section">
                <label for="wsd_new_user_name">Name:</label>
                <input type="text" name="wsd_new_user_name" id="wsd_new_user_name" value="<?php echo isset($_POST['wsd_new_user_name']) ? $_POST['wsd_new_user_name'] : '' ?>" />
            </div>
            <div class="wsd-new-user-section">
                <label for="wsd_new_user_surname">Surname:</label>
                <input type="text" name="wsd_new_user_surname" id="wsd_new_user_surname" value="<?php echo isset($_POST['wsd_new_user_surname']) ? $_POST['wsd_new_user_surname']: '' ?>" />
            </div>
            <div class="wsd-new-user-section">
                <label for="wsd_new_user_password">Password:</label>
                <input type="password" name="wsd_new_user_password" id="wsd_new_user_password"/>
                <label class="password-meter" style="background-color: rgb(238, 0, 0); display: none;">Too Short</label>
            </div>
            <div class="wsd-new-user-section">
                <label for="wsd_new_user_password_re">Retype Password:</label>
                <input type="password" name="wsd_new_user_password_re" id="wsd_new_user_password_re"/>
            </div>
            <div class="wsd-new-user-section">
              <?php
                    echo wsd_recaptcha_get_html($recaptcha_publickey, null, true);
              ?>
            </div>
          <input type="submit" name="wsd-new-user" id="wsd-new-user" value="Register">
        </form>    
      </div>
      <?php  
    }


    function wsd_process_login()
    {
        $email = isset($_POST['wsd_login_form_email']) ? $_POST['wsd_login_form_email'] : null;
        $password = isset($_POST['wsd_login_form_password']) ? $password = $_POST['wsd_login_form_password'] : null;

        if (empty($email)) {
            $this->wsd_render_user_login('Email address is required.');
            return;
        }

        if (empty($password)) {
            $this->wsd_render_user_login('Password is required.');
            return;
        }

        // $password is received as MD5 hash
        $login = $this->wsd_jsonRPC(self::WSD_URL_RPC, "cUser.login", array($email, $password));

        if ($login == null) {
            $this->wsd_render_user_login('Invalid login');
            return;
        }

        $user = get_option("WSD-USER");
        if ($user === false) {
            add_option("WSD-USER", $email);
        }
        else {update_option("WSD-USER", $email);}

        $this->wsd_add_or_process_target();
    }

    function wsd_render_add_target_id()
    {
      ?>
        <div class="wsd-inside">
            <?php if(!empty($error)) {$this->wsd_render_error($error);} ?>
            <form action="" method="post" id="wsd_target_id_form" name="wsd_target_id_form">
                <label for="wsd_target_update_id">Target ID:</label>
                    <input type="text" name="targetid" id="targetid"/>
                <input type="submit" name="wsd_update_target_id" value="Update" />
            </form>
        </div>
      <?php
    }

    function wsd_process_add_target_id()
    {
      //echo "wsd_process_add_target_id<br>";
      add_option('WSD-TARGETID', $_POST['targetid']);
      $this->wsd_render_target_status();
    }

    function wsd_add_or_process_target()
    {
      //check if we already registered
      $targetid = get_option('WSD-TARGETID');

      if($targetid !== false)
      {
        $this->wsd_render_target_status();
        return;
      }
      else
      {
        //check first is this url is already there
        $target = $this->wsd_jsonRPC(self::WSD_URL_RPC, "cPlugin.urlstatus", $this->wsd_site_url());
        if($target === null)
        {
          $this->wsd_render_error();
          return;
        }
        if(array_key_exists('id', $target) && ($target['id'] != null))
        {
          if($targetid === false) {add_option('WSD-TARGETID', $target['id']);}
          else {update_option('WSD-TARGETID', $target['id']);}
          $this->wsd_render_target_status();
          return;
        }
      }  

      //the target was not there so we have to register a new one
      $newtarget = $this->wsd_jsonRPC(self::WSD_URL_RPC, "cTargets.add", $this->wsd_site_url());
      if($newtarget === null)
      {
        if($GLOBALS['wsd_last_err']['code'] == self::WSD_ERROR_LIMITATION)
        {
          $this->wsd_render_error("This account reached the maximum number of targets.");
          return;
        }
        if($GLOBALS['wsd_last_err']['code'] == self::WSD_ERROR_WPP_URL_EXIST)
        {
          $this->wsd_render_add_target_id();
          return;
        }
        print_r($GLOBALS['wsd_last_err']);
        return;
      }

      if(!array_key_exists("id", $newtarget))
      {
        $this->wsd_render_error("Invalid WSD response received.");
        return;
      }

      delete_option('WSD-TARGETID');
      add_option('WSD-TARGETID', $newtarget['id']);

      //download agent
      $targetInstalError = '';

      $headers = array("a"=>"a");
      $option_cookie = get_option("WSD-COOKIE");
      if($option_cookie !== false) $headers["Cookie"] = $option_cookie;

      //print "<br>Downloading: ". WSD_URL_DOWN.'?id='.$newtarget['id'] ."#". print_r($headers, true). "<br>";

      $agent = $this->wsd_httpRequest("GET", self::WSD_URL_DOWN.'?id='.$newtarget['id'], "", $headers);

      if($agent["error"] !== null) {
        $targetInstalError = 'The WebsiteDefender Agent failed to install automatically [0x01].'; //can't download
      }
      else
      {
        //try to copy the target
        $agentURL = $agent["sensor_url"];
        if(preg_match('/[a-f0-9]{40}.php/', $newtarget["sensor_url"], $matches))
        {
          $path = rtrim(ABSPATH, '/');
          $path .= '/'.$matches[0];

          $r = file_put_contents($path, $agent['body']);
          if(!$r) {$targetInstalError = 'The WebsiteDefender Agent failed to install automatically [0x02].';} /* can't save */
        }
        else {$targetInstalError = 'The WebsiteDefender Agent failed to install automatically [0x03].';} /* other */
      }

      //test the agent, this will triger agentless if agent not functioning
      $testTarget = $this->wsd_jsonRPC(self::WSD_URL_RPC, "cTargets.agenttest", $newtarget['id']);  
      $enbableTarget = $this->wsd_jsonRPC(self::WSD_URL_RPC, "cTargets.enable", array($newtarget['id'], true));

      if($targetInstalError != '') {$this->wsd_render_agent_install_issues($targetInstalError);}

      $this->wsd_render_target_status();  
    }

    function wsd_process_new_user_form()
    {
      //print "wsd_process_new_user_form<br>";

        $email = $_POST['wsd_new_user_email'];
        $name = $_POST['wsd_new_user_name'];
        $surname = $_POST['wsd_new_user_surname'];
        $password	= $_POST['wsd_new_user_password'];
        $password_re = $_POST['wsd_new_user_password_re'];

        if (empty($email)) {
            $this->wsd_render_new_user('Email is required.');
            return;
        }
        if (empty($name)) {
            $this->wsd_render_new_user('Name is required.');
            return;
        }
        if (empty($surname)) {
            $this->wsd_render_new_user('Surname is required.');
            return;
        }
        if (empty($password)) {
            $this->wsd_render_new_user('Password is required.');
            return;
        }
        if ($password != $password_re) {
            $this->wsd_render_new_user('Passwords do not match.');
            return;
        }

        $register = $this->wsd_jsonRPC(self::WSD_URL_RPC, "cPlugin.register",
                              array(
                                    array("challenge"=>$_POST['recaptcha_challenge_field'],
                                          "response"=>$_POST['recaptcha_response_field']),
                                    array(
                                          "url" => $this->wsd_site_url(),
                                          "email" => $email,
                                          "name" => $name,
                                          "surname" => $surname,
                                        /* the password coming from the client already as a hash */
                                          "pass" => $password,
                                          "source" => self::WSD_SOURCE
                                          )
                                    ));
      if($register == null)
      {
        if($GLOBALS['wsd_last_err']['code'] == self::WSD_ERROR_WPP_INVALID_CAPTCHA)
        {
          $this->wsd_render_new_user('Invalid captcha. Please try again.');
          return;
        }
        if($GLOBALS['wsd_last_err']['code'] == self::WSD_ERROR_WPP_USER_EXIST)
        {
          $this->wsd_render_new_user("This user is already registered. To continue with this user, please use the login form above or register with a new user name.");
          return;
        }
        $this->wsd_render_new_user('Registration failed! Please try again.');
        return;
      }
      $user = get_option("WSD-USER");
      if($user === false) {
          add_option("WSD-USER", $email);
      } 
      else {update_option("WSD-USER", $email);}
      
      $this->wsd_add_or_process_target();
    }

    function wsd_render_target_status()
    {
      #echo "wsd_render_target_status<br>";
      $user = get_option('WSD-USER');
      if((!is_string($user))||($user == "") ) {$user = get_option("admin_email"); } 
      $status = $this->wsd_jsonRPC(self::WSD_URL_RPC, "cPlugin.status", array($user, get_option('WSD-TARGETID')));
      if($status === null)
      {
        $this->wsd_render_error();
        return;  
      }
      if((!array_key_exists('active', $status)) || ($status['active'] !== 1))
      {
        //our target is not valid anymore
        delete_option('WSD-TARGETID');
        return false;
      }

      echo '<p class="wsd-inside">';
        echo 'Thank you for registering with WebsiteDefender.  Please navigate to the <a target="_blank" href="https://dashboard.websitedefender.com/">WebsiteDefender dashboard</a> to see the alerts.';
      echo "</p>";

      $enabled = array_key_exists('enabled', $status) ? $status['enabled'] : null;
      $scanned = array_key_exists('scanned', $status) ? $status['scanned'] : null;
      $agentless = array_key_exists('agentless', $status) ? $status['agentless'] : null;

      if (!is_numeric($enabled) || !is_numeric($scanned) || !is_numeric($agentless))
      {
          $this->wsd_render_error('Invalid server response.');
          return;
      }
      $enabled = intval($enabled);
      $scanned = intval($scanned);
      $agentless = intval($agentless);
      ?>

    <div id="wsd-target-status-holder" class="wsd-inside">
        <p class="wsd-target-status-title">
            Website status on Website Defender
        </p>
        <div class="wsd-target-status-section">
            <?php
//                $statusText = 'NO';
//                if ($enabled == 1) {
//                    $statusText = 'YES';
//                }
                $statusText = (($enabled == 1) ? 'YES' : 'NO');

                echo '<span class="wsd-target-status-section-label">Enabled: </span>',
                     '<span class="wsd-target-status-section-', $enabled ? 'enabled' : 'disabled', '">', $statusText, '</span>';
            ?>
        </div>
        <div class="wsd-target-status-section">
            <?php
//                $statusText = 'NO';
//                if ($scanned == 1) {
//                    $statusText = 'YES';
//                }
                $statusText = (($scanned == 1) ? 'YES' : 'NO');

                echo '<span class="wsd-target-status-section-label">Scanned: </span>',
                     '<span class="wsd-target-status-section-', $scanned ? 'enabled' : 'disabled', '">', $statusText, '</span>';
            ?>
        </div>
        <div class="wsd-target-status-section">
            <?php
//                $statusText = 'UP';
//                if ($agentless == 1) {
//                    $statusText = 'DOWN';
//                }
                $statusText = (($agentless == 1) ? 'DOWN' : 'UP');

                echo '<span class="wsd-target-status-section-label">Agent status: </span>',
                     '<span class="wsd-target-status-section-', $agentless ? 'disabled' : 'enabled', '">', $statusText, '</span>';
            ?>
        </div>
    </div>

    <?php return true; }

    function wsd_render_main()
    {
      if(1==0)
      {
        delete_option('WSD-TARGETID');
        delete_option("WSD-COOKIE");
        delete_option("WSD-USER");
        return;
      }

      if(isset($_POST['wsd-new-user']))
      {
        $this->wsd_process_new_user_form();
        return;
      }

      if(isset($_POST['wsd-login']))
      {
        $this->wsd_process_login();
        return;
      }

      if(isset($_POST['wsd_update_target_id']))
      {
        $this->wsd_process_add_target_id();
        return;
      }

      $targetid = get_option("WSD-TARGETID");
      if($targetid !== false)
      {
        $this->wsd_render_target_status();
        return;
      }

      $hello = $this->wsd_jsonRPC(self::WSD_URL_RPC, "cPlugin.hello", $this->wsd_site_url());
      if($hello == null)
      {
        $this->wsd_render_error();
        return;
      }

      if($hello == 'registered')
      {
        $this->wsd_render_add_target_id();
        return;
      }
      elseif($hello == 'new')
      {
        //$user = get_option("WSD-USER"); if($user === false)
        $this->wsd_render_new_user();
        //else wsd_render_user_login();
      }
      else
      {
        $this->wsd_render_error("Invalid server response.");
        return;
      }
    }

}
/* End of file: swWSD.php */
