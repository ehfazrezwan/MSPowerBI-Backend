<?php

class DBClass{

  private $vmConn, $radiusConn, $radConn;

  function __construct(){
    $radiusHost = "wifi.aamra.com.bd";
    $radiusUsername = "ehfaz";
    $radiusPassword = "anl123";

    $vmHost = "localhost";
    $vmUser = "phpmyadmin";
    $vmPass = "WErsdfser##";

    $this->vmConn = mysqli_connect($vmHost, $vmUser, $vmPass, 'reportserver');
    $this->radConn = mysqli_connect($vmHost, $vmUser, $vmPass, 'reportserver');
    $this->radiusConn = mysqli_connect($radiusHost, $radiusUsername, $radiusPassword, 'radius');
    if (mysqli_connect_errno($this->vmConn) || mysqli_connect_errno($this->radiusConn)){
      echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
  }

  //Function to insert raw RADIUS data into localhost

  public function insertRadacct(){

    $query = "SELECT radacctid FROM radacct ORDER BY radacctid DESC LIMIT 1";
    $result = mysqli_query($this->vmConn, $query);
    $result = mysqli_fetch_array($result);

    if($result){
      $lastID = $result['radacctid'];

      // echo $lastID;
      $query = "SELECT * FROM radacct WHERE radacctid > '$lastID'";
      $result = mysqli_query($this->radiusConn, $query);
      $result = mysqli_fetch_all($result, MYSQLI_ASSOC);

      foreach($result as $row){

        $radacctid = $row['radacctid'];
        $acctsessionid = $row['acctsessionid'];
        $acctuniqueid = $row['acctuniqueid'];
        $username = $row['username'];
        $groupname = $row['groupname'];
        $realm = $row['realm'];
        $nasipaddress = $row['nasipaddress'];
        $nasportid = $row['nasportid'];
        $nasporttype = $row['nasporttype'];
        $acctstarttime = $row['acctstarttime'];
        $acctstoptime = $row['acctstoptime'];
        $acctsessiontime = $row['acctsessiontime'];
        $acctauthentic = $row['acctauthentic'];
        $connectinfo_start = $row['connectinfo_start'];
        $connectinfo_stop = $row['connectinfo_stop'];
        $acctinputoctets = $row['acctinputoctets'];
        $acctoutputoctets = $row['acctoutputoctets'];
        $calledstationid = $row['calledstationid'];
        $callingstationid	= $row['callingstationid'];
        $acctterminatecause	= $row['acctterminatecause'];
        $servicetype = $row['servicetype'];
        $framedprotocol = $row['framedprotocol'];
        $framedipaddress = $row['framedipaddress'];
        $acctstartdelay = $row['acctstartdelay'];
        $acctstopdelay = $row['acctstopdelay'];
        $xascendsessionsvrkey = $row['xascendsessionsvrkey'];

        $insertQuery = "INSERT INTO radacct VALUES('$radacctid', '$acctsessionid', '$acctuniqueid', '$username', '$groupname', '$realm', '$nasipaddress', '$nasportid', '$nasporttype', '$acctstarttime', '$acctstoptime', '$acctsessiontime', '$acctauthentic', '$connectinfo_start', '$connectinfo_stop', '$acctinputoctets', '$acctoutputoctets', '$calledstationid', '$callingstationid', '$acctterminatecause', '$servicetype', '$framedprotocol', '$framedipaddress', '$acctstartdelay', '$acctstopdelay', '$xascendsessionsvrkey')";

        $result = mysqli_query($this->vmConn, $insertQuery);
        if(!$result){
          echo "<pre>";
          echo mysqli_error($this->vmConn);
          echo "<br>";
          echo $insertQuery;
          echo "</pre>";
        }
      }
    }
  }

  //Function to determine carrier

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

  //Function to prepare data for entry into database from
  //the local RADIUS database

  public function insertRadReport(){

    $query = "SELECT id FROM radiusreport ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($this->vmConn, $query);
    $result = mysqli_fetch_array($result);

    if($result){
      $lastID = $result['id'];
    }else{
      $lastID = 0;
    }

    echo $lastID;

    $query = "SELECT radacctid, username, nasipaddress, nasportid, acctstarttime, acctstoptime, acctinputoctets, acctoutputoctets, acctterminatecause, locationmapping.zone, callingstationid FROM radacct LEFT JOIN locationmapping ON locationmapping.ipAddress = nasipaddress WHERE radacctid > '$lastID' ORDER BY radacctid ASC LIMIT 1000000";
    $result = mysqli_query($this->vmConn, $query);
    $result = mysqli_fetch_all($result, MYSQLI_ASSOC);

    foreach($result as $row){

      $id = $row['radacctid'];
      $username = $row['username'];
      $nasipaddress = $row['nasipaddress'];
      $nasportid = $row['nasportid'];
      $sessionStart = $row['acctstarttime'];
      $sessionStop = $row['acctstoptime'];
      $dataUpload = $row['acctinputoctets'];
      $dataDownload = $row['acctoutputoctets'];
      $sessionTerminate = $row['acctterminatecause'];
      $macAddress = $row['callingstationid'];
      $carrier = $this->getDeviceCarrier($username);
      $locationZone = $row['zone'];
      $locationZone = str_replace("'", "''", $locationZone);
      $macAssignment = substr(str_replace(":", "", $macAddress), 0, 6);;

      $query = "INSERT INTO radiusreport (id, username, nasipaddress, nasportid, sessionStart, sessionStop, dataUpload, dataDownload, sessionTerminate, macAddress, carrier, locationZone, macAssignment) VALUES('$id', '$username', '$nasipaddress', '$nasportid', '$sessionStart', '$sessionStop', '$dataUpload', '$dataDownload', '$sessionTerminate', '$macAddress', '$carrier', '$locationZone', '$macAssignment')";
      $result = mysqli_query($this->vmConn, $query);

      if(!$result){
        echo "<pre>";
        echo mysqli_error($this->vmConn);
        echo "<br>";
        echo $query;
        echo "</pre>";
      }
    }

  }

  //Function to update data download/upload details of users who were active
  //whilst running the insert script

  public function updateDataUsage(){


    $query = "SELECT radacctid FROM radacct WHERE acctstoptime LIKE '0000-00-00%'";
    $result = mysqli_query($this->vmConn, $query);
    $result = mysqli_fetch_all($result, MYSQLI_ASSOC);

    if($result){

      foreach($result as $row){
        $radID = $row['radacctid'];
        $query = "SELECT radacctid, acctstoptime, acctinputoctets, acctoutputoctets FROM radacct WHERE radacctid = '$radID' AND acctstoptime NOT LIKE '0000-00-00%'";
        $dResult = mysqli_query($this->radiusConn, $query);
        $dResult = mysqli_fetch_array($dResult);

        if($dResult){

          $radacctid = $dResult['radacctid'];
          $sessionStop = $dResult['acctstoptime'];
          $dataUpload = $dResult['acctinputoctets'];
          $dataDownload = $dResult['acctoutputoctets'];

          $updateQuery1 = "UPDATE radacct SET acctstoptime = '$sessionStop', acctinputoctets = '$dataUpload', acctoutputoctets = '$dataDownload' WHERE radacctid = '$radacctid'";
          $updateQuery2 = "UPDATE radiusreport SET sessionStop = '$sessionStop', dataUpload = '$dataUpload', dataDownload = '$dataDownload' WHERE id = '$radacctid'";

          $updateResult1 = mysqli_query($this->vmConn, $updateQuery1);
          $updateResult2 = mysqli_query($this->vmConn, $updateQuery2);

          if(!$updateResult1 || !$updateResult2){
            echo "<pre>";
            echo mysqli_error($this->vmConn);
            echo "</pre>";
          }else{
            echo "<pre>";
            echo $updateQuery1;
            echo "</pre>";
          }

        }
      }

    }

  }

}

?>
