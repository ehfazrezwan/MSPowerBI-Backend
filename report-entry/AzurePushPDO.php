<?php

class AzurePushPDO{

  private $azConn, $vmConn;

  function __construct(){

    // $vmHost = "localhost";
    $vmHost = "45.64.135.236";    
    $vmUser = "phpmyadmin";
    $vmPass = "WErsdfser##";

    $serverName = "aamrareportserver.database.windows.net";
    $database = "AamraReportserver";
    $uid = "rndadmin";
    $pwd = "admin123ADMIN";
    $this->vmConn = mysqli_connect($vmHost, $vmUser, $vmPass, 'reportserver');

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

  public function displayTest(){

    $query = "SELECT * FROM dbo.test";
    $results = $this->azConn->query($query);
    $results = $results->fetch(PDO::FETCH_ASSOC);;

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
    $checkResult = $this->azConn->query($checkQuery);
    if($checkResult != FALSE){
      $this->azConn->query("DELETE FROM dbo.wifireporttotals WHERE 1 = 1");
    }

    $updateQuery = "INSERT INTO dbo.wifireporttotals (totalUp, totalDown, totalBW, totalLogin, activeSessions, newDailyUsers) VALUES('$totalUp', '$totalDown', '$totalBW', '$totalLogin', '$activeSessions', '$dailyNew')";
    $result = $this->azConn->query($updateQuery);
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
    $checkResult = $this->azConn->query($checkQuery);

    if($checkResult != FALSE){
      $this->azConn->query("DELETE FROM dbo.graphdata WHERE 1 = 1");
    }

    $query = "SELECT * FROM graphdata";
    $result = mysqli_query($this->vmConn, $query);
    $result = mysqli_fetch_all($result, MYSQLI_ASSOC);

    foreach($result as $row){
      $hour = $row['hourOfDay'];
      $users = $row['userLogins'];
      $totalBW = $row['dataUsage'];
      $dataUp = $row['dataUp'];
      $dataDown = $row['dataDown'];

      $insertQuery = "INSERT INTO dbo.graphdata (hourOfDay, userLogins, BWConsumed, dataUp, dataDown) VALUES('$hour', '$users', '$totalBW', '$dataUp', '$dataDown')";
      $result = $this->azConn->query($insertQuery);

    }
  }

  public function insertAPDetails(){

    $query = "SELECT * FROM apdetails";
    $result = mysqli_query($this->vmConn, $query);
    $result = mysqli_fetch_array($result);

    $totalAP = $result['totalAP'];
    $devicesUp = $result['apUp'];
    $devicesDown = $result['apDown'];

    $query = "SELECT * FROM dbo.apDet";

    $checkResult = $this->azConn->query($query);

    if($checkResult != FALSE){
      $this->azConn->query("DELETE FROM dbo.apDet WHERE 1 = 1");
    }

    $insertQuery = "INSERT INTO dbo.apDet (totalAP, apDown, apUp) VALUES ('$totalAP', '$devicesDown', '$devicesUp')";
    $result = $this->azConn->query($insertQuery);

  }

  public function insertUpgradation(){

    $query = "SELECT * FROM upgradation WHERE effective >= '2017'";
    $result = mysqli_query($this->vmConn, $query);
    $selResult = mysqli_fetch_all($result, MYSQLI_ASSOC);

    if($selResult != NULL){
      $checkQuery = "SELECT * FROM dbo.upgradation";
      $checkResult = $this->azConn->query($checkQuery);

      if($checkResult != FALSE){
        $delQuery = "DELETE FROM dbo.upgradation WHERE 1 = 1";
        $this->azConn->query($delQuery);
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

        $result = $this->azConn->query($insertQuery);
        if($result == FALSE){
          echo "<pre>";
          print_r($this->azConn->errorInfo());
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
      $checkResult = $this->azConn->query($checkQuery);

      if($checkResult != FALSE){
        $delQuery = "DELETE FROM dbo.saleorderinfo WHERE 1 = 1";
        $this->azConn->query($delQuery);
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
        $handleBy = $row['handleBy'];
        $preSale = $row['preSale'];

        $insertQuery = "INSERT INTO dbo.saleorderinfo (sid, saleby, custname, saletype, otcamt, mrcamt, request, delivery, billing, billed, comndate, mrc12, handleBy, preSale) VALUES('$sid', '$saleby', '$custname', '$saletype', '$otcamt', '$mrcamt', '$request', '$delivery', '$billing', '$billed', '$comndate', '$mrc12', '$handleBy', '$preSale')";
        echo "<pre>";
        echo $insertQuery;
        echo "</pre>";
        $result = $this->azConn->query($insertQuery);
        if($result == FALSE){
          echo "<pre>";
          print_r($this->azConn->errorInfo());
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
      $checkResult = $this->azConn->query($checkQuery);

      if($checkResult != FALSE){
        $delQuery = "DELETE FROM dbo.personGP WHERE 1 = 1";
        $this->azConn->query($delQuery);
      }

      foreach($result as $row){

        $saleby = $row['saleby'];
        $totalSale = $row['totalSale'];
        $totalLoss = $row['totalLoss'];
        $totalGP = $row['totalGP'];

        $insertQuery = "INSERT INTO dbo.personGP(saleby, saleamt, lostamt, gpamt) VALUES('$saleby', '$totalSale', '$totalLoss', '$totalGP')";
        $result = $this->azConn->query($insertQuery);
        if($result == FALSE){
          echo "<pre>";
          print_r($this->azConn->errorInfo());
          echo "</pre>";
        }
      }

    }

  }

  // public function getTopTenGP(){
  //
  //   $query = "SELECT saleby, SUM(gpamt) as totalGP FROM gporderinfo WHERE billdate >= '2017' GROUP BY saleby ORDER BY totalGP DESC LIMIT 10";
  //   $result = mysqli_query($this->vmConn, $query);
  //   $result = mysqli_fetch_all($result, MYSQLI_ASSOC);
  //
  //   if($result){
  //
  //     $checkQuery = "SELECT * FROM dbo.toptenGP";
  //     $checkResult = $this->azConn->query($checkQuery);
  //
  //     if($checkResult != FALSE){
  //       $delQuery = "DELETE FROM dbo.toptenGP WHERE 1 = 1";
  //       $this->azConn->query($delQuery);
  //     }
  //
  //     foreach($result as $row){
  //
  //       $saleby = $row['saleby'];
  //       $gpamt = $row['totalGP'];
  //
  //       $insertQuery = "INSERT INTO dbo.toptenGP (saleby, gpamt) VALUES('$saleby', '$gpamt')";
  //       $result = $this->azConn->query($insertQuery);
  //       if($result == FALSE){
  //         echo "<pre>";
  //         print_r($this->azConn->errorInfo());
  //         echo "</pre>";
  //       }
  //     }
  //
  //   }
  //
  // }
  //
  // public function getTopTenSale(){
  //
  //   $query = "SELECT saleby, SUM(saleamt) as totalSale FROM gporderinfo WHERE billdate >= '2017' GROUP BY saleby ORDER BY totalSale DESC LIMIT 10";
  //   $result = mysqli_query($this->vmConn, $query);
  //   $result = mysqli_fetch_all($result, MYSQLI_ASSOC);
  //
  //   if($result){
  //
  //     $checkQuery = "SELECT * FROM dbo.toptenSale";
  //     $checkResult = $this->azConn->query($checkQuery);
  //
  //     if($checkResult != FALSE){
  //       $delQuery = "DELETE FROM dbo.toptenSale WHERE 1 = 1";
  //       $this->azConn->query($delQuery);
  //     }
  //
  //     foreach($result as $row){
  //
  //       $saleby = $row['saleby'];
  //       $saleamt = $row['totalSale'];
  //
  //       $insertQuery = "INSERT INTO dbo.toptenSale (saleby, saleamt) VALUES('$saleby', '$saleamt')";
  //       $result = $this->azConn->query($insertQuery);
  //       if($result == FALSE){
  //         echo "<pre>";
  //         print_r($this->azConn->errorInfo());
  //         echo "</pre>";
  //       }
  //     }
  //
  //   }
  // }

  public function insertTicketAll(){

    $query = "SELECT * FROM dbo.ticketInfoAll ORDER BY id DESC";
    $result = $this->azConn->query($query);

    if($result){
      $query = "DELETE FROM dbo.ticketInfoAll WHERE 1 = 1";
      $result = $this->azConn->query($query);
    }

    // if($result == FALSE){
    //   $lastTicketID = 0;
    // }else{
    //   $result = $result->fetch(PDO::FETCH_ASSOC);
    //   $lastTicketID = $result['lastTicketID'];
    //   if($lastTicketID == ''){
    //     $lastTicketID = 0;
    //   }
    // }
    //
    $query = "SELECT * FROM ticketinfoall ORDER BY ticketID DESC";
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
        $result = $this->azConn->query($insertQuery);
        if($result == FALSE){
          echo "<pre>";
          print_r($this->azConn->errorInfo());
          echo "</pre>";
        }

      }

    }

  }

  public function insertInfra(){
    $query = "SELECT * FROM dbo.infrareport ORDER BY id DESC";
    $result = $this->azConn->query($query);

    if($result){
      $query = "DELETE FROM dbo.infrareport WHERE 1 = 1";
      $result = $this->azConn->query($query);
    }

    $query = "SELECT * FROM infrareport ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($this->vmConn, $query);
    $result = mysqli_fetch_array($result);

    if($result){

      $id = $result['id'];
      $total_mux = $result['total_mux'];
      $total_router = $result['total_router'];
      $total_ups = $result['total_ups'];
      $total_switch = $result['total_switch'];
      $total_radio = $result['total_radio'];
      $total_connection = $result['total_connection'];
      $total_customer = $result['total_customer'];
      $total_ticket_open = $result['total_ticket_open'];
      $total_ticket_closed = $result['total_ticket_closed'];
      $total_tickets_today = $result['total_tickets_today'];
      $avg_ticket_time = $result['avg_ticket_time'];
      $total_pop = $result['total_pop'];

      $insertQuery = "INSERT INTO dbo.infrareport (id, total_mux, total_router, total_ups, total_switch, total_radio, total_connection, total_customer, total_ticket_open, total_ticket_closed, total_tickets_today, avg_ticket_time, total_pop, last_update) VALUES ('$id', '$total_mux', '$total_router', '$total_ups', '$total_switch', '$total_radio', '$total_connection', '$total_customer', '$total_ticket_open', '$total_ticket_closed', '$total_tickets_today', '$avg_ticket_time', '$total_pop', CURRENT_TIMESTAMP)";
      $result = $this->azConn->query($insertQuery);
      if($result == FALSE){
        echo "<pre>";
        print_r($this->azConn->errorInfo());
        echo "</pre>";
      }

    }

  }

  public function locEntry(){
    $query = "SELECT * FROM hotspotLoc";
    $result = mysqli_query($this->vmConn, $query);
    $result = mysqli_fetch_all($result, MYSQLI_ASSOC);

    if($result){
      $checkQuery = "SELECT id FROM dbo.hotspotLoc";
      $checkResult = $this->azConn->query($checkQuery);
    }
  }

  public function insertSaleGP(){

    $query = "SELECT * FROM saleandgp WHERE request >= '2017' ORDER BY sid ASC";
    $result = mysqli_query($this->vmConn, $query);
    $selResult = mysqli_fetch_all($result, MYSQLI_ASSOC);

    if($selResult != NULL){

      $checkQuery = "SELECT * FROM dbo.saleandgp";
      $checkResult = $this->azConn->query($checkQuery);

      if($checkResult != FALSE){
        $delQuery = "DELETE FROM dbo.saleandgp WHERE 1 = 1";
        $this->azConn->query($delQuery);
      }

      foreach($selResult as $row){
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
        if($delidate == '0000-00-00'){
          $delidate = NULL;
        }
        $deliby = $row['deliby'];
        $billing = $row['billing'];
        if($billing == '0000-00-00'){
          $billing = NULL;
        }
        $billed = $row['billed'];
        if($billed == '0000-00-00'){
          $billed = NULL;
        }
        $presale = $row['presale'];
        $comndate = $row['comndate'];
        if($comndate == '0000-00-00'){
          $comndate = NULL;
        }

        $insertQuery = "INSERT INTO dbo.saleandgp (sid, saleby, custname, saletype, otcamt, mrcamt, saleamt, costamt, lostamt, gpamt, request, delidate, deliby, billing, billed, presale, comndate) VALUES ('$sid', '$saleby', '$custname', '$saletype', '$otcamt', '$mrcamt', '$saleamt', '$costamt', '$lostamt', '$gpamt', '$request', '$delidate', '$deliby', '$billing', '$billed', '$presale', '$comndate')";
        $insertQuery = str_replace("'NULL'", "NULL", $insertQuery);
        // echo "<pre>";
        // echo $insertQuery;
        // echo "</pre>";
        $result = $this->azConn->query($insertQuery);
        if($result == FALSE){
          echo "<pre>";
          print_r($this->azConn->errorInfo());
          echo "</pre>";
          // die();
        }
      }
    }
  }

  public function testQuery(){

    $query = "SELECT * FROM dbo.apDet";
    $result = $this->azConn->query($query);

    print_r($result->fetch(PDO::FETCH_ASSOC));

  }
}

?>
