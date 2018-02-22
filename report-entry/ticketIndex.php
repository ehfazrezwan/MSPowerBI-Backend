<?php

  include "DBClass.php";
  include "AzurePushPDO.php";

  $tick = new DBClass();
  $az = new AzurePushPDO();

  // $tick->newTicketParse();
  $tick->insertInfra();
  // $az->insertTicketAll();
  $az->insertInfra();
 ?>
