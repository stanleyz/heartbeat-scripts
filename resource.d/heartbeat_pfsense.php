#!/usr/local/bin/php -f
<?php
/* $Id$ */
/*
 * This is a Heartbeat script which will add/remove ip alias and restart OpenVPN if necessary on pfSense firewall.
 *
 * OpenVPN will be restarted only if local interface is set to the specified ip alias. There are two ways you can 
 * specify local interface for an OpenVPN on pfSense:
 *
 * 1. select the interface from drop-down list for option "Interface"
 * 2. pass "local IP_ADDR" or "--local IP_ADDR" to custom options in "Advance" section
 */

require_once("functions.inc");
require_once("config.inc");
require_once("notices.inc");
require_once("openvpn.inc");
require_once("interfaces.inc");

function usage() {
  echo "usage: argv[0] <ip-address> {start|stop} \n
      <ip-address> must be formated as IP/NETMASK/INTERFACE\n";
  exit(1);
}

/* change CIDR notation to subnet masks */ 
function get_netmask($netmask) {
  $_netmask = $netmask;
  if(is_numeric($netmask)) {
    $num_arr_netmask = array();
    $str_arr_netmask = str_split(str_pad(str_pad('', $netmask, '1'), 32, '0'), 8);
    foreach($str_arr_netmask as $s) {
      $num_arr_netmask[] = bindec($s);
    }
    $_netmask = join($num_arr_netmask, '.');
  }

  return $_netmask;
}

/* Set up IP alias here */
function set_ipalias($ip, $netmask, $if, $action = '') {
  if($action == '-') {
    $_alias = "-alias";
    log_error("Removing IP $ip with netmask $netmask from interface $if");
  } else {
    $_alias = "alias";
    log_error("Adding IP $ip with netmask $netmask to interface $if");
  }

  $_cmd = "ifconfig $if $_alias $ip netmask $netmask";
  system($_cmd, $_exit_status);
  return $_exit_status; 
}

/* Start OpenVPN clients running on this VIP, since they should be in the stopped state while the VIP is CARP Backup. */
function restart_openvpn($ip) {
  global $config;
  $_ipv4_regex = "/local[ ]+$ip/";
  if (is_array($config['openvpn']) && is_array($config['openvpn']['openvpn-client'])) {
    foreach ($config['openvpn']['openvpn-client'] as $settings) {
      if ($settings['interface'] == $ip || preg_match($_ipv4_regex, $settings['custom_options'])) {
        log_error("Restarting OpenVPN client instance on {$ip} because of transition of IP $ip.");
        openvpn_restart('client', $settings);
      }
    }
  }
  if (is_array($config['openvpn']) && is_array($config['openvpn']['openvpn-server'])) {
    foreach ($config['openvpn']['openvpn-server'] as $settings) {
      if ($settings['interface'] == $ip || preg_match($_ipv4_regex, $settings['custom_options'])) {
        log_error("Restarting OpenVPN instance on {$ip} because of transition of IP $ip.");
        openvpn_restart('server', $settings);
      }
    }
  }
}

/* Entry point of actually doing things */
function do_action($ip, $netmask, $if, $action) {
  $_ip_status = set_ipalias($ip, $netmask, $if, $action); 
  if($_ip_status == 0) {
    log_error("Successfully manipulated IP $ip with netmask $netmask on interface $if");
    restart_openvpn($ip);
  } else {
    log_error("Error when manipulating IP $ip with netmask $netmask on interface $if");
    exit($_ip_status);
  }
}

if ($argc != 3) {
  log_error("No argument supplied to $argv[0]");
  usage();
}

$ipalias = explode('/', $argv[1]);
$ip = $ipalias[0];
$netmask = get_netmask($ipalias[1]);
$interface = $ipalias[2];
$action = $argv[2];

switch($action) {
case "start":
  do_action($ip, $netmask, $interface, '+');
  break;
case "stop":
  do_action($ip, $netmask, $interface, '-');
  break;
}
?>
