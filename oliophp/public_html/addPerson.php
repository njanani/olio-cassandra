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
 * This page is to collect the information of a new user. 
 * Once the user submits the page, user gets registered and will be directed to userAdded.php page.
 *
*/
session_start();
require_once("../etc/config.php");
require_once('../etc/phpcassa_config.php');


$uname = $_REQUEST['username'];
if(!is_null($uname)){

$column_family = new ColumnFamily($conn,'PERSON');
$index_exp = CassandraUtil::create_index_expression('username', $uname);
$index_clause = CassandraUtil::create_index_clause(array($index_exp));
$rows = $column_family->get_indexed_slices($index_clause);
$column_family1 = new ColumnFamily($conn,'ADDRESS');

foreach($rows as $key => $row) {
	
	$addr_rows = $column_family1->get($row['ADDRESS_addressid']);
	$row['street1']=$addr_rows['street1'];
	$row['street2']=$addr_rows['street2'];
	$row['city']=$addr_rows['city'];
	$row['state']=$addr_rows['state'];
	$row['zip']=$addr_rows['zip'];
	$row['country']=$addr_rows['country'];
}

$un = $row['username'];
$pwd= $row['password'];
$fn = $row['firstname'];
$ln = $row['lastname'];
$email = $row['email'];
$tele = $row['telephone'];
$img = $row['imageurl'];
$summary = $row['summary'];
$tz = $row['timezone'];
$street1 = $row['street1'];
$street2 = $row['street2'];
$city = $row['city'];
$state = $row['state'];
$zip = $row['zip'];
$country = $row['country'];
unset($result);
}

if(!is_null($uname) && ( is_null($_SESSION["uname"]) || !($_SESSION["uname"]==$uname) ) ){
$fillMessage = "<font color=red>Failed to edit user.</font>";
}else{
ob_start();
require("../views/addPerson.php");
$fillContent = ob_get_clean();
}
require_once("../views/site.php"); 
?>

