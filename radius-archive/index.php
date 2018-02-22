<?php

  ini_set('memory_limit','2048M');

  include "DBClass.php";
  // include "AzurePush.php";
  include "Azure.php";

  $db = new DBClass();
  $az = new Azure();
  $db->insertRadacct();
  $db->insertRadReport();
  $az->pushToAzure();
  // $db->updateDataUsage();
  // $db->radiusReport();
  // $az->pushToAzureMain();

  // $db->radiusReportLoc();
  // echo "Working";
 ?>
