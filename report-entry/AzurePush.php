<?php

class AzurePush{

  private $azConn, $vmConn;

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

  public function displayTest(){

    $query = "SELECT * FROM dbo.test";
    $results = sqlsrv_query($this->azConn, $query);
    $results = sqlsrv_fetch_array($results, SQLSRV_FETCH_ASSOC);

    return $results;
  }

  public function getTotals(){
    $query = "SELECT * FROM wifireporttotals ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($this->vmConn, $query);
    $result = mysqli_fetch_array($result, MYSQLI_ASSOC);

    return $result;
  }

  public function getTopBW(){
    $query = "SELECT * FROM toptenbwusers ORDER BY totalBWUsageBytes DESC";
    $result = mysqli_query($this->vmConn, $query);
    $result = mysqli_fetch_all($result, MYSQLI_ASSOC);

    return $result;
  }

  public function getTopLog(){
    $query = "SELECT * FROM toptenlognnum ORDER BY totalLogins DESC";
    $result = mysqli_query($this->vmConn, $query);
    $result = mysqli_fetch_all($result, MYSQLI_ASSOC);

    return $result;
  }

  public function insertReportTotal(){
    $totals = $this->getTotals();

    $totalUp = $totals['totalUp'] / (1024 * 1024 * 1024 * 1024);
    $totalDown = $totals['totalDown'] / (1024 * 1024 * 1024 * 1024);
    $totalBW = $totals['totalBW'] / (1024 * 1024 * 1024 * 1024);
    $totalLogin = $totals['totalLogin'];
    $activeSessions = $totals['activeSessions'];
    $dailyNew = $totals['newDailyUsers'];
    // $totalLocation = $totals['totalLocation'];
    // $totalDevices = $totals['totalDevices'];

    $checkQuery = "SELECT * FROM dbo.wifireporttotals";
    $checkResult = sqlsrv_query($this->azConn, $checkQuery);
    if($checkResult != FALSE){
      sqlsrv_query($this->azConn, "DELETE FROM dbo.wifireporttotals WHERE 1 = 1");
    }

    $updateQuery = "INSERT INTO dbo.wifireporttotals (totalUp, totalDown, totalBW, totalLogin, activeSessions, newDailyUsers) VALUES('$totalUp', '$totalDown', '$totalBW', '$totalLogin', '$activeSessions', '$dailyNew')";
    $result = sqlsrv_query($this->azConn, $updateQuery);
  }

  // public function insertTopTenBW(){
  //   $topBW = $this->getTopBW();
  //
  //   $checkQuery = "SELECT * FROM dbo.toptenbwusers";
  //   $checkResult = sqlsrv_query($this->azConn, $checkQuery);
  //
  //   if($checkResult != FALSE){
  //     sqlsrv_query($this->azConn, "DELETE FROM dbo.toptenbwusers WHERE 1 = 1");
  //   }
  //
  //   foreach($topBW as $row){
  //     $username = $row['username'];
  //     $totalBW = $row['totalBWUsageBytes'];
  //
  //     $query = "INSERT INTO dbo.toptenbwusers (username, totalBWUsageBytes) VALUES('$username', '$totalBW')";
  //     $result = sqlsrv_query($this->azConn, $query);
  //   }
  // }
  //
  // public function insertTopLogins(){
  //
  //   $topLog = $this->getTopLog();
  //
  //   $checkQuery = "SELECT * FROM dbo.toptenloginnum";
  //   $checkResult = sqlsrv_query($this->azConn, $checkQuery);
  //
  //   if($checkResult != FALSE){
  //     sqlsrv_query($this->azConn, "DELETE FROM dbo.toptenloginnum WHERE 1 = 1");
  //   }
  //
  //   foreach($topLog as $row){
  //     $username = $row['username'];
  //     $totalLogins = $row['totalLogins'];
  //
  //     $query = "INSERT INTO dbo.toptenloginnum (username, totalLogins) VALUES('$username', '$totalLogins')";
  //     $result = sqlsrv_query($this->azConn, $query);
  //   }
  //
  // }

  public function insertGraphData(){

    $checkQuery = "SELECT * FROM dbo.graphdata";
    $checkResult = sqlsrv_query($this->azConn, $checkQuery);

    if($checkResult != FALSE){
      sqlsrv_query($this->azConn, "DELETE FROM dbo.graphdata WHERE 1 = 1");
    }

    $query = "SELECT * FROM graphData";
    $result = mysqli_query($this->vmConn, $query);
    $result = mysqli_fetch_all($result, MYSQLI_ASSOC);

    foreach($result as $row){
      $hour = $row['hourOfDay'];
      $users = $row['userLogins'];
      $totalBW = $row['dataUsage'];
      $dataUp = $row['dataUp'];
      $dataDown = $row['dataDown'];

      $insertQuery = "INSERT INTO dbo.graphdata (hourOfDay, userLogins, BWConsumed, dataUp, dataDown) VALUES('$hour', '$users', '$totalBW', '$dataUp', '$dataDown')";
      $result = sqlsrv_query($this->azConn, $insertQuery);

    }
  }

  public function insertAPDetails(){

    $query = "SELECT * FROM apDetails";
    $result = mysqli_query($this->vmConn, $query);
    $result = mysqli_fetch_array($result);

    $totalAP = $result['totalAP'];
    $devicesUp = $result['apUp'];
    $devicesDown = $result['apDown'];

    $query = "SELECT * FROM dbo.apDet";

    $checkResult = sqlsrv_query($this->azConn, $query);

    if($checkResult != FALSE){
      sqlsrv_query($this->azConn, "DELETE FROM dbo.apDet WHERE 1 = 1");
    }

    $insertQuery = "INSERT INTO dbo.apDet (totalAP, apDown, apUp) VALUES ('$totalAP', '$devicesDown', '$devicesUp')";
    $result = sqlsrv_query($this->azConn, $insertQuery);

  }

  public function insertUpgradation(){

    $query = "SELECT * FROM upgradation WHERE effective >= '2017'";
    $result = mysqli_query($this->vmConn, $query);
    $selResult = mysqli_fetch_all($result, MYSQLI_ASSOC);

    if($selResult != NULL){
      $checkQuery = "SELECT * FROM dbo.upgradation";
      $checkResult = sqlsrv_query($this->azConn, $checkQuery);

      if($checkResult != FALSE){
        $delQuery = "DELETE FROM dbo.upgradation WHERE 1 = 1";
        sqlsrv_query($this->azConn, $delQuery);
      }

      foreach($selResult as $row){

        $sid = $row['sid'];
        $actmgr = $row['actmgr'];
        $custname = $row['custname'];
        $custname = str_replace("'", "''", $custname);
        $sitename = $row['sitename'];
        $sitename = str_replace("'", "''", $sitename);
        $bwupgrade = $row['bwupgrade'];
        $gpamtadd = $row['gpamtadd'];
        $effective = $row['effective'];
        $mrcadd = $row['mrcadd'];

        $insertQuery = "INSERT INTO dbo.upgradation (sid, actmgr, custname, sitename, bwupgrade, gpamtadd, effective, mrcadd) VALUES ('$sid', '$actmgr', '$custname', '$sitename', '$bwupgrade', '$gpamtadd', '$effective', '$mrcadd')";

        $result = sqlsrv_query($this->azConn, $insertQuery);
        if($result == FALSE){
          echo "<pre>";
          print_r(sqlsrv_errors());
          echo "<br>";
          echo $insertQuery;
          echo "</pre>";
        }

      }

    }

  }

  public function insertSaleOrder(){

    $query = "SELECT * FROM saleorderinfo WHERE request >= '2017'";
    $result = mysqli_query($this->vmConn, $query);
    $selResult = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // echo "<pre>";
    // print_r($selResult);
    // echo "</pre>";
    if($selResult != NULL){

      $checkQuery = "SELECT * FROM dbo.saleorderinfo";
      $checkResult = sqlsrv_query($this->azConn, $checkQuery);

      if($checkResult != FALSE){
        $delQuery = "DELETE FROM dbo.saleorderinfo WHERE 1 = 1";
        sqlsrv_query($this->azConn, $delQuery);
      }

      foreach($selResult as $row){
        $sid = $row['sid'];
        $saleby = $row['saleby'];
        $custname = $row['custname'];
        $custname = str_replace("'", "''", $custname);
        $saletype = $row['saletype'];
        $otcamt = $row['otcamt'];
        $mrcamt = $row['mrcamt'];
        $request = $row['request'];
        $delivery = $row['delivery'];
        $billing = $row['billing'];
        $billed = $row['billed'];
        $comndate = $row['comndate'];
        $mrc12 = $row['mrcamt']*12;

        $insertQuery = "INSERT INTO dbo.saleorderinfo (sid, saleby, custname, saletype, otcamt, mrcamt, request, delivery, billing, billed, comndate, mrc12) VALUES('$sid', '$saleby', '$custname', '$saletype', '$otcamt', '$mrcamt', '$request', '$delivery', '$billing', '$billed', '$comndate', '$mrc12')";
        echo "<pre>";
        echo $insertQuery;
        echo "</pre>";
        $result = sqlsrv_query($this->azConn, $insertQuery);
        if($result == FALSE){
          echo "<pre>";
          print_r(sqlsrv_errors());
          echo "</pre>";
        }
      }

    }
  }

  public function insertGP(){

    $query = "SELECT saleby, SUM(saleamt) as totalSale, SUM(lostamt) as totalLoss, SUM(gpamt) as totalGP FROM `gporderinfo` WHERE billdate >= '2017' GROUP BY saleby ORDER BY totalGP DESC";
    $result = mysqli_query($this->vmConn, $query);
    $result = mysqli_fetch_all($result, MYSQLI_ASSOC);

    if($result){
      $checkQuery = "SELECT * FROM dbo.personGP";
      $checkResult = sqlsrv_query($this->azConn, $checkQuery);

      if($checkResult != FALSE){
        $delQuery = "DELETE FROM dbo.personGP WHERE 1 = 1";
        sqlsrv_query($this->azConn, $delQuery);
      }

      foreach($result as $row){

        $saleby = $row['saleby'];
        $totalSale = $row['totalSale'];
        $totalLoss = $row['totalLoss'];
        $totalGP = $row['totalGP'];

        $insertQuery = "INSERT INTO dbo.personGP(saleby, saleamt, lostamt, gpamt) VALUES('$saleby', '$totalSale', '$totalLoss', '$totalGP')";
        $result = sqlsrv_query($this->azConn, $insertQuery);
        if($result == FALSE){
          echo "<pre>";
          print_r(sqlsrv_errors());
          echo "</pre>";
        }
      }

    }

  }

  public function getTopTenGP(){

    $query = "SELECT saleby, SUM(gpamt) as totalGP FROM gporderinfo WHERE billdate >= '2017' GROUP BY saleby ORDER BY totalGP DESC LIMIT 10";
    $result = mysqli_query($this->vmConn, $query);
    $result = mysqli_fetch_all($result, MYSQLI_ASSOC);

    if($result){

      $checkQuery = "SELECT * FROM dbo.toptenGP";
      $checkResult = sqlsrv_query($this->azConn, $checkQuery);

      if($checkResult != FALSE){
        $delQuery = "DELETE FROM dbo.toptenGP WHERE 1 = 1";
        sqlsrv_query($this->azConn, $delQuery);
      }

      foreach($result as $row){

        $saleby = $row['saleby'];
        $gpamt = $row['totalGP'];

        $insertQuery = "INSERT INTO dbo.toptenGP (saleby, gpamt) VALUES('$saleby', '$gpamt')";
        $result = sqlsrv_query($this->azConn, $insertQuery);
        if($result == FALSE){
          echo "<pre>";
          print_r(sqlsrv_errors());
          echo "</pre>";
        }
      }

    }

  }

  public function getTopTenSale(){

    $query = "SELECT saleby, SUM(saleamt) as totalSale FROM gporderinfo WHERE billdate >= '2017' GROUP BY saleby ORDER BY totalSale DESC LIMIT 10";
    $result = mysqli_query($this->vmConn, $query);
    $result = mysqli_fetch_all($result, MYSQLI_ASSOC);

    if($result){

      $checkQuery = "SELECT * FROM dbo.toptenSale";
      $checkResult = sqlsrv_query($this->azConn, $checkQuery);

      if($checkResult != FALSE){
        $delQuery = "DELETE FROM dbo.toptenSale WHERE 1 = 1";
        sqlsrv_query($this->azConn, $delQuery);
      }

      foreach($result as $row){

        $saleby = $row['saleby'];
        $saleamt = $row['totalSale'];

        $insertQuery = "INSERT INTO dbo.toptenSale (saleby, saleamt) VALUES('$saleby', '$saleamt')";
        $result = sqlsrv_query($this->azConn, $insertQuery);
        if($result == FALSE){
          echo "<pre>";
          print_r(sqlsrv_errors());
          echo "</pre>";
        }
      }

    }
  }

  public function insertTicket(){
    $query = "SELECT TOP (1) ticketID FROM dbo.ticketInfo ORDER BY ticketID DESC";
    $result = sqlsrv_query($this->azConn, $query);

    if($result == FALSE){
      $lastTicketID = 0;
    }else{
      $result = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
      $lastTicketID = $result['ticketID'];
    }

    // echo $lastTicketID;
    $query = "SELECT * FROM ticketInfo WHERE ticketID >= '$lastTicketID' ORDER BY ticketID DESC";
    $result = mysqli_query($this->vmConn, $query);
    $result = mysqli_fetch_all($result, MYSQLI_ASSOC);

    if($result){

      foreach($result as $row){

        $ticketID = $row['ticketID'];
        $category = $row['category'];
        $subcategory = $row['subcategory'];

        if($row['status'] == 0){
          $status = 'Awaiting';
        }else if($row['status'] == 1){
          $status = 'Responded';
        }else{
          $status = 'Closed';
        }

        $tStamp = $row['tStamp'];

        $insertQuery = "INSERT INTO dbo.ticketInfo (ticketID, category, subcategory, status, tStamp) VALUES ('$ticketID', '$category', '$subcategory', '$status', '$tStamp')";
        $result = sqlsrv_query($this->azConn, $insertQuery);
        if($result == FALSE){
          echo "<pre>";
          print_r(sqlsrv_errors());
          echo "</pre>";
        }
      }
    }
  }

  public function insertTicketAll(){

    $query = "SELECT TOP(1) SUBSTRING([ticketID], 5, 5) as lastTicketID FROM dbo.ticketInfoAll ORDER BY id DESC";
    $result = sqlsrv_query($this->azConn, $query);

    if($result == FALSE){
      $lastTicketID = 0;
    }else{
      $result = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
      $lastTicketID = $result['lastTicketID'];
    }

    $query = "SELECT * FROM ticketInfoAll WHERE SUBSTRING(ticketID, 5, 5) >= '$lastTicketID' ORDER BY ticketID DESC";
    $result = mysqli_query($this->vmConn, $query);
    $result = mysqli_fetch_all($result, MYSQLI_ASSOC);

    if($result){

      foreach($result as $row){

        $ticketID = $row['ticketID'];
        $subject = $row['subject'];
        $subject = str_replace("'", "''", $subject);
        $created = $row['created'];
        $createdBy = $row['createdBy'];
        $category = $row['category'];
        $subCategory = $row['subCategory'];
        $linkID = $row['linkID'];
        $companyName = $row['companyName'];
        $owner = $row['owner'];
        $owner = str_replace("'", "''", $owner);

        $dept = $row['dept'];
        $status = $row['status'];

        if($status == 0){
          $status = "Awaiting";
        }else if($status == 1){
          $status = "Responded";
        }else{
          $status = "Closed";
        }

        $priority = $row['priority'];

        if($priority == 0){
          $priority = "Low";
        }else if($priority == 1){
          $priority = "Medium";
        }else if($priority == 2){
          $priority = "High";
        }else{
          $priority = "Urgent";
        }

        $insertQuery = "INSERT INTO dbo.ticketInfoAll (ticketID, subject, created, createdBy, category, subCategory, linkID, companyName, owner, dept, status, priority) VALUES ('$ticketID', '$subject', '$created', '$createdBy', '$category', '$subCategory', '$linkID', '$companyName', '$owner', '$dept', '$status', '$priority')";
        $result = sqlsrv_query($this->azConn, $insertQuery);
        if($result == FALSE){
          echo "<pre>";
          print_r(sqlsrv_errors());
          echo "</pre>";
        }

      }

    }

  }

  public function locEntry(){
    $query = "SELECT * FROM hotspotLoc";
    $result = mysqli_query($this->vmConn, $query);
    $result = mysqli_fetch_all($result, MYSQLI_ASSOC);

    if($result){
      $checkQuery = "SELECT id FROM dbo.hotspotLoc";
      $checkResult = sqlsrv_query($this->azConn, $checkQuery);
    }
  }

}

?>
