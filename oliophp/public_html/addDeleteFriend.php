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
$friends = Users_Controller::getInstance();
$person = $_REQUEST['person'];
$friend = $_REQUEST['friend'];
$frnd = $_REQUEST['frnd'];
$user = $_REQUEST['query'];
$flag = $_REQUEST['flag'];

$cf = new ColumnFamily($conn,'PERSON_PERSON');
$index_exp1 = CassandraUtil::create_index_expression('Person_username',$friend);
$index_exp2 = CassandraUtil::create_index_expression('friends_username',$person);

if ($flag == "add"){
   $personid =  exec("python /usr/pysnowflakeclient/pysnowflakeclient/__init__.py");
   $cf->insert($personid,array('id' => $personid,'Person_username' => $friend,'friends_username' => $person ,'is_accepted' => 0));
}else if ($flag == "delete"){
	$index_exp3 = CassandraUtil::create_index_expression('is_accepted',0);
	$index_clause = CassandraUtil::create_index_clause(array($index_exp1,$index_exp2,$index_exp3));
	$result = $cf->get_indexed_slices($index_clause);
	foreach($result as $key => $col) {
   	$cf->remove($col["id"]);
   }
}else if($flag == "frnd"){
	$index_exp3 = CassandraUtil::create_index_expression('is_accepted',1);
	$index_clause = CassandraUtil::create_index_clause(array($index_exp1,$index_exp2,$index_exp3));
	$result = $cf->get_indexed_slices($index_clause);
	foreach($result as $key => $col) {
      $cf->remove($col["id"]);
   }
}
if($flag == "frnd"){
header("Location:friends.php?username=$person&flag=$flag&reqUser=$friend");
}else{
header("Location:findUsers.php?query=$user&flag=$flag&reqUser=$friend");
}
?>
