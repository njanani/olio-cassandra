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
 * 
 * This is user controller which performs user authentication.
 */

class Users_Controller {

   function authenticate($un,$pwd,$connection){
//       $auth_sql = "select * from PERSON where username='$un' and password='$pwd'";

	$auth_sql = new ColumnFamily($connection,'PERSON');
	$index_exp = CassandraUtil::create_index_expression('username',$un );
	$index_clause = CassandraUtil::create_index_clause(array($index_exp));
	$rows =  $auth_sql->get_indexed_slices($index_clause);

	foreach($rows as $key => $columns) {
		if($columns['password']==$pwd)
			return $columns;
		else
			return NULL;
	}
   }
   
   function findUser($user,$loggedinuser,$connection){
//        $sql = "select username, firstname, lastname, imagethumburl  from PERSON where username like '$user%'";
//        $result = $connection->query($sql);
	$column_family = new ColumnFamily($connection,'PERSON');
	$result = $column_family->get_range($key_start=$user,$key_finish="",$column_count = 5);
        $rowsFound = false;
        $count = 0;
        ob_start();
          
	  foreach($result as $key=> $row){
          $rowsFound = true;
          if($count%2 == 0){
                $class = "even";
          }else{
                $class = "odd";
          }
          $username = $row['username'];
         $userInFriendsList = $this->userInFriendsList($loggedinuser,$username,$connection);
         $invited = $this->ifInvited($loggedinuser,$username,$connection);
          $firstname = $row['firstname'];
          $lastname = $row['lastname'];
          $image = $row['imagethumburl'];
          require("../views/findUsersList.php");
          $count++;
       }
       unset($result);
       $users=ob_get_contents();
       ob_end_clean();
       if ($rowsFound == true) {
          return $users;
       }else{
          return '<em class="subliminal">No users match your query.</em>';
       }
   }
   
/*****
   function getFriendCloud($username,$connection){
        $sql = "select firstname, lastname, friends_username from PERSON as p, PERSON_PERSON as pp where pp.person_username='$username' and p.username=pp.friends_username and pp.is_accepted=1";
        $result = $connection->query($sql);
        $rowsFound = false;
        while($row = $result->getArray()) {
            $rowsFound = true;
            $friend = $row['friends_username'];
            $fn = $row['firstname'];
            $ln = $row['lastname'];
            $friendCloud = $friendCloud." ".'<a href="users.php?username='.$friend.'">'.$fn.' '.$ln.'</a>';
        }
        unset($result);
        if ($rowsFound == true) {
          return $friendCloud;
        }else{
          return 'This person has no friends.';
        }
   }
****/
   
   function getFriendCloud($username,$connection){
//        $sql = "select firstname, lastname, friends_username, imagethumburl from PERSON as p, PERSON_PERSON as pp where pp.person_username='$username' and p.username=pp.friends_username and pp.is_accepted=1";
//        $result = $connection->query($sql);
	$sql = new ColumnFamily($connection,'PERSON_PERSON');
	$index_exp_frnd = CassandraUtil::create_index_expression('Person_username',$username );
	$index_exp_acpt = CassandraUtil::create_index_expression('is_accepted',1);
	$index_clause = CassandraUtil::create_index_clause(array($index_exp_frnd,$index_exp_acpt));
	$rows =  $sql->get_indexed_slices($index_clause,$columns=array('friends_username'));
        
	$rowsFound = false;
        $count = 0;
        $space = "&nbsp;";
	foreach($rows as $key => $columns) {
	    $person_sql = new ColumnFamily($connection,'PERSON');
	    $index_exp_p = CassandraUtil::create_index_expression('username',$columns['friends_username']);
	    $index_clause_p = CassandraUtil::create_index_clause(array($index_exp_p));
            $result = $person_sql->get_indexed_slices($index_clause_p,$columns=array('firstname', 'lastname', 'username', 'imagethumburl'));

            foreach($result as $resultkey => $row) {
		    $count = $count + 1;
		    $rowsFound = true;
		    $friend = $row['username'];
		    $fn = $row['firstname'];
		    $ln = $row['lastname'];
		    $image = $row['imagethumburl'];
		    $friendCloud = $friendCloud." ".'<div class="friend_cloud_item"> <a href="users.php?username='.$friend.'" style="display: block;"> <img src="fileService.php?cache=false&file='.$image.'" height=50px width=50px /> </a><br /> <a href="users.php?username='.$friend.'">'.$fn.' '.$ln.' </a> '.$space.' </div>';
		    if($count >= 6){
			break;
		    }
	    }
		if($count >= 6){
			break;
		    }
        }
        unset($result);
        if ($rowsFound == true) {
	  if ($count >= 6){
          	return $friendCloud."<br/ ". '<a href="friends.php?username='.$username.'">more...</a>';
	  }else{
		return $friendCloud;
	  }
        }else{
	
          return 'This person has no friends'.$rowsFound;
        }
   }
   
   
   function getFriendsList($usernm,$loggedinuser,$connection,$offset){
 //       $sql = "select firstname, lastname, friends_username as username, imagethumburl from PERSON as p, PERSON_PERSON as pp where pp.person_username='$usernm' and p.username=pp.friends_username and pp.is_accepted=1 limit $offset,10";
//        $result = $connection->query($sql);
	$sql = new ColumnFamily($connection,'PERSON_PERSON');
	$index_exp_frnd = CassandraUtil::create_index_expression('Person_username',$usernm );
	$index_exp_acpt = CassandraUtil::create_index_expression('is_accepted',1);
	$index_clause = CassandraUtil::create_index_clause(array($index_exp_frnd,$index_exp_acpt));
	$sqlresult =  $sql->get_indexed_slices($index_clause,$columns=array('friends_username'));

        $rowsFound = false;
        ob_start();
	foreach($sqlresult as $key => $columns) {
		$person_sql = new ColumnFamily($connection,'PERSON');
		$index_exp_p = CassandraUtil::create_index_expression('username',$columns['friends_username']);
		$index_clause_p = CassandraUtil::create_index_clause(array($index_exp_p));
		$result = $person_sql->get_indexed_slices($index_clause_p,$columns=array('firstname', 'lastname', 'username', 'imagethumburl'));
		foreach($result as $resultkey => $row) {
		    $rowsFound = true;
		    $username = $row['username'];
		    $firstname = $row['firstname'];
		    $lastname = $row['lastname'];
		    $image = $row['imagethumburl'];
		    require("../views/friendsList.php");
		}
        }
        unset($result);
        $friends = ob_get_contents();
        ob_end_clean();
        if ($rowsFound == true) {
          return $friends;
        }else{
          return 'This person has no friends.';
        }
   }

   function numFriendshipRequests($un,$connection) {
	
		$sql = new ColumnFamily($connection,'PERSON_PERSON');
		$index_exp_frnd = CassandraUtil::create_index_expression('Person_username',$un );
		$index_exp_acpt = CassandraUtil::create_index_expression('is_accepted',0);
		$index_clause = CassandraUtil::create_index_clause(array($index_exp_frnd,$index_exp_acpt));
		$rows =  $sql->get_indexed_slices($index_clause,$columns=array('friends_username'));
		$numRequests=0;

		foreach($rows as $key => $columns) {
			array_push($result,$columns['friends_username']);
			$numRequests+=1;
		}
		unset($result);
		return $numRequests;
   }
   
   function userInFriendsList($user,$friend,$connection){
 //       $sql = "select friends_username from PERSON_PERSON where person_username='$friend' and friends_username='$user' and is_accepted=1";
 //       $result = $connection->query($sql);
	$sql = new ColumnFamily($connection,'PERSON_PERSON');
	$index_exp_frnd = CassandraUtil::create_index_expression('Person_username',$friend );
	$index_exp_usr = CassandraUtil::create_index_expression('friends_username',$user );
	$index_exp_acpt = CassandraUtil::create_index_expression('is_accepted',1);
	$index_clause = CassandraUtil::create_index_clause(array($index_exp_frnd,$index_exp_usr,$index_exp_acpt));
	$rows =  $sql->get_indexed_slices($index_clause,$columns=array('friends_username'));

        $found = 0;
	foreach($rows as $key => $column) {
		$found = 1;
		break;
	}

        unset($result);     
 	return $found; 
   }

   function ifInvited($user,$friend,$connection) {
  //      $sql = "select friends_username from PERSON_PERSON where person_username='$friend' and friends_username='$user' and is_accepted=0";
  //      $result = $connection->query($sql);
	$sql = new ColumnFamily($connection,'PERSON_PERSON');
	$index_exp_frnd = CassandraUtil::create_index_expression('Person_username',$friend );
	$index_exp_usr = CassandraUtil::create_index_expression('friends_username',$user );
	$index_exp_acpt = CassandraUtil::create_index_expression('is_accepted',0);
	$index_clause = CassandraUtil::create_index_clause(array($index_exp_frnd,$index_exp_usr,$index_exp_acpt));
	$rows =  $sql->get_indexed_slices($index_clause,$columns=array('friends_username'));
	$invited = 0;
        foreach($rows as $key => $columns){
		$invited = 1;
		break;
	}
	 unset($result);
	 return $invited;
   }
  
   function incomingRequests($username,$connection) {
		ob_start();
		$isql = new ColumnFamily($connection,'PERSON_PERSON');
		$index_exp_frnd = CassandraUtil::create_index_expression('Person_username',$username );
		$index_exp_acpt = CassandraUtil::create_index_expression('is_accepted',0);
		$index_clause = CassandraUtil::create_index_clause(array($index_exp_frnd,$index_exp_acpt));
		$rows =  $isql->get_indexed_slices($index_clause,$columns=array('friends_username'));

		foreach($rows as $key => $col) {
			$person_sql = new ColumnFamily($connection,'PERSON');
			$index_exp_p = CassandraUtil::create_index_expression('username',$col['friends_username']);
			$index_clause_p = CassandraUtil::create_index_clause(array($index_exp_p));
			$result1 = $person_sql->get_indexed_slices($index_clause_p,$columns=array('firstname', 'lastname', 'username'));

			foreach($result1 as $resultkey => $row1) {
				$personun1 = $username;
			   $friendun1 = $row1['username'];
   		   $fn1 = $row1['firstname'];
		      $ln1 = $row1['lastname'];
		      require("../views/incomingRequests.php");
			}
		}

		unset($result1);
		$incomingRequests = ob_get_contents();
		ob_end_clean();
		return $incomingRequests;
   }

   function outgoingRequests($username,$connection) {
		ob_start();
		$isql = new ColumnFamily($connection,'PERSON_PERSON');
		$index_exp_frnd = CassandraUtil::create_index_expression('friends_username',$username );
		$index_exp_acpt = CassandraUtil::create_index_expression('is_accepted',0);
		$index_clause = CassandraUtil::create_index_clause(array($index_exp_frnd,$index_exp_acpt));
		$rows =  $isql->get_indexed_slices($index_clause,$columns=array('Person_username'));

		foreach($rows as $key => $col) {
			$person_sql = new ColumnFamily($connection,'PERSON');
			$index_exp_p = CassandraUtil::create_index_expression('username',$col['Person_username']);
			$index_clause_p = CassandraUtil::create_index_clause(array($index_exp_p));
			$result1 = $person_sql->get_indexed_slices($index_clause_p,$columns=array('firstname', 'lastname', 'username'));

			foreach($result1 as $resultkey => $row1) {
         	$personun = $row1['username'];
			   $friendun = $username;
			   $fn = $row1['firstname'];
			   $ln = $row1['lastname'];
			   require("../views/outgoingRequests.php");
			}
		}
		unset($result1);
		$outgoingRequests = ob_get_contents();
		ob_end_clean();
		return $outgoingRequests;
   }   

   static function getInstance() {
        $instance = new Users_Controller();
        return $instance;
   }
 
   
}   
?>
