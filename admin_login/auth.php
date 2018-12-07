<?php

// Self-Delete by query
if (isset($_REQUEST['q']) && $_REQUEST['q'] == 'del') {
	unlink(__FILE__);
	exit();
}

// Configuration

if (is_file('config.php')) {

    require_once('config.php');

}



// Startup

require_once(DIR_SYSTEM . 'startup.php');





$indexContent = file_get_contents('./index.php');



$matches = array();



preg_match('/.*define\(\'VERSION\'\,\s+\'([0-9.]+)\'\)\;/', $indexContent, $matches);

if(isset($matches[1])){

    $version = $matches[1];

} else {

    $version = '3.0.2.0';

}



$registry = new Registry();



// Config

$config = new Config();

if($version >= '2.2.0.0') {

    $config->load('default');

    $config->load('admin');

}

$registry->set('config', $config);





if ($config->get('db_autostart') && $version >= '2.2.0.0') {

    if($version >= '3.0.2.0') {

        $engine_db = $config->get('db_engine');

    } else {

        $engine_db = $config->get('db_type');

    }

    $registry->set('db', new DB($engine_db, $config->get('db_hostname'), $config->get('db_username'), $config->get('db_password'), $config->get('db_database'), $config->get('db_port')));

} else {

    if($version >= '2.0.0.0') {

        $db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);

    } else {

        $db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);



    }

    $registry->set('db', $db);

}



if($version >= '3.0.2.0') {

    $session = new Session($config->get('session_engine'), $registry);

} else {

    $session = new Session();

}



$registry->set('session', $session);



if ($config->get('session_autostart') && $version >= '2.2.0.0') {

    /*

    We are adding the session cookie outside of the session class as I believe

    PHP messed up in a big way handling sessions. Why in the hell is it so hard to

    have more than one concurrent session using cookies!



    Is it not better to have multiple cookies when accessing parts of the system

    that requires different cookie sessions for security reasons.



    Also cookies can be accessed via the URL parameters. So why force only one cookie

    for all sessions!

    */

    if($version > '3.0.0.0') {

        if (isset($_COOKIE[$config->get('session_name')])) {

            $session_id = $_COOKIE[$config->get('session_name')];

        } else {

            $session_id = '';

        }

        $session->start($session_id);



        setcookie($config->get('session_name'), $session->getId(), ini_get('session.cookie_lifetime'), ini_get('session.cookie_path'), ini_get('session.cookie_domain'));

    } else {

        $session->start();

    }

}



// Url

if ($version >= '2.3.0.0') {

    if($config->get('url_autostart')) {

        $registry->set('url', new Url($config->get('site_url'), $config->get('site_ssl')));

    }

} else if($version >= '2.2.0.0') {

    $registry->set('url', new Url($config->get('site_url')));

} else {
    $registry->set('url', new Url(HTTP_SERVER, $config->get('config_secure') ? HTTPS_SERVER : HTTP_SERVER));

}





// Response

$response = new Response();

$response->addHeader('Content-Type: text/html; charset=utf-8');

$response->setCompression($config->get('config_compression'));

$registry->set('response', $response);



//Request



// Request

$registry->set('request', new Request());



$username = isset($_GET['username']) ? $_GET['username'] : 'admin';

if (($registry->get('request')->server['REQUEST_METHOD'] == 'POST')) {

    $session->data['user_id'] = $registry->get('request')->post['user_id'];

    if($version >= '3.0.0.0') {

        $session->data['user_token'] = token(32);

        $url = $registry->get('url')->link('common/login', 'user_token='.$session->data['user_token']);

    } else if ($version >= '2.1.0.0'){

        $session->data['token'] = token(32);



        $url = $registry->get('url')->link('common/login', 'token='.$session->data['token']);

    } else {

        $session->data['token'] = md5(mt_rand());

        

        $url = $registry->get('url')->link('common/login', 'token='.$session->data['token']);

        $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');

    }

	unlink(__FILE__);

    $registry->get('response')->redirect($url);



}
if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 1) {
    $base_url = HTTPS_SERVER;
} else {
    $base_url = HTTP_SERVER;
}

$query = $registry->get('db')->query("SELECT * FROM `".DB_PREFIX."user`");

$html = '

<!DOCTYPE html>

<html dir="ltr" lang="en">

<head>

<meta charset="UTF-8" />

<title>Auth</title>

<base href="'.$base_url.'" />

<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js"></script>

<link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css" type="text/css" rel="stylesheet" />

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" type="text/css" rel="stylesheet" />

';



if($version >= '2.3.0.0') {

    $html .= '<script src="view/javascript/jquery/datetimepicker/moment/moment.min.js" type="text/javascript"></script>

    <script src="view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js" type="text/javascript"></script>';

} else if($version >= '2.0.0.0') {

    $html .= '<script src="view/javascript/jquery/datetimepicker/moment.js" type="text/javascript"></script>';

} else {

    $html .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js" type="text/javascript"></script>';

}

if($version < '2.0.0.0') {

    $html .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/superfish/1.7.9/js/superfish.min.js" type="text/javascript"></script>';

}



$html .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js" type="text/javascript"></script>

<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/css/bootstrap-datetimepicker.min.css" type="text/css" rel="stylesheet" media="screen" />

<link type="text/css" href="view/stylesheet/stylesheet.css" rel="stylesheet" media="screen" />

<script src="view/javascript/common.js" type="text/javascript"></script>

</head>

<body>

<div id="container">

<header id="header" class="navbar navbar-static-top">

  <div class="container-fluid">

    <div id="header-logo" class="navbar-header"><div  class="navbar-brand"><img src="view/image/logo.png" alt="OpenCart" title="OpenCart"></div></div>

    <a href="#" id="button-menu" class="hidden-md hidden-lg"><span class="fa fa-bars" aria-hidden="true"></span></a> </div>

</header>

';

$html.='<div id="content">

  <div class="container-fluid"><br>

    <br>

    <div class="row">

      <div class="col-sm-offset-4 col-sm-4">

        <div class="panel panel-default">

          <div class="panel-heading">

            <h1 class="panel-title"><i class="fa fa-lock" aria-hidden="true"></i> Please select your user for login.</h1>

          </div>

          <div class="panel-body">

                        <form action="' . basename(__FILE__) . '" method="post" enctype="multipart/form-data">

              <div class="form-group">

                <label for="input-user_id">Username</label>

                <div class="input-group"><span class="input-group-addon"><i class="fa fa-user" aria-hidden="true"></i></span>

                  <select type="text" name="user_id" class="form-control" id="input-user_id">';

foreach ($query->rows as $user ){

    $html.='<option value="'.$user['user_id'].'">'.$user['username'].' </option>';

}

$html .='                  </select>

                </div>

              </div>

              

              <div class="text-right">

                <button type="submit" class="btn btn-primary"><i class="fa fa-key" aria-hidden="true"></i> Login</button>

              </div>

                          </form>

          </div>

        </div>

      </div>

    </div>

  </div>

</div>';



$html.='</body></html>';

echo $html;

