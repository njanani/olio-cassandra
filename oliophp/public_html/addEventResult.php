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
    
session_start();    
require_once("../etc/config.php");
require_once('../etc/phpcassa_config.php');
$connection = DBConnection::getWriteInstance();

// 1. Get data from submission page.
$description=$_POST['description'];
$summary = $_POST['summary'];
$title=$_POST['title'];
$strt1= $_POST['street1'];
$street2= $_POST['street2'];
$cty  = $_POST['city'];
$street1 = str_replace(" ","+",$strt1);
$city = str_replace(" ","+",$cty);
$state = $_POST['state'];
$zip = $_POST['zip'];
$country = $_POST['country'];
$telephone = $_POST['telephone'];
$year = $_POST['year'];
$month = $_POST['month'];
$day = $_POST['day'];
$hour = $_POST['hour'];
$minute = $_POST['minute'];
$eventtime=strtotime($year.$month.$day.$hour.$minute."00");
$eventdate=$year."-".$month."-".$day;
$tags=$_POST['tags'];
//echo "Tags = ".$tags."<br/>";

$image_name= basename($_FILES['upload_image']['name']);
$literature_name=basename($_FILES['upload_literature']['name']);


// 2. Get coordinates of the address.
$geocode = new Geocoder($street1, $city, $state, $zip);

// 3. Insert address and get the address id.
$insertaddr = new ColumnFamily($conn,'ADDRESS');
$addrid = exec("python /usr/pysnowflakeclient/pysnowflakeclient/__init__.py");
$insertaddr->insert($addrid,array('addressid' => $addrid,'street1' => $strt1 ,'street2' => $street2,'city' => $cty,'state' => $state,
'zip' => $zip,'country' => $country,'latitude' => $geocode->latitude, 'longitude' => $geocode->longitude));

// 4. Insert event and get the event id.
$usrnm = $_SESSION["uname"];
$evid =  $_SESSION["addEventSE"];

if (isset($_POST['addeventsubmit'])) {
	$insertse = new ColumnFamily($conn,'SOCIALEVENT');
	$eventid =  exec("python /usr/pysnowflakeclient/pysnowflakeclient/__init__.py");
	$insertse->insert($eventid,array('socialeventid' => $eventid,'title' => $title ,'description' => $description,'summary' => $summary ,'submitterUsername' => $usrnm,'ADDRESS_addressid' => $addrid ,'telephone' => $telephone,'timezone' => $timezone, 'eventtimestamp' => $eventtime, 'createdtimestamp' => time(), 'eventdate' => $eventdate,'totalscore' => 0,'disabled' => 0));
}else if (isset($_POST['addeventsubmitupdate'])) {
	$cf = new ColumnFamily($conn,'SOCIALEVENT');
	$isSuccess = $cf->insert($evid,array('title' => $title,'description' => $description,'summary' => $summary,'submitterUserName' => $usrnm,'ADDRESS_addressid' => $addrid,'telephone' => $telephone,'timezone' => $timezone,'eventtimestamp' => $eventtime,'eventdate' => $eventdate));

    if (!$isSuccess)
    throw new Exception("Error updating event with image locations. Update returned $updated!");

}

// 5. Check tags. Insert if not available and get id, then insert relationship.
$tagList = preg_split("/[\s,]+/", trim($tags));

// We need to sort the tags before insert/update. Different tag sequences
// can lead to deadlocks.
sort($tagList);
foreach ($tagList as $tag) {
	 $cf = new ColumnFamily($conn,'SOCIALEVENTTAG');
    $index_exp = CassandraUtil::create_index_expression('tag',$tag);
    $index_clause = CassandraUtil::create_index_clause(array($index_exp));
    $result = $cf->get_indexed_slices($index_clause);

    if (isset($_POST['addeventsubmit'])) {
        $count = 0;

        foreach ($result as $key => $col) {
           $count = 1;
			  // Even if we update, we still need the tagid.
           $tagid = $col['socialeventtagid'];
			  $cf->insert($tagid,array('refcount' => $col['refcount'] + 1));
        }
        if ($count == 0) { // Update did not find the tag, so we insert.
				$inserttag = new ColumnFamily($conn,'SOCIALEVENTTAG');
				$tagid =  exec("python /usr/pysnowflakeclient/pysnowflakeclient/__init__.py");
				$inserttag->insert($tagid,array('socialeventtagid' => $tagid,'tag' => $tag,'refcount' => 1 ));
        } 

        // Now, insert relationship.
		  $inserttagid = new ColumnFamily($conn,'SOCIALEVENTTAG_SOCIALEVENT');
		  $id = exec("python /usr/pysnowflakeclient/pysnowflakeclient/__init__.py");
		  $inserttagid->insert($id,array('id' => $id,'socialeventtagid' => $tagid,'socialeventid' => $eventid));
    }

    if (isset($_POST['addeventsubmitupdate'])) {
		$rowsFound = false;
		foreach ($result as $key => $row) {
	        $rowsFound = true;
			$tagid = $key;
		}

        if(!$rowsFound){
				$inserttag = new ColumnFamily($conn,'SOCIALEVENTTAG');
				$tagid = exec("python /usr/pysnowflakeclient/pysnowflakeclient/__init__.py");
				$inserttag->insert($tagid,array('socialeventtagid' => $tagid,'tag' => $tag,'refcount' => 1));

				$inserttagid = new ColumnFamily($conn,'SOCIALEVENTTAG_SOCIALEVENT');
				$id = exec("python /usr/pysnowflakeclient/pysnowflakeclient/__init__.py");
				$inserttagid->insert($id,array('id' => $id,'socialeventtagid' => $tagid,'socialeventid' => $evid));
        }
    }
}

// 6. Insert submitter to the event attendee list.
if (isset($_POST['addeventsubmit'])) {
	$insertPS = new ColumnFamily($conn,'PERSON_SOCIALEVENT');
	$id = exec("python /usr/pysnowflakeclient/pysnowflakeclient/__init__.py");	
	$insertPS->insert($id,array('id' => $id,'username' => $usrnm,'socialeventid' => $eventid));	
}

// 7. Determine image and thumbnail file names.
$default_image = false;
if ($image_name != "") {
    $pos=strpos($image_name,'.');
    $img_ext = substr($image_name,$pos,strlen($image_name));
    if (isset($_POST['addeventsubmit'])) {
        $modified_image_name = "E".$eventid.$img_ext;
    }else if (isset($_POST['addeventsubmitupdate'])) {
        $modified_image_name = "E".$evid.$img_ext;
    }
    if (isset($_POST['addeventsubmit'])) {
        $imagethumb = "E".$eventid."T".$img_ext;
    }else if (isset($_POST['addeventsubmitupdate'])) {
        $imagethumb = "E".$evid."T".$img_ext;
    }
} else {
    if (isset($_POST['addeventsubmit'])) {
        $default_image = true;
        $modified_image_name = "";
        $imagethumb = "";
    }else if (isset($_POST['addeventsubmitupdate'])) {
			$imgq = new ColumnFamily($conn,'SOCIALEVENT');
        $imgqresult =  $imgq->get($evid,$columns=array('imageurl', 'imagethumburl'));
			if($imgqresult)
			{
				$modified_image_name = $imgqresult['imageurl'];
				$imagethumb = $imgqresult['imagethumburl'];
			}
      	  unset($imgqresult);
    }
}

// 8. Determine literature file names.
$default_literature = false;
if ($literature_name != "") {
    $pos=strpos($literature_name,'.');
    $lit_ext = substr($literature_name,$pos,strlen($literature_name));
    if (isset($_POST['addeventsubmit'])) {
        $modified_literature_name="E".$eventid."L".$lit_ext;
    }else if (isset($_POST['addeventsubmitupdate'])) {
        $modified_literature_name="E".$evid."L".$lit_ext;
    }
} else {
    if (isset($_POST['addeventsubmit'])) {
        $default_literature = true;
        $modified_literature_name="";
    }else if (isset($_POST['addeventsubmitupdate'])) {
			$litq = new ColumnFamily($conn,'SOCIALEVENT');
        $litqresult =  $imgq->get($evid,$columns=array('literatureurl'));
		if( $litqresult)
		$modified_literature_name =  $litqresult['literatureurl'];

        unset($litqresult);
    }
}

// We end the DB transaction here.
//$connection->commit();

// 9. Generate thumbnail and save images to file storage (outside tx)
if ($image_name != "") {
    $resourcedir = '/tmp/';
    $user_image_location = $resourcedir . $modified_image_name;
    if (!move_uploaded_file($_FILES['upload_image']['tmp_name'], $user_image_location)) {
        throw new Exception("Error moving uploaded file to $modified_image_name");
    }
    $thumb_location = $resourcedir . $imagethumb;
    ImageUtil::createThumb($user_image_location, $thumb_location, 120, 120);
    if (!isset($fs))
    $fs = FileSystem::getInstance();
    if (!$fs->create($user_image_location, "NO_OP", "NO_OP")) {
        error_log("Error copying image " . $user_image_location);
    }
    if (!$fs->create($thumb_location, "NO_OP", "NO_OP")) {
        error_log("Error copying thumb " . $thumb_location);
    }
    unlink($user_image_location);
    unlink($thumb_location);
}

// 10. Save literature file to storage
if ($literature_name != "") {
    $lit_resourcedir = '/tmp/';
    $upload_literature_location = $lit_resourcedir . $modified_literature_name;
    if (!move_uploaded_file($_FILES['upload_literature']['tmp_name'], $upload_literature_location)) {
        throw new Exception("Error moving uploaded file to $upload_literature_location");
    }
    if (!isset($fs))
    $fs = FileSystem::getInstance();
    if (!$fs->create($upload_literature_location, "NO_OP", "NO_OP")) {
        error_log("Error copying literature " . $upload_literature_location);
    }
    unlink($upload_literature_location);
}


// 11. Update the image names back to the database.
// Note: this update is in it's own transaction, after the images are
// properly stored. It is a single statement transaction and with autocommit
// on, we do not need to start and commit.
if (isset($_POST['addeventsubmit'])) {
	$updatese = new ColumnFamily($conn,'SOCIALEVENT');
	$updatese->insert($eventid,array('imageurl' => $modified_image_name,'imagethumburl' => $imagethumb,'literatureurl' => $modified_literature_name));
} else if (isset($_POST['addeventsubmitupdate'])) {
	$updatese = new ColumnFamily($conn,'SOCIALEVENT');
	$updatese->insert($evid,array('imageurl' => $modified_image_name,'imagethumburl' => $imagethumb,'literatureurl' => $modified_literature_name));
}

// 12. Redirect the results.
if (isset($_POST['addeventsubmit'])) {
    header("Location:events.php?socialEventID=".$eventid);
}else if (isset($_POST['addeventsubmitupdate'])) {
    header("Location:events.php?socialEventID=".$evid);
}

?>
