<?php

  include "DBClass.php";
  include "AzurePDO.php";

  $db = new DBClass();
  $az = new AzurePDO();

  // $db->updateDataUsage();
  $az->updateAzureData();


 ?>
