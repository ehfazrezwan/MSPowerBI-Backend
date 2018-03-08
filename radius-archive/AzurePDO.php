<?php

  class AzurePDO{

    private $vmConn, $azConn, $radiusConn;

    function __construct(){

      $radiusHost = "wifi.aamra.com.bd";
      $radiusUsername = "ehfaz";
      $radiusPassword = "anl123";

      $vmHost = "localhost";
      $vmUser = "phpmyadmin";
      $vmPass = "WErsdfser##";

      $serverName = "aamrareportserver.database.windows.net";
      $database = "AamraReportserver";
      $uid = "rndadmin";
      $pwd = "admin123ADMIN";

      $this->vmConn = mysqli_connect($vmHost, $vmUser, $vmPass, 'reportserver');
      $this->radiusConn = mysqli_connect($radiusHost, $radiusUsername, $radiusPassword, 'radius');

      try{
        $this->azConn = new PDO(
          "dblib:host=$serverName;dbname=$database",
          $uid,
          $pwd
        );
      }
      catch(PDOException $e) {
          die("Error connecting to SQL Server: " . $e->getMessage());
      }
      echo "<p>Connected to SQL Server</p>\n";


    }

    public function pushToAzure(){

      $query = "SELECT TOP(1) id FROM dbo.radiusreport ORDER BY id DESC";
      $result = $this->azConn->query($query);

      if($result){
        $result = $result->fetch(PDO::FETCH_ASSOC);
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
          // $locationZone = $row['locationZone'];
          // $locationZone = str_replace("'", "''", $locationZone);
          $macAssignment = $row['macAssignment'];
          $calledstationid = $row['calledstationid'];

          if($sessionStop == NULL){
            $insertQuery = "INSERT INTO dbo.radiusreport (id, username, nasipaddress, nasportid, sessionStart, sessionStop, dataUpload, dataDownload, sessionTerminate, macAddress, carrier, macAssignment, calledstationid) VALUES ('$id', '$username', '$nasipaddress', '$nasportid', '$sessionStart', NULL, '$dataUpload', '$dataDownload', '$sessionTerminate', '$macAddress', '$carrier', '$macAssignment', '$calledstationid')";
          }else{
            $insertQuery = "INSERT INTO dbo.radiusreport (id, username, nasipaddress, nasportid, sessionStart, sessionStop, dataUpload, dataDownload, sessionTerminate, macAddress, carrier, macAssignment, calledstationid) VALUES ('$id', '$username', '$nasipaddress', '$nasportid', '$sessionStart', '$sessionStop', '$dataUpload', '$dataDownload', '$sessionTerminate', '$macAddress', '$carrier', '$macAssignment', '$calledstationid')";
          }

          $res = $this->azConn->query($insertQuery);
          if($res == FALSE){
            echo "<pre>";
            print_r($this->azConn->errorInfo());
            echo "<br>";
            echo $insertQuery;
            echo "</pre>";
          }else{
            echo "<pre>";
            echo "Success:";
            echo $insertQuery;
            echo "</pre>";
          }
        }
      }
    }

    public function updateAzureData(){

      $query = "SELECT id FROM dbo.radiusreport WHERE sessionStop IS NULL";
      $result = $this->azConn->query($query);

      if($result){

        $result = $result->fetchAll();

        foreach($result as $row){
          $radID = $row['id'];
          $query = "SELECT id, sessionStop, dataUpload, dataDownload FROM radiusreport WHERE id = '$radID' AND sessionStop NOT LIKE '0000-00-00%'";
          $dResult = mysqli_query($this->vmConn, $query);
          $dResult = mysqli_fetch_array($dResult);

          if($dResult){

            $radID = $dResult['id'];
            $sessionStop = $dResult['sessionStop'];
            $dataUpload = $dResult['dataUpload'];
            $dataDownload = $dResult['dataDownload'];

            $updateQuery = "UPDATE dbo.radiusreport SET sessionStop = '$sessionStop', dataUpload = '$dataUpload', dataDownload = '$dataDownload' WHERE id = '$radID'";
            $updateRes = $this->azConn->query($updateQuery);

            if(!$updateRes){
              echo "<pre>";
              echo "Error: ";
              print_r($this->azConn->errorInfo());
              echo "</pre>";
            }else{
              echo "<pre>";
              echo "Success: ";
              echo $updateQuery;
              echo "</pre>";
            }

          }

        }

      }

    }

  }


 ?>
