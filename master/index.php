<?php
/*
    PufferPanel - A Minecraft Server Management Panel
    Copyright (c) 2013 Dane Everitt
 
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program.  If not, see http://www.gnu.org/licenses/.
 */
session_start();
require_once('core/framework/framework.core.php');

if($core->framework->auth->isLoggedIn($_SERVER['REMOTE_ADDR'], $core->framework->auth->getCookie('pp_auth_token')) === true){
	$core->framework->page->redirect('servers.php');
}

if(isset($_GET['do']) && $_GET['do'] == 'login'){
	
        if(isset($_POST['redirect']) && !empty($_POST['redirect']))
            $postLoginURL = $_GET['redirect'];
        else
            $postLoginURL = 'servers.php';
    
		$selectAccount = $mysql->prepare("SELECT * FROM `users` WHERE `password` = :password AND `email` = :email");
		$selectAccount->execute(array(
			':password' => $core->framework->auth->encrypt($_POST['password']),
			':email' => $_POST['email']
		));
		
			if($selectAccount->rowCount() == 1){
			
				/*
				 * Account Exists
				 * Set Cookies and List Servers
				 */
				$token = $core->framework->auth->keygen('12');
				(isset($_POST['remember_me']) ? $expires = time() + 604800 : $expires = time() + 1800);
				(isset($_POST['remember_me']) ? $cookieExpire = time() + 604800 : $cookieExpire = 0);
				
					setcookie("pp_auth_token", $token, $cookieExpire, '/', $core->framework->settings->get('cookie_website'));
				
					$updateUsers = $mysql->prepare("UPDATE `users` SET `session_id` = :token, `session_ip` = :ipaddr, `session_expires` = :expires WHERE `password` = :password AND `email` = :email");
					$updateUsers->execute(array(
						':token' => $token,
						':ipaddr' => $_SERVER['REMOTE_ADDR'],
						':expires' => $expires,
						':password' => $core->framework->auth->encrypt($_POST['password']),
						':email' => $_POST['email']
					));
				
					/*
					 * Send Email if Set
					 */
					$row = $selectAccount->fetch();
					if($row['notify_login_s'] == 1){
						
						/*
						 * Send Email
						 */
						$core->framework->email->generateLoginNotification('success', array(
                            'IP_ADDRESS' => $_SERVER['REMOTE_ADDR'],
                            'GETHOSTBY_IP_ADDRESS' => gethostbyaddr($_SERVER['REMOTE_ADDR'])
                        ))->dispatch($_POST['email'], $core->framework->settings->get('company_name').' - Account Login  Notification');
						    
					}
				
					$core->framework->page->redirect($postLoginURL);
			
			}else{
				
				/*
				 * Spam Prevention
				 * This makes sure that the email is even in our system so we don't randomly email a user who doesn't have an account.
				 */
				$selectUser = $mysql->prepare("SELECT * FROM `users` WHERE `email` = ?");
				$selectUser->execute(array($_POST['email']));
				
					if($selectUser->rowCount() == 1){
			
						/*
						 * Send Email if Set
						 */
						$row = $selectUser->fetch();
						if($row['notify_login_f'] == 1){
																			
							/*
							 * Send Email
							 */								 
							$core->framework->email->generateLoginNotification('failed', array(
                                'IP_ADDRESS' => $_SERVER['REMOTE_ADDR'],
                                'GETHOSTBY_IP_ADDRESS' => gethostbyaddr($_SERVER['REMOTE_ADDR'])
                            ))->dispatch($_POST['email'], $core->framework->settings->get('company_name').' - Account Login Failure Notification');															 
								    
						}
							  
					}
				$core->framework->page->redirect('index.php?error=true');
			
			}
	
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title><?php echo $core->framework->settings->get('company_name'); ?> - Login</title>
	<link href='http://fonts.googleapis.com/css?family=Droid+Sans:400,700' rel='stylesheet'>
	<link rel="stylesheet" href="assets/css/style.css">
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>  
</head>
<body>
	<div id="top-bar">
		<div class="page-full-width">
			<a href="<?php echo $core->framework->settings->get('main_website'); ?>" class="round button dark ic-left-arrow image-left">Return to website</a>
		</div> <!-- end full-width -->	
	</div> <!-- end top-bar -->
	<div id="header">
		<div class="page-full-width cf">
			<div id="login-intro" class="fl">
				<h1>Login to <?php echo $core->framework->settings->get('company_name'); ?></h1>
				<h5>Enter your credentials below</h5>
			</div> <!-- login-intro -->
		</div> <!-- end full-width -->	
	</div> <!-- end header -->
	<!-- MAIN CONTENT -->
	<div id="content">
		<form action="index.php?do=login" method="POST" id="login-form">
			<?php if(isset($_GET['error'])){ echo '<div class="error-box round">Wrong email or password.</div><br />'; } ?>
			<fieldset>
				<p>
					<label for="login-email">email</label>
					<input type="text" id="login-email" name="email" autocomplete="off" class="round full-width-input" autofocus />
				</p>
				<p>
					<label for="login-password">password</label>
					<input type="password" id="login-password" name="password" autocomplete="off" class="round full-width-input" />
				</p>
				<p>I've <a href="password.php">forgotten my password</a>.</p>
                <?php
                    (isset($_GET['redirect'])) ? '<input type="hidden" name="redirect" value="'.$_GET['redirect'].'" />' : '';
                ?>
				<input type="submit" value="LOG IN" class="button round blue image-right ic-right-arrow" />
			</fieldset>
		</form>
	</div> <!-- end content -->
	<!-- FOOTER -->
	<div id="footer">
		<p>Copyright &copy; 2012 - 2013. All Rights Reserved.<br />Running PufferPanel Version 0.3 Beta distributed by <a href="http://pufferfi.sh">Puffer Enterprises</p>	
	</div> <!-- end footer -->
</body>
</html>