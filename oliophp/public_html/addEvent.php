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
        
/* 
 * PHP Template.
 * Author: Sheetal Patil. Sun Microsystems, Inc.
 *
*/
session_start();
require_once("../etc/config.php");
require_once('../etc/phpcassa_config.php');
$se = $_REQUEST['socialEventID'];
$_SESSION["addEventSE"]=$se;
$connection = DBConnection::getInstance();
if(!is_null($se)){
/*    $q = "select title,description,summary,imageurl,literatureurl,telephone,timezone,eventtimestamp,submitterusername,street1,street2,city,state,zip,country from SOCIALEVENT as s,ADDRESS as a where s.socialeventid='$se' and s.ADDRESS_addressid=a.addressid";
    $result = $connection->query($q);
    $row = $result->getArray();
*/

	$column_family = new ColumnFamily($conn,'SOCIALEVENT');
	$row = $column_family->get($se);
	$column_family1 = new ColumnFamily($conn,'ADDRESS');

	$addr_rows = $column_family1->get($row['ADDRESS_addressid']);
	$row['street1']=$addr_rows['street1'];
	$row['street2']=$addr_rows['street2'];
	$row['city']=$addr_rows['city'];
	$row['state']=$addr_rows['state'];
	$row['zip']=$addr_rows['zip'];
	$row['country']=$addr_rows['country'];

    $title = $row['title'];
    $description = $row['description'];
    $summary = $row['summary'];
    $image=$row['imageurl'];
    $literature=$row['literatureurl'];
    $telephone=$row['telephone'];
    $tz=$row['timezone'];
    $submitter=$row['submitterusername'];
    $eventdate=$row['eventtimestamp'];
    $year = substr($eventdate,0,4);
    $month = substr($eventdate,5,2);
    $day = substr($eventdate,8,2);
    $hour = substr($eventdate,11,2);
    $minute = substr($eventdate,14,2);
    $street1=$row['street1'];
    $street2=$row['street2'];
    $city=$row['city'];
    $state=$row['state'];
    $zip=$row['zip'];
    $country=$row['country'];
    unset($result);
//    $q1="select tag from SOCIALEVENTTAG as st, SOCIALEVENTTAG_SOCIALEVENT as sst where sst.socialeventid='$se' and sst.socialeventtagid=st.socialeventtagid order by tag ASC";
//    $result1 = $connection->query($q1);

    $sql = new ColumnFamily($conn,'SOCIALEVENTTAG_SOCIALEVENT');
    $index_exp = CassandraUtil::create_index_expression('socialeventid',$se );
    $index_clause = CassandraUtil::create_index_clause(array($index_exp));
    $result1 =  $sql->get_indexed_slices($index_clause,$columns=array('socialeventtagid'));
    $q1 = new ColumnFamily($conn,'SOCIALEVENTTAG');
    foreach($result1 as $key1 => $col1) {
	$row = $q1->get($col1['socialeventtagid']);
	$tg = $row['tag'];
        $tags = $tags." ".$tg;
    }

    unset($result1);
}
if(!is_null($se) && (is_null($_SESSION["uname"]) || !($_SESSION["uname"]==$submitter) )){
    $fillMessage = "<font color=red>You can only edit events you created.</font> ";
}else{
    ob_start();
    require("../views/addEvent.php");
    $fillContent = ob_get_clean();
}
require_once("../views/site.php");
?>
