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
$username= $_REQUEST['username'];
$connection = DBConnection::getInstance();
$events = Events_Controller::getInstance();
$url = RequestUrl::getInstance();
$page= $_REQUEST['page'];
$flag = true;
$href = $url->getGetRequest();
if(!is_null($page)){
  $href = substr($href, 0, strrpos($href,"&"));
}
echo "sadd";
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
/*    $query = "select count(*) as count from SOCIALEVENT where submitterusername='$username' and createdtimestamp<=now() and eventtimestamp>=CURRENT_TIMESTAMP";
    $result = $connection->query($query);
    $row = $result->getArray();
*/	
	$row['count'] = 0;
	$query = new ColumnFamily($conn,'SOCIALEVENT');
	$index_exp1 = CassandraUtil::create_index_expression('submitterUserName',$username,cassandra_IndexOperator::EQ);
	$index_exp2 = CassandraUtil::create_index_expression('createdtimestamp',time(),cassandra_IndexOperator::LTE);
	$index_exp3 = CassandraUtil::create_index_expression('eventtimestamp',time(),cassandra_IndexOperator::GTE);
	$index_clause = CassandraUtil::create_index_clause(array($index_exp1,$index_exp2,$index_exp3));
	$result =  $query->get_indexed_slices($index_clause);

	foreach($result as $key=>$value)
	{
		$row['count']+=1;			
	}
    $count = $row['count'];
echo "asa";
echo $count;
    unset($result);
    $numPages  = ceil($count / 10);
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

$postedEvents = $events->getRecentlyPostedEventsOfUser($username,$conn,$flag,$offset);
ob_start();
require("../views/postedEvents.php");
$fillContent = ob_get_clean();
require_once("../views/site.php");
?>
