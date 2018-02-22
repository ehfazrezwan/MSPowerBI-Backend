<?php

  include "AzurePDO.php";
  include "DBClass.php";
  $az = new AzurePDO();
  $db = new DBClass();

  $db->insertRadacct();
  $db->insertRadReport();
  $az->pushToAzure();

 ?>
