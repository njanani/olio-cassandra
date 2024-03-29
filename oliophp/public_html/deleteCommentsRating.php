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

$events = Events_Controller::getInstance();
$dateFormat = "l,  F j,  Y,  h:i A";
$commentid = $_REQUEST['commentid'];
$se= $_REQUEST['socialEventID'];

$cf = new ColumnFamily($conn,'COMMENTS_RATING');
$cf->remove($commentid);

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

unset($commentsratingResult);
$eventCommentsRating = ob_get_contents();
ob_end_clean();    

echo $eventCommentsRating;

?>
