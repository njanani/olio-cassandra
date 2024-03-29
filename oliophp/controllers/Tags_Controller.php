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
 * This is Tags Controller. This computes the tagcloud
 * that needs to be displayed on the homepage.
 */

class Tags_Controller {
    function getHomePageTagCloud($connection) {
//        $cloudquery = "(SELECT st.tag as tag,st.refcount as count from SOCIALEVENTTAG as st where st.refcount>100 limit 50)  order by tag ASC";
 //      $cloudquery = "(SELECT st.tag as tag,st.refcount as count from SOCIALEVENTTAG as st order by st.refcount desc limit 50) order by tag asc";
 //       $cloudresult =$connection->query($cloudquery);

	$cloudquery = new ColumnFamily($connection,'SOCIALEVENTTAG');
	$cloudresult = $cloudquery->get_range($key_start="1",$key_finish="50");
        $rowsFound = false;

	foreach($cloudresult as $key1=>$row){
            $rowsFound = true;
            $tags[$row['tag']] = $row['refcount'];
        }
      
	 unset($cloudresult);
        if ($rowsFound) {
            // change these font sizes if you will
            $max_size = 250; // max font size in %
            $min_size = 100; // min font size in %

            // get the largest and smallest array values
            $max_count = max(array_values($tags));
            $min_count = min(array_values($tags));

            // find the range of values
            $spread = $max_count - $min_count;
            if (0 == $spread) { // we don't want to divide by zero
                $spread = 1;
            }

            // determine the font-size increment
            // this is the increase per tag count (times used)
            $step = ($max_size - $min_size)/($spread);

            // loop through our tag array
            foreach ($tags as $key => $value) {
                $size = $min_size + (($value - $min_count) * $step);
                // uncomment if you want sizes in whole %:
                $size = ceil($size);
                $homePageTagCloud =  $homePageTagCloud." ".'<a href="taggedEvents.php?tag='.$key.'&count='.$value.'" style="font-size:'.$size.'%" title="'.$value.' events tagged with '.$key.'" >'.$key.'</a> ';
            }
        }

        return $homePageTagCloud;
    }
    
    function getEventsPageTagCloud($connection,$se) {
	$rowsFound = false;
   $sql = new ColumnFamily($connection,'SOCIALEVENTTAG_SOCIALEVENT');
   $cloudquery = new ColumnFamily($connection,'SOCIALEVENTTAG');
   $index_exp = CassandraUtil::create_index_expression('socialeventid',$se );
   $index_clause = CassandraUtil::create_index_clause(array($index_exp));
   $result =  $sql->get_indexed_slices($index_clause);
   if(!empty($result)) {
      foreach($result as $key => $value){
      	if($value['socialeventtagid'] != NULL) {
		   	$cloudresult = $cloudquery->get($value['socialeventtagid']);
				
				if($cloudresult['refcount'] > 0) {
						$rowsFound = true;
						$tags[$cloudresult['tag']] = $cloudresult['refcount'];
				}
			}
      }
   }
        unset($cloudresult);

        if ($rowsFound) {
            // change these font sizes if you will
            $max_size = 250; // max font size in %
            $min_size = 100; // min font size in %

            // get the largest and smallest array values
            $max_count = max(array_values($tags));
            $min_count = min(array_values($tags));

            // find the range of values
            $spread = $max_count - $min_count;
            if (0 == $spread) { // we don't want to divide by zero
                $spread = 1;
            }

            // determine the font-size increment
            // this is the increase per tag count (times used)
            $step = ($max_size - $min_size)/($spread);

            // loop through our tag array
            foreach ($tags as $key => $value) {
                $size = $min_size + (($value - $min_count) * $step);
                // uncomment if you want sizes in whole %:
                $size = ceil($size);
                $eventTagCloud =  $eventTagCloud." ".'<a href="taggedEvents.php?tag='.$key.'" style="font-size:'.$size.'%" title="'.$value.' events tagged with '.$key.'" >'.$key.'</a> ';
            }
        }
        return $eventTagCloud;
    }

    static function getInstance() {
        $instance = new Tags_Controller();
        return $instance;
    }
}
?>
