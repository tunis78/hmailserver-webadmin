<?php
if (!defined('IN_WEBADMIN'))
	exit();

$username = hmailGetVar("username", "");
$password = hmailGetVar("password", "");

if (Allow_admin($username) && Login($username, $password)) {
	header("refresh:0; url=" . $hmail_config['rooturl']);
	exit();
} else {
	// Login failed.
	LoginError();
}

function Login($username, $password) {
	global $obBaseApp;

	if($username == "" || $password == "") {
		LoginError();
	}

	$obAccount = $obBaseApp->Authenticate($username, $password);
	if (!isset($obAccount))
		LoginError();

	$_SESSION['session_loggedin'] = 1;
	$_SESSION['session_adminlevel'] = $obAccount->AdminLevel();
	$_SESSION['session_username'] = $obAccount->Address;
	$_SESSION['session_password'] = $password;
	$_SESSION['session_domainid'] = $obAccount->DomainID();
	$_SESSION['session_accountid'] = $obAccount->ID();

	return true;
}

function LoginError() {
	global $hmail_config;
	header("refresh:0; url=" . $hmail_config['rooturl'] . "index.php?page=login&error=1");
	exit();
}

function Allow_admin($username) {
	if(strtolower($username) != 'administrator') return true;
	global $hmail_config;
	if($hmail_config['allow_admin_login'] === 1) return true;
	if($hmail_config['allow_admin_login'] === 2 && isset($_SERVER['REMOTE_ADDR'])) {
		if(($ipv = ipv4_or_ipv6($_SERVER['REMOTE_ADDR'])) === false) return false;
		$rangelist = explode(',', $hmail_config['allow_admin_addresses']);
		foreach ($rangelist as $range) {
			if($ipv === 4 && ipv4_or_ipv6($range) === 4 && ipv4_in_range($_SERVER['REMOTE_ADDR'], $range)) return true;
			if($ipv === 6 && ipv4_or_ipv6($range) === 6 && ipv6_in_range($_SERVER['REMOTE_ADDR'], $range)) return true;
		}
	}
	return false;
}

function ipv4_or_ipv6($ip) {
	if(strpos($ip, '/') !== false) list($ip, $netmask) = explode('/', $ip);
	if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
		return 4;
	}
	elseif(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
		return 6;
	}
	else {
		return false;
	}
}

function ipv4_in_range($ip, $range) {
	if(strpos($range, '/') == false) $range .= '/32';
	list($range, $netmask) = explode('/', $range, 2);
	$range_long = ip2long($range);
	$ip_long = ip2long($ip);
	$wildcard_long = pow(2, (32 - $netmask)) - 1;
	$netmask_long = ~ $wildcard_long;
	return (($ip_long & $netmask_long) == ($range_long & $netmask_long));
}

	function ipv6_in_range($ip, $range) {
	if(strpos($range, '/') == false) $range .= '/128';
	list($range, $maskbits) = explode('/', $range, 2);
	$range = inet_pton($range);
	$range_bin = inet_to_bits($range);
	$ip = inet_pton($ip);
	$ip_bin = inet_to_bits($ip);
	$ip_bits = substr($ip_bin, 0, $maskbits);
	$range_bits = substr($range_bin, 0, $maskbits);
	return ($ip_bits == $range_bits);
}

function inet_to_bits($inet)  {
	$unpacked = unpack('A16', $inet);
	$unpacked = str_split($unpacked[1]);
	$binaryip = '';
	foreach ($unpacked as $char) {
		$binaryip .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
	}
	$binaryip = str_pad($binaryip, 128, '0');
	return $binaryip;
}
?>