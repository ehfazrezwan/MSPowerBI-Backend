<?php

  class DBClass{

    private $vmConn, $radiusConn;

    function __construct(){
      $radiusHost = "wifi.aamra.com.bd";
      $radiusUsername = "ehfaz";
      $radiusPassword = "anl123";

      // $vmHost = "localhost";
      $vmHost = "45.64.135.236";

      $vmUser = "phpmyadmin";
      $vmPass = "WErsdfser##";

      $this->vmConn = mysqli_connect($vmHost, $vmUser, $vmPass, 'reportserver');
      $this->radiusConn = mysqli_connect($radiusHost, $radiusUsername, $radiusPassword, 'radius');
      if (mysqli_connect_errno($this->vmConn) || mysqli_connect_errno($this->radiusConn)){
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
      }
    }

    //Function to get last radacct id from Reporting server
    public function getLastID(){

      $query = "SELECT lastID FROM wifireporttotals ORDER BY lastID DESC LIMIT 1";
      $result = mysqli_query($this->vmConn, $query);
      if($result != NULL){
        $result = mysqli_fetch_array($result);
        return $result['lastID'];
      }else{
        return 1;
      }

    }

    public function totalLogins(){
      $lastID = $this->getLastID();

      $query = "SELECT COUNT(radacctid) AS totalUsers FROM radacct WHERE acctstarttime >= '2017' AND radacctid > '$lastID'";
      $result = mysqli_query($this->radiusConn, $query);
      $result = mysqli_fetch_array($result);
      return $result;

    }

    public function totalBWUp(){
      $lastID = $this->getLastID();

      $query = "SELECT (SUM(acctinputoctets)) AS bwUp FROM radacct WHERE acctstarttime >= '2017' AND radacctid > '$lastID'";
      $result = mysqli_query($this->radiusConn, $query);
      $result = mysqli_fetch_array($result);
      return $result;
    }

    public function totalBWDown(){
      $lastID = $this->getLastID();

      $query = "SELECT (SUM(acctoutputoctets)) AS bwDown, MAX(radacctid) AS maxID FROM radacct WHERE acctstarttime >= '2017' AND radacctid > '$lastID' ORDER BY radacctid DESC";
      $result = mysqli_query($this->radiusConn, $query);
      $result = mysqli_fetch_array($result);
      return $result;
    }

    public function activeSessions(){
      // date_default_timezone_set("Asia/Dhaka");
      // $date = date("Y-m-d H:i:s", strtotime('-3 hours', time()));

      $query = "SELECT hour(acctstarttime) as hourOfDay, COUNT(username) as userLogins FROM `radacct` WHERE acctstarttime >= CURDATE() AND acctstarttime >= hour(CURRENT_TIMESTAMP) GROUP BY hour(acctstarttime) ORDER BY hourOfDay DESC LIMIT 1";
      $result = mysqli_query($this->radiusConn, $query);
      $result = mysqli_fetch_array($result);
      return $result;
    }

    public function insertReportTotal(){

      $totalLogins = $this->totalLogins();
      $totalLogins = $totalLogins['totalUsers'];

      $totalBWUp = $this->totalBWUp();
      $totalBWUp = $totalBWUp['bwUp'];

      $totalBWDown = $this->totalBWDown();
      $maxID = $totalBWDown['maxID'];
      $totalBWDown = $totalBWDown['bwDown'];
      // echo $maxID;

      $activeSessions = $this->activeSessions();
      $activeSessions = $activeSessions['userLogins'];

      $totalBW = $totalBWUp + $totalBWDown;

      $query = "SELECT * FROM wifireporttotals ORDER BY id DESC LIMIT 1";
      $result = mysqli_query($this->vmConn, $query);
      $result = mysqli_fetch_all($result, MYSQLI_ASSOC);
      // $dailyNew = $this->getDailyNew();
      // $dailyNew = 500;
      if($result != NULL){
        // $totalUp = ($totalBWUp / (1024 * 1024 * 1024 * 1024)) + ($result[0]['totalUp']);
        $totalUp = ($totalBWUp) + ($result[0]['totalUp']);
        // $totalDown = ($totalBWDown / (1024 * 1024 * 1024 * 1024)) + ($result[0]['totalDown']);
        $totalDown = ($totalBWDown) + ($result[0]['totalDown']);
        $tLogin = $totalLogins + ($result[0]['totalLogin']);
        $totalBW = $totalUp + $totalDown;
      }else{
        // $totalUp = $totalBWUp / (1024 * 1024 * 1024 * 1024);
        $totalUp = $totalBWUp;
        // $totalDown = $totalBWDown / (1024 * 1024 * 1024 * 1024);
        $totalDown = $totalBWDown;
        $tLogin = $totalLogins;
        $totalBW = $totalUp + $totalDown;
      }

      $updateQuery = "INSERT INTO wifireporttotals (totalUp, totalDown, totalBW, totalLogin, activeSessions, lastID, newDailyUsers) VALUES('$totalUp', '$totalDown', '$totalBW', '$tLogin', '$activeSessions', '$maxID', '0')";
      $result = mysqli_query($this->vmConn, $updateQuery);
    }

    public function getGraphData(){
      $query = "SELECT hour(acctstarttime) as hourOfDay, COUNT(username) as userLogins, (SUM(acctinputoctets + acctoutputoctets)) as BWConsumed, (SUM(acctinputoctets)) as dataUp, (SUM(acctoutputoctets)) as dataDown FROM `radacct` WHERE acctstarttime >= CURDATE() GROUP BY hour(acctstarttime)";
      $result = mysqli_query($this->radiusConn, $query);
      $result = mysqli_fetch_all($result, MYSQLI_ASSOC);
      // echo "<pre>";
      // print_r($result);
      // echo "</pre>";

      return $result;
    }

    public function insertGraphData(){
      $gData = $this->getGraphData();

      $query = "SELECT * FROM graphdata";
      $result = mysqli_query($this->vmConn, $query);
      $result = mysqli_fetch_row($result);

      if($result){
        $delQuery = "DELETE FROM graphdata WHERE 1";
        $result = mysqli_query($this->vmConn, $delQuery);
      }

      foreach($gData as $row){
        $hour = $row['hourOfDay'];
        $user = $row['userLogins'];
        $totalBW = $row['BWConsumed'];
        $dataUp = $row['dataUp'];
        $dataDown = $row['dataDown'];
        $insertQuery = "INSERT INTO graphdata (hourOfDay, userLogins, dataUsage, dataUp, dataDown) VALUES('$hour', '$user', '$totalBW', '$dataUp', '$dataDown')";
        $result = mysqli_query($this->vmConn, $insertQuery);

        if(!$result){
          echo "<pre>";
          echo mysqli_error($this->vmConn);
          echo "</pre>";
        }
      }
    }

    public function parseTicket($tid = NULL){

      $url = 'http://ticket.aamranetworks.com/api_cableman/getBiAllTicketReport1?apikey=RhIZpWN5SGe8LtThgbxMpD4tMHa1wg9qBaeSWklArGdT3uXBa7C2VZqwi2EyLf49yGLRjVebkK5YEFFuEoJll9e1cl&tid='.$tid;
      $ch = curl_init();
      curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url
      ));

      $response = curl_exec($ch);
      curl_close($ch);

      $decoded = json_decode($response);

      return $decoded;
    }

    public function newTicketParse(){
      // echo "<pre>";
      // print_r($this->parseTicket(34657));
      // echo "</pre>";

      $query = "SELECT SUBSTRING(ticketID, 5, 5) as newTickID FROM ticketinfoall ORDER BY ticketID DESC LIMIT 1";
      $result = mysqli_query($this->vmConn, $query);
      $result = mysqli_fetch_array($result);

      // echo $result['ticketID'];
      if($result){
        $parsed = $this->parseTicket($result['newTickID']);
        // echo $result['ticketID'];
      }else{
        $parsed = $this->parseTicket(0);
        // echo 0;
      }

      foreach($parsed->data as $row){
        $ticketID = $row->id;
        $subject = $row->subject;
        $subject = str_replace("'", "''", $subject);
        $created = $row->created;
        // $created = date('Y-m-d H:i:s', strtotime($created));
        $createdBy = $row->created_by;
        $category = $row->category;
        $subcategory = $row->sub_category;
        $linkID = $row->link_id;
        $companyName = $row->company_name;
        $owner = $row->owner;
        $owner = str_replace("'", "''", $owner);
        $dept = $row->department;
        $status = $row->status;
        $priority = $row->priority;

        $insertQuery = "INSERT INTO ticketinfoall (ticketID, subject, created, createdBy, category, subCategory, linkID, companyName, owner, dept, status, priority) VALUES ('$ticketID', '$subject', '$created', '$createdBy', '$category', '$subcategory', '$linkID', '$companyName', '$owner', '$dept', '$status', '$priority')";
        $result = mysqli_query($this->vmConn, $insertQuery);

        if(!$result){
          echo "<pre>";
          echo mysqli_error($this->vmConn);
          echo "</pre>";
        }
      }

    }

    public function parseInfra(){
      $url = 'http://infra.aamranetworks.net/api/powerBIApi.php?api_key=dChUYpsmokHGTZ0i';
      $ch = curl_init();
      curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url
      ));

      $response = curl_exec($ch);
      curl_close($ch);

      $decoded = json_decode($response, true);

      return $decoded;
    }

    public function insertInfra(){

      $infraDetails = $this->parseInfra();

      $infraDetails = $infraDetails['data'];
      $total_pop = $infraDetails['total_pop'];
      $total_mux = $infraDetails['total_mux'];
      $total_router = $infraDetails['total_router'];
      $total_ups = $infraDetails['total_ups'];
      $total_switch = $infraDetails['total_switch'];
      $total_radio = $infraDetails['total_radio'];
      $total_connection = $infraDetails['total_connection'];
      $total_customer = $infraDetails['total_customer'];
      $total_ticket_open = $infraDetails['total_ticekt_open'];
      $total_ticket_closed = $infraDetails['total_ticekt_closed'];
      $total_tickets_today = $infraDetails['total_ticekts_today'];
      $avg_ticket_time = $infraDetails['avg_ticket_time'];

      $query = "INSERT INTO infrareport (total_pop, total_mux, total_router, total_ups, total_switch, total_radio, total_connection, total_customer, total_ticket_open, total_ticket_closed, total_tickets_today, avg_ticket_time) VALUES('$total_pop', '$total_mux', '$total_router', '$total_ups', '$total_switch', '$total_radio', '$total_connection', '$total_customer', '$total_ticket_open', '$total_ticket_closed', '$total_tickets_today', '$avg_ticket_time')";
      $result = mysqli_query($this->vmConn, $query);

      if(!$result){
        echo "<pre>";
        echo mysqli_error($this->vmConn);
        echo "</pre>";
      }
    }

  }

 ?>
