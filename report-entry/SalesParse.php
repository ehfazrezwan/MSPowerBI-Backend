<?php

  class SalesParse{

    private $vmConn;
    function __construct(){

      $vmHost = "localhost";
      $vmUser = "phpmyadmin";
      $vmPass = "WErsdfser##";

      $this->vmConn = mysqli_connect($vmHost, $vmUser, $vmPass, 'reportserver');
    }

    public function getSales($url){

      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url
      ));

      if(!curl_exec($curl)){
        $response = "cURL error: ".curl_error($curl);
      }else{
        $response = curl_exec($curl);
      }

      curl_close($curl);

      return $response;
    }

    public function parseSaleOrder(){
      $saleOrderInfo = $this->getSales('https://ems.aamra.com.bd/api/saleorder_info_powerbi.php');
      // print_r($saleOrderInfo);
      $saleDecoded = json_decode($saleOrderInfo, true);

      // echo "<pre>";
      // print_r($saleDecoded);
      // echo "</pre>";
      $i = 0;
      //
      $query = "SELECT * FROM saleorderinfo";
      $result = mysqli_query($this->vmConn, $query);
      $result = mysqli_fetch_array($result);

      if($result != NULL){
        $delQuery = "DELETE FROM saleorderinfo WHERE 1";
        $result = mysqli_query($this->vmConn, $delQuery);
      }
      foreach($saleDecoded['response'] as $row){

        $sid = $row['sid'];
        $saleby = $row['saleby'];
        $custname = $row['custname'];
        $custname = str_replace("'", "''", $custname);
        $saletype = $row['saletype'];
        $otcamt = $row['otcamt'];
        $mrcamt = $row['mrcamt'];
        $request = $row['request'];
        $delivery = $row['delidate'];
        $billing = $row['billing'];
        $billed = $row['billed'];
        $comndate = $row['comndate'];
        $handleBy = $row['handleby'];
        $preSale = $row['presale'];

        if($delivery == '0000-00-00'){
          $delivery = $request;
        }

        if($billing == '0000-00-00'){
          $billing = $request;
        }
        if($billed == '0000-00-00'){
          $billed = $request;
        }
        if($comndate == '0000-00-00'){
          $comndate = $request;
        }

        $insertQuery = "INSERT INTO saleorderinfo (sid, saleby, custname, saletype, otcamt, mrcamt, request, delivery, billing, billed, comndate, elementID, handleBy, preSale) VALUES('$sid', '$saleby', '$custname', '$saletype', '$otcamt', '$mrcamt', '$request', '$delivery', '$billing', '$billed', '$comndate', '$i', '$handleBy', '$preSale')";

        $result = mysqli_query($this->vmConn, $insertQuery);
        if(!$result){
          echo "<pre>";
          echo mysqli_error($this->vmConn);
          echo "</pre>";
        }
        $i++;
      }
      // echo "<pre>";
      // print_r($saleDecoded);
      // echo "</pre>";

    }

    public function gpOrderInfo(){
      $gpOrderInfo = $this->getSales("https://ems.aamra.com.bd/api/salegp_info_powerbi.php");
      $gpDecoded = json_decode($gpOrderInfo, true);

      $i = 0;

      $query = "SELECT * FROM gporderinfo";
      $result = mysqli_query($this->vmConn, $query);
      $result = mysqli_fetch_array($result);

      if($result != NULL){
        $delQuery = "DELETE FROM gporderinfo WHERE 1";
        $result = mysqli_query($this->vmConn, $delQuery);
      }

      foreach($gpDecoded['response'] as $row){
        $sid = $row['sid'];
        $saleby = $row['saleby'];
        $custname = $row['custname'];
        $custname = str_replace("'", "''", $custname);
        $saletype = $row['saletype'];
        $saleamt = $row['saleamt'];
        $costamt = $row['costamt'];
        $lostamt = $row['lostamt'];
        $gpamt = $row['gpamt'];
        $billdate = $row['billdate'];
        // if($billdate == "0000-00-00"){
        //   $billdate = "1970-01-01";
        // }

        $insertQuery = "INSERT INTO gporderinfo (sid, saleby, custname, saletype, saleamt, costamt, lostamt, gpamt, billdate) VALUES('$sid', '$saleby', '$custname', '$saletype', '$saleamt', '$costamt', '$lostamt', '$gpamt', '$billdate')";
        $result = mysqli_query($this->vmConn, $insertQuery);
        if(!$result){
          echo "<pre>";
          echo mysqli_error($this->vmConn);
          echo "</pre>";
        }
      }

      // echo "<pre>";
      // print_r($gpDecoded);
      // echo "</pre>";
    }

    public function upgradeParse(){
      $upgradeInfo = $this->getSales('https://ems.aamra.com.bd/api/bwgp_info_powerbi.php');
      $upDecode = json_decode($upgradeInfo, true);

      // echo "<pre>";
      // print_r($upgradeInfo);
      // echo "</pre>";

      // $i = 0;

      $query = "SELECT * FROM upgradation";
      $result = mysqli_query($this->vmConn, $query);
      $result = mysqli_fetch_array($result);

      if($result != NULL){
        $delQuery = "DELETE FROM upgradation WHERE 1";
        $result = mysqli_query($this->vmConn, $delQuery);
      }
      foreach($upDecode['response'] as $row){

        $sid = $row['sid'];
        $actmgr = $row['actmgr'];
        $custname = $row['custname'];
        $custname = str_replace("'", "''", $custname);
        $sitename = $row['sitename'];
        $sitename = str_replace("'", "''", $sitename);
        $bwupgrade = $row['bwupgrde'];
        $gpamtadd = $row['gpamtadd'];
        $effective = $row['effective'];
        $mrcadd = $row['mrcadd'];

        $insertQuery = "INSERT INTO upgradation (sid, actmgr, custname, sitename, bwupgrade, gpamtadd, effective, mrcadd) VALUES ('$sid', '$actmgr', '$custname', '$sitename', '$bwupgrade', '$gpamtadd', '$effective', '$mrcadd')";

        $result = mysqli_query($this->vmConn, $insertQuery);
        if(!$result){
          echo "<pre>";
          echo mysqli_error($this->vmConn);
          echo "</pre>";
        }
        // $i++;
      }

    }

    public function parseSaleAndGP(){

      $newSaleInfo = $this->getSales('https://ems.aamra.com.bd/api/saleorder_gpinfo_powerbi.php');
      $newSaleDecoded = json_decode($newSaleInfo, true);

      $query = "SELECT * FROM saleandgp";
      $result = mysqli_query($this->vmConn, $query);
      $result = mysqli_fetch_array($result);

      if($result){
        $delQuery = "DELETE FROM saleandgp WHERE 1";
        $result = mysqli_query($this->vmConn, $delQuery);
      }


      foreach($newSaleDecoded['response'] as $row){

        $sid = $row['sid'];
        $saleby = $row['saleby'];
        $custname = $row['custname'];
        $custname = str_replace("'", "''", $custname);
        $saletype = $row['saletype'];
        $otcamt = $row['otcamt'];
        $mrcamt = $row['mrcamt'];
        $saleamt = $row['saleamt'];
        $costamt = $row['costamt'];
        $lostamt = $row['lostamt'];
        $gpamt = $row['gpamt'];
        $request = $row['request'];
        $delidate = $row['delidate'];
        $deliby = $row['deliby'];
        $billing = $row['billing'];
        $billed = $row['billed'];
        $presale = $row['presale'];
        $comndate = $row['comndate'];

        $insertQuery = "INSERT INTO saleandgp (sid, saleby, custname, saletype, otcamt, mrcamt, saleamt, costamt, lostamt, gpamt, request, delidate, deliby, billing, billed, presale, comndate) VALUES ('$sid', '$saleby', '$custname', '$saletype', '$otcamt', '$mrcamt', '$saleamt', '$costamt', '$lostamt', '$gpamt', '$request', '$delidate', '$deliby', '$billing', '$billed', '$presale', '$comndate')";

        $result = mysqli_query($this->vmConn, $insertQuery);
        if(!$result){
          echo "<pre>";
          echo mysqli_error($this->vmConn);
          echo "</pre>";
        }

      }

    }



  }

 ?>
