<?php

  class Azure{

    private $vmConn, $azConn;

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

      $this->vmConn = mysqli_connect($vmHost, $vmUser, $vmPass, 'reportserver');
      $this->azConn = sqlsrv_connect($serverName, $connOptions);
      if(sqlsrv_errors()){
        echo "<pre>";
        print_r(sqlsrv_errors());
        echo "</pre>";
      }

    }

    public function pushToAzure(){

      $query = "SELECT TOP(1) id FROM dbo.radiusreport ORDER BY id DESC";
      $result = sqlsrv_query($this->azConn, $query);

      if($result){
        $result = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $lastID = $result['id'];
        if($lastID == ''){
          $lastID = 0;
        }
      }else{
        $lastID = 0;
      }

      $query = "SELECT * FROM radiusreport WHERE id > '$lastID' ORDER BY id ASC LIMIT 1000000";
      $result = mysqli_query($this->vmConn, $query);
      $result = mysqli_fetch_all($result, MYSQLI_ASSOC);

      if($result){
        foreach($result as $row){

          $id = $row['id'];
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
          $sessionTerminate = $row['sessionTerminate'];
          $macAddress = $row['macAddress'];
          $carrier = $row['carrier'];
          $locationZone = $row['locationZone'];
          $locationZone = str_replace("'", "''", $locationZone);
          $macAssignment = $row['macAssignment'];

          if($sessionStop == NULL){
            $insertQuery = "INSERT INTO dbo.radiusreport (id, username, nasipaddress, nasportid, sessionStart, sessionStop, dataUpload, dataDownload, sessionTerminate, macAddress, carrier, locationZone, macAssignment) VALUES ('$id', '$username', '$nasipaddress', '$nasportid', '$sessionStart', NULL, '$dataUpload', '$dataDownload', '$sessionTerminate', '$macAddress', '$carrier', '$locationZone', '$macAssignment')";
          }else{
            $insertQuery = "INSERT INTO dbo.radiusreport (id, username, nasipaddress, nasportid, sessionStart, sessionStop, dataUpload, dataDownload, sessionTerminate, macAddress, carrier, locationZone, macAssignment) VALUES ('$id', '$username', '$nasipaddress', '$nasportid', '$sessionStart', '$sessionStop', '$dataUpload', '$dataDownload', '$sessionTerminate', '$macAddress', '$carrier', '$locationZone', '$macAssignment')";
          }

          $res = sqlsrv_query($this->azConn, $insertQuery);
          if($res == FALSE){
            echo "<pre>";
            print_r(sqlsrv_errors());
            echo "<br>";
            echo $insertQuery;
            echo "</pre>";
          }
        }
      }
    }

    public function updateAzureData(){
      
    }

  }


 ?>
