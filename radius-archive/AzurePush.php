<?php

  class AzurePush{

    private $azConn, $vmConn, $radConn;

    function __construct(){

      $vmHost = "localhost";
      $vmUser = "root";
      $vmPass = "";

      $serverName = "aamrareportserver.database.windows.net";
      $connOptions = array(
        "Database" => "AamraReportserver",
        "Uid" => "rndadmin",
        "PWD" => "admin123ADMIN"
      );
      $this->radConn = mysqli_connect($vmHost, $vmUser, $vmPass, 'reportserver');
      $this->vmConn = mysqli_connect($vmHost, $vmUser, $vmPass, 'reportserver');

      $this->azConn = sqlsrv_connect($serverName, $connOptions);
      if(sqlsrv_errors()){
        echo "<pre>";
        print_r(sqlsrv_errors());
        echo "</pre>";
      }
    }

    public function pushToAzure(){

      $query = "SELECT TOP (1) radacctid FROM dbo.radiusDump ORDER BY radacctid DESC";
      $result = sqlsrv_query($this->azConn, $query);

      if($result != FALSE){
        $result = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $lastID = $result['radacctid'];
        if($lastID == ''){
          $lastID = 0;
        }
      }else{
        $lastID = 0;
      }

      $query = "SELECT * FROM radacct WHERE radacctid > '$lastID' ORDER BY radacctid ASC LIMIT 20000";
      $result = mysqli_query($this->vmConn, $query);
      $result = mysqli_fetch_all($result, MYSQLI_ASSOC);

      foreach($result as $row){

        $radacctid = $row['radacctid'];
        $username = $row['username'];
        $nasipaddress = $row['nasipaddress'];
        $nasportid = $row['nasportid'];
        $sessionStart = $row['acctstarttime'];
        $sessionStop = $row['acctstoptime'];

        if($sessionStop == '0000-00-00 00:00:00'){
          $sessionStop = NULL;
        }

        $dataUpload = $row['acctinputoctets'];
        $dataDownload = $row['acctoutputoctets'];
        $sessionTerminateCause = $row['acctterminatecause'];
        $macAddress = $row['callingstationid'];
        $deviceVendor = $this->getVendor($macAddress);
        $carrier = $this->getDeviceCarrier($username);

        $insertQuery = "INSERT INTO dbo.radiusDump (radacctid, username, nasipaddress, nasportid, sessionStart, sessionStop, dataUpload, dataDownload, sessionTerminateCause, macAddress, deviceVendor, carrier) VALUES('$radacctid', '$username', '$nasipaddress', '$nasportid', '$sessionStart', '$sessionStop', '$dataUpload', '$dataDownload', '$sessionTerminateCause', '$macAddress', '$deviceVendor', '$carrier')";
        $result = sqlsrv_query($this->azConn, $insertQuery);
        if($result == FALSE){
          echo "<pre>";
          print_r(sqlsrv_errors());
          echo "<br>";
          echo $insertQuery;
          echo "</pre>";
        }else{
          echo "<pre>";
          echo $insertQuery;
          echo "</pre>";
        }

      }

    }

    public function pushToAzureMain(){

      $query = "SELECT TOP (1) radacctid FROM dbo.radiusreport ORDER BY radacctid DESC";
      $result = sqlsrv_query($this->azConn, $query);

      if($result != FALSE){
        $result = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $lastID = $result['radacctid'];
        if($lastID == ''){
          $lastID = 0;
        }
      }else{
        $lastID = 0;
      }

      $query = "SELECT * FROM radiusreport WHERE radacctid > '$lastID' ORDER BY radacctid ASC LIMIT 20000";
      $result = mysqli_query($this->radConn, $query);
      $result = mysqli_fetch_all($result, MYSQLI_ASSOC);

      foreach($result as $row){

        $radacctid = $row['radacctid'];
        $username = $row['username'];
        $nasipaddress = $row['nasipaddress'];
        $nasportid = $row['nasportid'];
        $sessionStart = $row['sessionStart'];
        $sessionStop = $row['sessionStop'];

        if($sessionStop == '0000-00-00 00:00:00'){
          $sessionStop = NULL;
        }

        $dataUpload = $row['dataUpload'];
        $dataDownload = $row['dataDownload'];
        $sessionTerminateCause = $row['sessionTerminate'];
        $macAddress = $row['macAddress'];
        $deviceVendor = NULL;
        $carrier = $row['carrier'];

        $insertQuery = "INSERT INTO dbo.radiusreport (radacctid, username, nasipaddress, nasportid, sessionStart, sessionStop, dataUpload, dataDownload, sessionTerminate, macAddress, carrier) VALUES('$radacctid', '$username', '$nasipaddress', '$nasportid', '$sessionStart', '$sessionStop', '$dataUpload', '$dataDownload', '$sessionTerminateCause', '$macAddress', '$carrier')";
        $result = sqlsrv_query($this->azConn, $insertQuery);
        if($result == FALSE){
          echo "<pre>";
          print_r(sqlsrv_errors());
          echo "<br>";
          echo $insertQuery;
          echo "</pre>";
        }else{
          echo "<pre>";
          echo $insertQuery;
          echo "</pre>";
        }

      }

    }

    public function getVendor($mac){
      $mac_address = $mac;
        $url = "http://api.macvendors.com/" . urlencode($mac_address);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        if($response) {
          if($response == 'Vendor not found'){
            $response = 'Unknown';
          }
          return $response;
        } else {
          return "Not Found";
        }
    }

    public function getDeviceCarrier($mobNum){

      $carrierPrefix = substr($mobNum, 0, 3);

      if($carrierPrefix == '015'){
        return "Teletalk";
      }else if($carrierPrefix == '016'){
        return "Airtel";
      }else if($carrierPrefix == '017'){
        return "Grameenphone";
      }else if($carrierPrefix == '018'){
        return "Robi";
      }else if($carrierPrefix == '019'){
        return "Banglalink";
      }else{
        return "Unknown carrier";
      }
    }

  }



 ?>
