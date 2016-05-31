# Heartbeat scripts

Container for [Heartbeat](http://linux-ha.org/wiki/Heartbeat) related scripts

## resource.d/heartbeat_pfsense.php

Set up ip alias, restart related OpenVPN on pfSense

### How to use

*1. Install heartbeat package on pfsense:*

```
pkg install heartbeat
```
*2. Add the following inline to `/etc/rc.conf`*
```
heartbeat_enable="YES"
```
*3. Tailor ha.cf and authkeys in folder `/usr/local/etc/ha.d`*

*4. Put `heartbeat_pfsense` to `/usr/local/etc/ha.d/resource.d`*

You might want to change the name of `heartbeat_pfsense` to something else, it's probably too general to be in `/usr/local/etc/ha.d/resource.d` although the name is relatively OK to be in a source repo

*5. Add ip alias info to `/usr/local/etc/ha.d/haresources`*
```
fw01.localdomain heartbeat_pfsense::192.168.23.251/24/em1
```
*6. Star/Stop Heartbeat*
```
Usage: /usr/local/etc/rc.d/heartbeat [fast|force|one|quiet](start|stop|restart|rcvar|enabled|reload|gracefulstop|status|poll)
```
*7. Takeover/Standby perticular node*
```
[/usr/local/etc/ha.d]$ ls /usr/local/lib/heartbeat/hb_
hb_addnode@   hb_delnode@   hb_setsite@   hb_setweight@ hb_standby@   hb_takeover@

[/usr/local/etc/ha.d]$ /usr/local/lib/heartbeat/hb_takeover

[/usr/local/etc/ha.d]$ /usr/local/lib/heartbeat/hb_standby
```
### Other doc
[Deployment of a High-Availability Cluster on FreeBSD](http://www.todoo.biz/cluster_ha_freebsd.php)
