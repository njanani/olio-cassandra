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
$connection = DBConnection::getInstance();
$un = $_SESSION["uname"];
$events = Events_Controller::getInstance();
$flag = true;
$url = RequestUrl::getInstance();
$page= $_REQUEST['page'];
$href = $url->getGetRequest();

if(!is_null($page)){
  $href = substr($href, 0, strrpos($href,"&"));
}
if($href==""){
  $href = "?";
}

//Start Pagination
if(!is_null($page)){
    $numPages  =$_SESSION["numPages"];
    $_SESSION["currentpage"]=$page;
    $curr_page = $_SESSION["currentpage"];
    $prev_page = $_SESSION["currentpage"] - 1;
    $next_page = $_SESSION["currentpage"] + 1;
    $offset = ($page * 10) - 10;
    if($offset < 0) {
    $offset = 0;
    }
    if($prev_page < 0) {
    $prev_page = 1;
    }
    if($next_page >  $numPages) {
    $next_page = $numPages;
    }
}else{
/*    $query = "select count(*) as count From SOCIALEVENT as se,PERSON_SOCIALEVENT as ps where se.socialeventid=ps.socialeventid and se.eventtimestamp>=CURRENT_TIMESTAMP and ps.username='$un'";
    $result = $connection->query($query);
    $row = $result->getArray();
*/	
	$row['count'] = 0;
	$sql = new ColumnFamily($conn,'PERSON_SOCIALEVENT');
	$index_exp_uname = CassandraUtil::create_index_expression('username',$un);
	$index_clause = CassandraUtil::create_index_clause(array($index_exp_uname));
	$rows =  $sql->get_indexed_slices($index_clause,$columns=array('socialeventid'));
	$query = new ColumnFamily($conn,'SOCIALEVENT');

	foreach($rows as $key => $columns) {
		$index_exp1 = CassandraUtil::create_index_expression('socialeventid', $columns['socialeventid'],cassandra_IndexOperator::EQ);
		$index_exp2 = CassandraUtil::create_index_expression('eventtimestamp',time(),cassandra_IndexOperator::GTE);
		$index_clause1 = CassandraUtil::create_index_clause(array($index_exp1,$index_exp2));
		$result =  $query->get_indexed_slices($index_clause1);
		foreach($result as $key1=>$value1)
			$row['count'] +=1;
	}

    $count = $row['count'];
    unset($result);
    $numPages  = ceil($count / 10);;
    $_SESSION["numPages"] = $numPages;
    $prev_page = 1;
    $next_page = 2;
    $curr_page = 1;
    $offset = 0;
    session_unregister ("currentpage");
}    
ob_start();
require("../views/paginate.php");
$paginateView = ob_get_clean();
//End Pagination

$upcomingEvents = $events->getUpcomingEventsForUser($un,$conn,$flag,$offset);
ob_start();
require("../views/upcomingEvents.php");
$fillContent = ob_get_clean();
require_once("../views/site.php");
?>

