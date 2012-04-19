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
 */
session_start();
require_once("../etc/config.php");
require_once('../etc/phpcassa_config.php');
$se = $_REQUEST['id'];
$username = $_SESSION["uname"];
$txBegun = false;
if (!is_null($username)) {
    $connection = DBConnection::getWriteInstance();
    $connection->beginTransaction();
    $txBegun = true;
//    $checkuserIfAttending = "select count(username) as count from PERSON_SOCIALEVENT where socialeventid = '$se' and username = '$username'";
//    $result = $connection->query($checkuserIfAttending);
//    $row = $result->getArray();
	$checkuserIfAttending = new ColumnFamily($conn,'PERSON_SOCIALEVENT');
	$index_exp_event = CassandraUtil::create_index_expression('socialeventid', $se);
	$index_exp_uname = CassandraUtil::create_index_expression('username', $username);
	$index_clause = CassandraUtil::create_index_clause(array($index_exp_event,$index_exp_uname));
	$result = $checkuserIfAttending->get_indexed_slices($index_clause);
	$row['count']=0;
	foreach ($result as $key => $column) {
		$row['count']+=1;
	}
    $userExists = $row['count'];
    if ($userExists <= 0) {
/*        $insertuser = "insert into PERSON_SOCIALEVENT values('$username','$se')";
        $connection->exec($insertuser);
*/
		$insertuser = new ColumnFamily($conn,'PERSON_SOCIALEVENT');
		$id = exec("python /usr/pysnowflakeclient/pysnowflakeclient/__init__.py");
		$insertuser->insert($id,array('id' => $id,'username' => $username,'socialeventid' => $se));
    }
}

if (!isset($connection)) { // If connection not there, we're read-only.
    $connection = DBConnection::getInstance();
}
/*
$listquery = "select username from PERSON_SOCIALEVENT ".
             "where socialeventid = '$se' and username = '$username' ".
             "union select username from PERSON_SOCIALEVENT ".
             "where socialeventid = '$se' limit 20";
$listqueryresult = $connection->query($listquery);
*/
$listquery = new ColumnFamily($conn,'PERSON_SOCIALEVENT');
$index_exp_event = CassandraUtil::create_index_expression('socialeventid', $se);
$index_exp_uname = CassandraUtil::create_index_expression('username', $username);
$index_clause = CassandraUtil::create_index_clause(array($index_exp_event,$index_exp_uname));
$result = $listquery->get_indexed_slices($index_clause);
$count = 0;
foreach ($result as $key => $column) {
    $count+=1;
}
$username = $_SESSION["uname"];
if($count > 0)
{
	$index_clause_list = CassandraUtil::create_index_clause(array($index_exp_event));
	$listqueryresult = $listquery->get_indexed_slices($index_clause_list);
	
	foreach ($listqueryresult as $key => $column) {
		$tmp_uname = $column['username'];
	        $attendeeList = $attendeeList." ".'<a href="users.php?username='.$tmp_uname.'">'.$tmp_uname.'</a><br />';
	}
}
        

unset($listqueryresult);
if ($txBegun) {
    $connection->commit();
}

$numofattendees = $_SESSION["numofattendees"] + 1;
$_SESSION["numofattendees"] = $numofattendees;
echo '<h2 class="smaller_heading">'.$numofattendees.' Attendees:</h2><br/><input name="unattend" type="button" value="Unattend" onclick="deleteAttendee();"/><br/><div id="attendees">'.$attendeeList.'</div>';
?>
