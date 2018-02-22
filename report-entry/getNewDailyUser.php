<?php

  $radiusHost = "wifi.aamra.com.bd";
  $radiusUsername = "ehfaz";
  $radiusPassword = "anl123";

  $vmHost = "localhost";
  $vmUser = "extUser";
  $vmPass = "";

  $vmConn = mysqli_connect($vmHost, $vmUser, $vmPass, 'reportserver');
  $radiusConn = mysqli_connect($radiusHost, $radiusUsername, $radiusPassword, 'radius');

  $query = "SELECT COUNT(username) AS total FROM (SELECT username, COUNT(username) AS countUsers, acctstarttime FROM radacct WHERE calledstationid = 'hs-vlan230_WE' GROUP BY username) AS t1 WHERE countUsers = 1 AND acctstarttime >= CURDATE()";
  $result = mysqli_query($radiusConn, $query);
  $result = mysqli_fetch_array($result);

  $newUsers = $result['total'];

  $insertQuery = "INSERT INTO dailyNewUsers (userCount) VALUES('$newUsers')";
  $result = mysqli_query($vmConn, $insertQuery);

?>
