<?php

  class Monitoring{

    private $monConn, $vmConn;

    function __construct(){
      $monHost = "203.202.253.162";
      $monUser = "reportuser";
      $monPass = "";

      $this->monConn = mysqli_connect($monHost, $monUser, $monPass, 'nagios');

      // $vmHost = "localhost";
      $vmHost = "45.64.135.236";      
      $vmUser = "phpmyadmin";
      $vmPass = "WErsdfser##";

      $this->vmConn = mysqli_connect($vmHost, $vmUser, $vmPass, 'reportserver');

    }

    public function getUpDownAndTotal(){

      $upQuery = "SELECT COUNT(nagios_hoststatus.host_object_id) as devicesUp FROM nagios_hoststatus INNER JOIN nagios_hosts ON nagios_hoststatus.host_object_id = nagios_hosts.host_object_id WHERE address NOT LIKE '10.10%' AND address NOT LIKE '103.9%' AND output LIKE 'PING OK%'";
      $result = mysqli_query($this->monConn, $upQuery);
      $result = mysqli_fetch_array($result);

      $devicesUp = $result['devicesUp'];

      $downQuery = "SELECT COUNT(nagios_hoststatus.host_object_id) as devicesDown FROM nagios_hoststatus INNER JOIN nagios_hosts ON nagios_hoststatus.host_object_id = nagios_hosts.host_object_id WHERE address NOT LIKE '10.10%' AND address NOT LIKE '103.9%' AND output NOT LIKE 'PING OK%'";
      $result = mysqli_query($this->monConn, $downQuery);
      $result = mysqli_fetch_array($result);

      $devicesDown = $result['devicesDown'];

      $totalAP = $devicesUp + $devicesDown;

      return array(
        'devicesUp' => $devicesUp,
        'devicesDown' => $devicesDown,
        'totalAP' => $totalAP
      );
    }

    public function insertIntoDB(){
      $devDetails = $this->getUpDownAndTotal();

      $totalAP = $devDetails['totalAP'];
      $devicesUp = $devDetails['devicesUp'];
      $devicesDown = $devDetails['devicesDown'];

      $chQuery = "SELECT * FROM apdetails";
      $chResult = mysqli_query($this->vmConn, $chQuery);
      $chResult = mysqli_fetch_array($chResult);

      if($chResult){
        $delQuery = "DELETE FROM apdetails WHERE 1";
        mysqli_query($this->vmConn, $delQuery);
      }

      $insertQuery = "INSERT INTO apdetails (totalAP, apDown, apUp) VALUES ('$totalAP', '$devicesDown', '$devicesUp')";
      mysqli_query($this->vmConn, $insertQuery);
    }
  }

 ?>
