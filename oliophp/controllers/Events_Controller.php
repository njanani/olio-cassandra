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
 * This is events controller which computes the top events list on the home page.
 * Computes events for the selected calendar date.
 * Provides event details for a particular event.
 */
class Events_Controller {
	

    function formatdatetime($syntax,$DateTime) {
            $year = substr($DateTime,0,4);
            $month = substr($DateTime,5,2);
            $day = substr($DateTime,8,2);
            $hour = substr($DateTime,11,2);
            $min = substr($DateTime,14,2);
            $sec = substr($DateTime,17,2);
            return date($syntax, mktime($hour,$min,$sec,$month,$day,$year));
    }	

    function getHomePageEvents($zipcode,$order,$offset,$connection){

            if(is_null($zipcode) || is_null($order) ) {
//              $query = "select socialeventid,title,summary,imagethumburl,createdtimestamp,eventdate,submitterusername From SOCIALEVENT where  eventtimestamp>=CURRENT_TIMESTAMP ORDER BY eventdate ASC limit $offset,10";
		$query = new ColumnFamily($connection,'SOCIALEVENT');
		$index_exp_dummy = CassandraUtil::create_index_expression('disabled',0,cassandra_IndexOperator::EQ);
		$index_exp = CassandraUtil::create_index_expression('eventtimestamp',time(),cassandra_IndexOperator::GTE);
		$index_clause = CassandraUtil::create_index_clause(array($index_exp_dummy,$index_exp),$offset,10);
		$result2 =  $query->get_indexed_slices($index_clause);


            }else if(!is_null($zipcode)  && !is_null($order) && $order == "created_at"){
//              $query = "select socialeventid,title,summary,imagethumburl,createdtimestamp,eventdate,submitterusername From SOCIALEVENT as se,ADDRESS as a where se.eventtimestamp>=CURRENT_TIMESTAMP and se.ADDRESS_addressid=a.addressid and a.zip='$zipcode' ORDER BY se.createdtimestamp DESC limit $offset,10";

		$sql = new ColumnFamily($connection,'SOCIALEVENT');
		$index_exp_dummy = CassandraUtil::create_index_expression('disabled',0,cassandra_IndexOperator::EQ);
		$index_exp = CassandraUtil::create_index_expression('eventtimestamp',time(),cassandra_IndexOperator::GTE);
		$index_clause = CassandraUtil::create_index_clause(array($index_exp_dummy,$index_exp));
		$result =  $sql->get_indexed_slices($index_clause);
		$query = new ColumnFamily($connection,'ADDRESS');

		foreach($result as $key=>$value)
		{
			$index_exp1 = CassandraUtil::create_index_expression('ADDRESS_addressid',$value['ADDRESS_addressid']);
			$index_exp2 = CassandraUtil::create_index_expression('zip',$zipcode);
			$index_clause1 = CassandraUtil::create_index_clause(array($index_exp1,$index_exp2),$offset,10);
			$result1 =  $query->get_indexed_slices($index_clause1);

			foreach($result1 as $key1=>$value1)
			{
				$value['socialeventid'] = $key;
				array_push($result2,$key,$value);
			}
		}


            }else if(!is_null($zipcode)  && !is_null($order) && $order == "event_date"){
//              $query = "select socialeventid,title,summary,imagethumburl,createdtimestamp,eventdate,submitterusername From SOCIALEVENT as se,ADDRESS as a where se.eventtimestamp>=CURRENT_TIMESTAMP and se.ADDRESS_addressid=a.addressid and a.zip='$zipcode' ORDER BY se.eventdate ASC limit $offset,10";

		$sql = new ColumnFamily($connection,'SOCIALEVENT');
		$index_exp_dummy = CassandraUtil::create_index_expression('disabled',0,cassandra_IndexOperator::EQ);
		$index_exp = CassandraUtil::create_index_expression('eventtimestamp',time(),cassandra_IndexOperator::GTE);
		$index_clause = CassandraUtil::create_index_clause(array($index_exp_dummy,$index_exp));
		$result =  $sql->get_indexed_slices($index_clause);
		$query = new ColumnFamily($connection,'ADDRESS');

		foreach($result as $key=>$value)
		{
			$index_exp1 = CassandraUtil::create_index_expression('ADDRESS_addressid',$value['ADDRESS_addressid']);
			$index_exp2 = CassandraUtil::create_index_expression('zip',$zipcode);
			$index_clause1 = CassandraUtil::create_index_clause(array($index_exp1,$index_exp2),$offset,10);
			$result1 =  $query->get_indexed_slices($index_clause1);

			foreach($result1 as $key1=>$value1)
			{
				$value['socialeventid'] = $key;
				array_push($result2,$key,$value);
			}
		}
           }      
            return $result2;            
 
//	    return $query;
	  }

    function getNumPages($zipcode,$eventdate,$connection){

		$numRecords = 0;
      if(!is_null($eventdate)){

			$query = new ColumnFamily($connection,'SOCIALEVENT');
			$index_exp = CassandraUtil::create_index_expression('eventdate', $eventdate);
			$index_clause = CassandraUtil::create_index_clause(array($index_exp));
			$numRecords = count($query->get_indexed_slices($index_clause));

		}else if(!is_null($_REQUEST['zipcode']) ){
		
			$sql = new ColumnFamily($connection,'SOCIALEVENT');
			$index_exp_dummy = CassandraUtil::create_index_expression('disabled',0,cassandra_IndexOperator::EQ);
			$index_exp = CassandraUtil::create_index_expression('eventtimestamp',time(),cassandra_IndexOperator::GTE);
			$index_clause = CassandraUtil::create_index_clause(array($index_exp_dummy,$index_exp));
			$result =  $sql->get_indexed_slices($index_clause);
			$query = new ColumnFamily($connection,'ADDRESS');

			foreach($result as $key=>$value)
			{
				$index_exp1 = CassandraUtil::create_index_expression('ADDRESS_addressid',$value['ADDRESS_addressid']);
				$index_exp2 = CassandraUtil::create_index_expression('zip',$zipcode);
				$index_clause1 = CassandraUtil::create_index_clause(array($index_exp1,$index_exp2));
				$result1 =  $query->get_indexed_slices($index_clause1);

				foreach($result1 as $key1=>$value1)
				{
					$numRecords+=1;
				}
			}
      }else{
			
			$sql = new ColumnFamily($connection,'SOCIALEVENT');
			$index_exp_dummy = CassandraUtil::create_index_expression('disabled',0,cassandra_IndexOperator::EQ);
			$index_exp = CassandraUtil::create_index_expression('eventtimestamp',time(),cassandra_IndexOperator::GTE);
			$index_clause = CassandraUtil::create_index_clause(array($index_exp_dummy,$index_exp));
			$numRecords =  count($sql->get_indexed_slices($index_clause));

      }

      $numEvents = $numRecords;
      //Calcuate total pages
      $numPages  = ceil($numEvents / 10);
      unset($result);
      return 5;
    }
   
    function getIndexEvents($zipcode,$order,$eventdate,$offset,$seid,$signedinuser,$connection){
      if(!is_null($seid)){
			$eventsQuery = new ColumnFamily($connection,'SOCIALEVENT');
	      $eventsresult =  $eventsQuery->get($seid,$columns=array('socialeventid','title','summary','imagethumburl','createdtimestamp','eventdate','submitterusername'));

      }else if(!is_null($eventdate) ){
			$eventsQuery = new ColumnFamily($connection,'SOCIALEVENT');
			$index_exp = CassandraUtil::create_index_expression('eventdate',$eventdate);
			$index_clause = CassandraUtil::create_index_clause(array($index_exp));
			$result =  $eventsQuery->get_indexed_slices($index_clause);
			$query = new ColumnFamily($connection,'ADDRESS');

			foreach($result as $key=>$value)
			{
				$index_exp1 = CassandraUtil::create_index_expression('ADDRESS_addressid',$value['ADDRESS_addressid']);
				$index_clause1 = CassandraUtil::create_index_clause(array($index_exp1),$offset,10);
				$result1 =  $query->get_indexed_slices($index_clause1);

				foreach($result1 as $key1=>$value1)
				{
					$value['socialeventid'] = $key;
					array_push($eventsresult,$key,$value);
				}
			}
      }else{
         $eventsresult=$this->getHomePageEvents($zipcode,$order,$offset,$connection);
      }
      $dateFormat = "l,  F j,  Y,  h:i A";
      ob_start();
	   foreach($eventsresult as $key=>$row){
      	$rowsFound = true;
         $title = $row['title'];
			if ($seid != NULL)
				$se = $seid;
			else
				$se = $key;
         $image = $row['imagethumburl'];
         $summary = $row['summary'];
         $submitter = $row['submitterUserName'];
         $ed = $row['eventdate'];
         $cd = trim($this->formatdatetime($dateFormat,$row['createdtimestamp']));
         require("../views/indexEvents.php");
     	}
     	unset($eventsresult);
     	$indexEvents = ob_get_contents();
      ob_end_clean();
      return $indexEvents;
    }
     
    function getNumAttendees($se,$connection) {
//            $query="select count(username) as count from PERSON_SOCIALEVENT where socialeventid = '$se'";
//            $result = $connection->query($query);
//            $row = $result->getArray();

	      $query = new ColumnFamily($connection,'PERSON_SOCIALEVENT');
	      $index_exp = CassandraUtil::create_index_expression('socialeventid', $se);
	      $index_clause = CassandraUtil::create_index_clause(array($index_exp));
	      $result = $query->get_indexed_slices($index_clause);
	      $row['count']=0;
	      foreach ($result as $key => $column) {
		 $row['count']+=1;
              }

            $count = $row['count'];
            unset($result);
            return $count;
    }
 
    function getRecentlyPostedEventsOfUser($user,$connection,$flag,$offset){
            if (!$flag){
              //$query="select socialeventid,title from SOCIALEVENT where submitterusername='$user' and createdtimestamp between date_add(now(),interval -15 day) and now() and eventtimestamp>=CURRENT_TIMESTAMP order by eventdate asc limit 10";
//              $query="select socialeventid,title from SOCIALEVENT where submitterusername='$user' and createdtimestamp<=now() and eventtimestamp>=CURRENT_TIMESTAMP order by eventdate asc limit 10";
		$query = new ColumnFamily($connection,'SOCIALEVENT');
		$index_exp1 = CassandraUtil::create_index_expression('submitterUserName',$user,cassandra_IndexOperator::EQ);
		$index_exp2 = CassandraUtil::create_index_expression('createdtimestamp',time(),cassandra_IndexOperator::LTE);
		$index_exp3 = CassandraUtil::create_index_expression('eventtimestamp',time(),cassandra_IndexOperator::GTE);
		$index_clause = CassandraUtil::create_index_clause(array($index_exp1,$index_exp2,$index_exp3),0,10);
		$result =  $query->get_indexed_slices($index_clause);
            $count = 1;
            }else if ($flag){
              //$query="select socialeventid,title from SOCIALEVENT where submitterusername='$user' and createdtimestamp between date_add(now(),interval -90 day) and now() and eventtimestamp>=CURRENT_TIMESTAMP order by eventdate asc limit 30";  
//              $query="select socialeventid,title from SOCIALEVENT where submitterusername='$user' and createdtimestamp<=now() and eventtimestamp>=CURRENT_TIMESTAMP order by eventdate asc limit $offset,10";  
		$query = new ColumnFamily($connection,'SOCIALEVENT');
		$index_exp1 = CassandraUtil::create_index_expression('submitterUserName',$user,cassandra_IndexOperator::EQ);
		$index_exp2 = CassandraUtil::create_index_expression('createdtimestamp',time(),cassandra_IndexOperator::LTE);
		$index_exp3 = CassandraUtil::create_index_expression('eventtimestamp',time(),cassandra_IndexOperator::GTE);
		$index_clause = CassandraUtil::create_index_clause(array($index_exp1,$index_exp2,$index_exp3),$offset,10);
		$result =  $query->get_indexed_slices($index_clause);        
              $count = 1 + $offset;
            }
	   foreach($result as $key=>$row){

                $rowsFound = true;
                $title = $row['title'];
              //  $se = $row['socialeventid'];
		$se = $key;
                $recentPostedEvents = $recentPostedEvents." ".'<a href="events.php?socialEventID='.$se.'">'.$count.'. '.$title.'</a><br/>';                
                $count++;
            }
            unset($result);
            return $recentPostedEvents;
    }

    function getUpcomingEventsForUser($user,$connection,$flag,$offset){
            if (!$flag){
//		$query = "select se.socialeventid,se.title From SOCIALEVENT as se,PERSON_SOCIALEVENT as ps where se.socialeventid=ps.socialeventid and se.eventtimestamp>=CURRENT_TIMESTAMP and ps.username='$user' ORDER BY se.eventdate ASC limit 3";
		$sql = new ColumnFamily($connection,'PERSON_SOCIALEVENT');
		$index_exp_uname = CassandraUtil::create_index_expression('username',$user);
		$index_clause = CassandraUtil::create_index_clause(array($index_exp_uname),0,3);
		$rows =  $sql->get_indexed_slices($index_clause,$columns=array('socialeventid'));
		$query = new ColumnFamily($connection,'SOCIALEVENT');

		foreach($rows as $key => $columns) {
			$index_exp1 = CassandraUtil::create_index_expression('socialeventid', $columns['socialeventid'],cassandra_IndexOperator::EQ);
			$index_exp2 = CassandraUtil::create_index_expression('eventtimestamp',time(),cassandra_IndexOperator::GTE);
			$index_clause1 = CassandraUtil::create_index_clause(array($index_exp1,$index_exp2));
			$queryresult =  $query->get_indexed_slices($index_clause1);
			$queryresult =  $query->get_indexed_slices($index_clause1);
			foreach($queryresult as $key1=>$value1)
				$result[$key1]=$value1;
		}
                $count = 1;
            }else if ($flag){
//		$query = "select se.socialeventid,se.title From SOCIALEVENT as se,PERSON_SOCIALEVENT as ps where se.socialeventid=ps.socialeventid and se.eventtimestamp>=CURRENT_TIMESTAMP and ps.username='$user' ORDER BY se.eventdate ASC limit $offset,10";
		$sql = new ColumnFamily($connection,'PERSON_SOCIALEVENT');
		$index_exp_uname = CassandraUtil::create_index_expression('username',$user);
		$index_clause = CassandraUtil::create_index_clause(array($index_exp_uname),$offset,10);
		$rows =  $sql->get_indexed_slices($index_clause,$columns=array('socialeventid'));
		$query = new ColumnFamily($connection,'SOCIALEVENT');

		foreach($rows as $key => $columns) {
			$index_exp1 = CassandraUtil::create_index_expression('socialeventid', $columns['socialeventid'],cassandra_IndexOperator::EQ);
			$index_exp2 = CassandraUtil::create_index_expression('eventtimestamp',time(),cassandra_IndexOperator::GTE);
			$index_clause1 = CassandraUtil::create_index_clause(array($index_exp1,$index_exp2));
			$queryresult =  $query->get_indexed_slices($index_clause1);
			$queryresult =  $query->get_indexed_slices($index_clause1);
			foreach($queryresult as $key1=>$value1)
				$result[$key1]=$value1;
		}
		 $count = 1 + $offset;
            }
		foreach($result as $key1=>$row){
		$rowsFound = true;
                $title = $row['title'];
                $se = $row['socialeventid'];
                $upcomingEvents = $upcomingEvents." ".'<a href="events.php?socialEventID='.$se.'">'.$count.'. '.$title.'</a><br/>';
                $count++;
            }
            unset($result);
            return $upcomingEvents;
    }


    static function getInstance() {
        $instance = new Events_Controller();
        return $instance;
    }
    
}
?>
