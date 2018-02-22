<?php

  include "DBClass.php";
  include "AzurePush.php";
  include "Monitoring.php";

  $db = new DBClass();
  $az = new AzurePush();
  $mon = new Monitoring();

  $db->insertGraphData();
  $db->insertReportTotal();
  $mon->insertIntoDB();
  $az->insertReportTotal();
  $az->insertGraphData();
  $az->insertAPDetails();
  $time_start = microtime(true);

  // Anywhere else in the script
  echo 'Total execution time in seconds: ' . (microtime(true) - $time_start);
 ?>
