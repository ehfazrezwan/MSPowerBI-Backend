<?php

  include "SalesParse.php";
  include "AzurePushPDO.php";

  $sale = new SalesParse();
  $az = new AzurePushPDO();
  $sale->parseSaleOrder();
  $sale->parseSaleAndGP();
  $az->insertSaleGP();
  // $sale->gpOrderInfo();
  $sale->upgradeParse();
  $az->insertSaleOrder();
  // $az->insertGP();
  // $az->testQuery();
  $az->insertUpgradation();
 ?>
