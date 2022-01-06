<?php

function createBase64($string) {
    $urlCode = base64_encode($string);
    return str_replace(array('+','/','='),array('-','_',''),$urlCode);
}

function decodeBase64($base64ID) {
    $data = str_replace(array('-','_'),array('+','/'),$base64ID);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
            $data .= substr('====', $mod4);
    }
    return base64_decode($data);
}

function alphanumeric_random_string($string_length=8){
	$permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	return substr(str_shuffle($permitted_chars), 0, $string_length);
}

function all_errors($errors){
    $arr = [];
    foreach ($errors as $key => $value) {
        $arr[$key] = $value;
    }
    return $arr;
}

function contact_no($contact_no){
    if(!preg_match('/^(\+|00)[0-9]{1,3}[0-9]{4,14}(?:x.+)?$/', $contact_no))
    {
        return FALSE;
    }
    else{
        return TRUE;
    }
}

function companyUsers()
{
  $CI = & get_instance();
  $CI->load->database();
  $CI->load->model('users_model' , 'users');
  $company_id = $CI->session->userdata('user_id');
  $where = "company_id = '".$company_id."'";
  $result = $CI->users->get_where('*', $where, true, '', '', '');
  return $result;
}

function send_email_to($name='',$email='',$message='',$subject='')
{
    $CI = get_instance();
    $CI->load->library('email');
    $CI->email->initialize(array(
        'protocol'     => 'smtp',
        'smtp_host'    => 'mail.mbtc.digital',
        'smtp_port'    => '587',
        'smtp_timeout' => '7',
        'smtp_user'    => USER_EMAIL,
        'smtp_pass'    => USER_PASS,
        'newline'   => "\r\n",
        'mailtype'=>'html',
        'charset'=>'utf-8',
        'starttls'=>true,
        'wordwrap'=>true
    ));

    $page = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- So that mobile will display zoomed in -->
  <meta http-equiv="X-UA-Compatible" content="IE=edge"> <!-- enable media queries for windows phone 8 -->
  <meta name="format-detection" content="telephone=no"> <!-- disable auto telephone linking in iOS -->
  <title></title>
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">
<style type="text/css">
body {margin: 0;padding: 0;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;}
@media screen and (max-width:640px) {
.mob{right: 0!important;}
}   
@media screen and (max-width: 480px) {
  .container {width: auto!important;margin-left:10px;margin-right:10px;}
.mob{position: relative!important;text-align: center;top: 10px !important;right: 0!important;}
.mob img{width: 240px;height:auto;}
}
</style>
</head>
<body style="margin:0; padding:0;"  leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<table border="0" width="100%" height="100%" cellpadding="0" cellspacing="0" bgcolor="#eff6e4" style="font-family: "Open Sans", sans-serif;">
  <tr>
    <td align="center" valign="top" ><br>      
      
      <table border="0" width="600" cellpadding="0" cellspacing="0" class="container" style="width:600px;max-width:600px">
        <tr><td align="left" style="font-size:18px;font-weight:bold;padding-bottom:12px;color:#a964a7;padding-left:5px;padding-right:5px">
              <div style="text-align:Center;">Welcome To MBTC!</div>
          </td>
        </tr>
        <tr>
          <td align="left" style="position:relative;padding-left:24px;padding-right:24px;padding-top:24px;padding-bottom:24px;border:3px solid #f7d5ed;background-color:#ffffff;border-radius:14px;-moz-border-radius:14px;-webkit-border-radius:14px;">
           <div style="font-size:25px;font-weight:700; padding-bottom: 35px; color:#a964a7">'.$name.'! </div>
            <div style="font-size:14px;line-height:20px;text-align:left;color:#333333"><br><br>
              <div style="font-size:18px;font-weight:700;color:#5e3368; padding-bottom:10px;"> '.$subject.' </div>
              '.$message.'
              <br><br>
          </td>
        </tr>
        </table></td></tr></table>
    </body></html>';

    $CI->email->clear();
    $CI->email->to($email);
    $CI->email->from(USER_EMAIL);
    $CI->email->subject($subject);
    $CI->email->message($page);
    $CI->email->send();
    //echo $CI->email->print_debugger(); exit;
    return false;
}


?>