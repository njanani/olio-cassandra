<?php
/*
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */ 
        
/**
 * PHP Template.
 * Author: Sheetal Patil. Sun Microsystems, Inc.
 *
 */
session_start();
require_once("../etc/config.php");
require_once('../etc/phpcassa_config.php');

$se = $_REQUEST['socialEventID'];
$comments = $_POST['comments'];
$cid = $_POST['editingcid'];
$editcomments = $_POST['editcomments'];
$tagslist = Tags_Controller::getInstance();
$events = Events_Controller::getInstance();
$username = $_SESSION["uname"];
$dateFormat = "l,  F j,  Y,  h:i A";
$txBegun = false;

$eventTaglist = $tagslist->getEventsPageTagCloud($conn,$se);
$numAttendees = $events->getNumAttendees($se,$conn);
$_SESSION["numofattendees"] = $numAttendees;
$rating = $_SESSION["rating"];

   $column_family = new ColumnFamily($conn,'SOCIALEVENT');
	$result = $column_family->get($se);
	$column_family1 = new ColumnFamily($conn,'ADDRESS');

	$addr_id = $result['ADDRESS_addressid'];
	$addr_rows = $column_family1->get($addr_id);
	$result['street1']=$addr_rows['street1'];
	$result['street2']=$addr_rows['street2'];
	$result['city']=$addr_rows['city'];
	$result['state']=$addr_rows['state'];
	$result['zip']=$addr_rows['zip'];
	$result['country']=$addr_rows['country'];
	$result['latitude']=$addr_rows['latitude'];
	$result['longitude']=$addr_rows['longitude'];


   // see if any rows were returned
   if ($result) {
   	$x = trim($result['latitude']);
      $y = trim($result['longitude']);
      $title =trim($result['title']);
      $description = trim($result['description']);
      $submitter=$result['submitterUserName'];
      $telephone = $result['telephone'];
      $street1 = $result['street1'];
      $street2 = $result['street2'];
      $city = $result['city'];
      $state = $result['state'];
     	$zip = $result['zip'];
      $country = $result['country'];
      $image = $result['imagethumburl'];
	   $eventDateTime = trim(date("Y-m-d h:i:s",$result['eventtimestamp']));
      $eventTimestamp = trim($events->formatdatetime($dateFormat,$eventDateTime));
      $literature =  $result['literatureurl'];
      $summary = $result['summary'];
      $address="".$result['street1']." ".$result['street2'].",".$result['city'].",".$result['state'].",".$result['zip'].",".$result['country'];
   }

unset($result);
$listqueryresult = "";
if (isset($_SESSION["uname"])) {
    // Ensure our user name comes in first, if already attending.

	$sql = new ColumnFamily($conn,'PERSON_SOCIALEVENT');
	$index_exp_event = CassandraUtil::create_index_expression('socialeventid',$se);
	$index_exp_uname = CassandraUtil::create_index_expression('username',$username);
	$index_clause = CassandraUtil::create_index_clause(array($index_exp_event,$index_exp_uname));
	$rows =  $sql->get_indexed_slices($index_clause,$columns=array('socialeventid'));

	foreach($rows as $key => $columns) {
		$index_exp_p = CassandraUtil::create_index_expression('socialeventid',$columns['socialeventid']);
		$index_clause_p = CassandraUtil::create_index_clause(array($index_exp_p));
		$listqueryresult = $sql->get_range($index_clause_p,$row_count=20,$column_count=20);
	}

} else {
	$listquery = new ColumnFamily($conn,'PERSON_SOCIALEVENT');
	$index_exp_event = CassandraUtil::create_index_expression('socialeventid', $se);
	$index_clause = CassandraUtil::create_index_clause(array($index_exp_event));
	$listqueryresult = $listquery->get_range($index_clause,$row_count=20,$column_count=20);
}

foreach ($listqueryresult as $key=>$column) {
		$tmp_uname = $column['username'];
		if (!isset($_SESSION["uname"]) && $tmp_uname == $username) {
		        $unattend = true; // show unattend button if user is already registered.
        }
        $attendeeList = $attendeeList." ".'<a href="users.php?username='.$tmp_uname.'">'.$tmp_uname.'</a><br />';

}
unset($listqueryresult);
if (isset($_POST['commentsratingsubmit'])) {
	$insertSql = new ColumnFamily($conn,'COMMENTS_RATING');
	$commentid =  exec("python /usr/pysnowflakeclient/pysnowflakeclient/__init__.py");
	$insertSql->insert($commentid,array('commentid' => $commentid,'username' => $username,'socialeventid' => $se ,'comments' => $comments,'ratings' => $rating,'created_at' => time()));
} else if (isset($_POST['editcommentsratingsubmit']) && isset($_POST['editingcid'])) {
	$cf = new ColumnFamily($conn,'COMMENTS_RATING');
	$cf->insert($cid,array('comments' => $editcomments,'ratings' => $rating,'updated_at' => time()));
	
}

$commentsrating = new ColumnFamily($conn,'COMMENTS_RATING');
$index_exp = CassandraUtil::create_index_expression('socialeventid', $se);
$index_clause = CassandraUtil::create_index_clause(array($index_exp));
$commentsratingResult = $commentsrating->get_indexed_slices($index_clause);
ob_start();


foreach($commentsratingResult as $key => $row1) {
	$tmp_commentid = $row1['commentid'];
	$tmp_uname = $row1['username'];
	$tmp_uname_comments = $row1['comments'];
	$tmp_uname_rating = $row1['ratings'];
	$tmp_uname_created_at = trim($events->formatdatetime($dateFormat,$row1['created_at'])); 
	$tmp_uname_updated_at = trim($events->formatdatetime($dateFormat,$row1['updated_at']));
	require("../views/commentsRating.php");
}


$eventCommentsRating = ob_get_contents();
ob_end_clean();
ob_start();
require("../views/events.php");
$fillContent = ob_get_clean();
require_once("../views/site.php");

?>

