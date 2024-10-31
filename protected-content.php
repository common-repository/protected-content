<?php
/*
Plugin Name: Protected Content
Plugin URI: 
Description: Check if the user can see protected content (This is a proof of conenpt ONLY, do NOT use)
Author: Chris Black
Version: 0.2
Author URI: http://cjbonline.org
*/

add_filter('the_posts', 'PC_posts_pages_list');
add_filter('get_pages', 'PC_posts_pages_list');

add_option('PC_parent_page', '79');
add_option('PC_redirect_page', '/protected-content');
add_option('PC_denied_roles','subscriber');


function PC_posts_pages_list($posts) {
	
	if (is_admin()) {
		return $posts;
	}
	$contractTypes = PC_getContractTypes();

	$requestURL = $_SERVER['REQUEST_URI'];
	
	$user_role = PC_check_user_role($contractTypes);
	for ($i = 0; $i < count($posts); $i++) {
		$parent_id=$posts[$i]->post_parent;
		while ($parent_id != '' && $parent_id != '0') {
			$meta = get_post_meta($posts[$i]->ID,'lowestLevel', true);
			if ($parent_id == get_option('PC_parent_page') && ($meta > $user_role || $user_role == '')) {
				unset($posts[$i]);
			}
			$tmpPost = get_post($parent_id); 
			$parent_id = $tmpPost->post_parent;
		}
	}

	if (count($posts) == 0 && !is_user_logged_in()) {
		wp_redirect(get_option('home') . "/cart/index.php?main_page=login&_wp_original_http_referer=" . urlencode($requestURL));
		die();
	} else if (count($posts) == 0) {
		wp_redirect(get_option('home') . get_option('PC_redirect_page') . "?lowLevel=true");
		die();	
	}

	return $posts;
}

function PC_check_user_role($contractTypes) {
	global $userdata;
	get_currentuserinfo();
	$highRole = 0;
	if ($user = get_userdatabylogin($userdata->user_login)) {
		$user_roles = $user->wr_capabilities;
		$user_roles = array_keys($user_roles, true);
//		print_r($user_roles);
		if ($user_roles[0] == 'administrator') {
			return 10;
		}
		foreach ($contractTypes as $contract) {
			foreach ($user_roles as $role) {
				if ((strtoupper($role) == strtoupper($contract->label)) && ($contract->rank > $highRole)) {
					$highRole = $contract->rank;
				}
			}
		}
//		print("HighRole:" . $highRole . "<br/>"); 
	}
	return $highRole;
}

function PC_getContractTypes() {
	
	$debug = false;
	if (!$wsdl) {
		$wsdl = 'http://xx.xx.xx.xx:8080/wsdl/MirthHQSessionBean?wsdl';
	}
	try {
		$options = array('trace' => TRUE);
		$ws = new SoapClient($wsdl, $options);	

		if ($debug) {
			print "<pre>-- Functions: ------------\n";
			var_dump($ws->__getFunctions());
			print "-- Types: ------------\n";
			var_dump($ws->__getTypes());
		}


		$response = $ws->getContractTypes();

		if ($debug) {
			print "-- Request headers ------------\n";
			print $ws->__getLastRequestHeaders() . "\n";
			print "-- Request ------------\n";
			print $ws->__getLastRequest() . "\n";
			print "-- Response headers ------------\n";
			print $ws->__getLastResponseHeaders() . "\n";
			print "-- Response ------------\n";
			print $ws->__getLastResponse() . "\n";
			print "\n";
			print "-- Returned ------------\n";
			print_r($response);
		}

		$contractTypes = new StdClass();
		return $response->return->contractTypes;


	} catch (SoapFault $e) {
		print "SoapFault caught:\n";
		print $e->getMessage() . "\n";
		print "-- Request headers ------------\n";
		print $ws->__getLastRequestHeaders() . "\n";
		print "-- Request ------------\n";
		print $ws->__getLastRequest() . "\n";
		print "-- Response headers ------------\n";
		print $ws->__getLastResponseHeaders() . "\n";
		print "-- Response ------------\n";
		print $ws->__getLastResponse() . "\n";
		print "\n";
		print "-- Returned ------------\n";
		print_r($response);
	}
}

?>