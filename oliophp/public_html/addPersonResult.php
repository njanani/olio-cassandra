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
        
require_once("../etc/config.php");
require_once('../etc/phpcassa_config.php');

// 1. Get data from submission page.
$username=$_POST['add_user_name'];
$pwd    =$_POST['psword'];
$summary=$_POST['summary'];
$fname=$_POST['first_name'];
$lname=$_POST['last_name'];
$email=$_POST['email'];
$strt1= $_POST['street1'];
$street2= $_POST['street2'];
$cty  = $_POST['city'];
$street1=str_replace(" ","+",$strt1);
$city = str_replace(" ","+",$cty);
$state=$_POST['state'];
$zip=$_POST['zip'];
$country=$_POST['country'];
$telephone=$_POST['telephone'];
$timezone=$_POST['timezone'];
$image_name= basename($_FILES['user_image']['name']);

// 2. Get coordinates of the address.
$geocode = new Geocoder($street1, $city, $state, $zip);

// 3. Insert address and get the address id, or update the address.
if(isset($_POST['addpersonsubmit'])) {
	$insertaddr = new ColumnFamily($conn,'ADDRESS');
	$addrid = exec("python /usr/pysnowflakeclient/pysnowflakeclient/__init__.py");
	$insertaddr->insert($addrid,array('addressid' => $addrid,'street1' => $strt1,'street2' => $street2, 'city' => $cty,'state' => $state,'zip' => $zip, 'country' => $country,'latitude' => $geocode->latitude,'longitude' => $geocode->longitude));

   // At least temporary place holder for the image.
   $modified_image_name = Olio::$config['includes'] . "userphotomissing.gif";
   $imagethumb = Olio::$config['includes'] . "userphotomissing.gif";

	$insertsql = new ColumnFamily($conn,'PERSON');
	$userid = exec("python /usr/pysnowflakeclient/pysnowflakeclient/__init__.py");
	$insertsql->insert($userid,array('userid' => $userid,'username' => $username ,'password' => $pwd,'firstname' => $fname,'lastname' => $lname,'email' => $email,'telephone' => $telephone, 'imageurl' => $modified_image_name,'imagethumburl' => $imagethumb,'summary' => $summary,'timezone' => $timezone,'ADDRESS_addressid' => $addrid));

}
else if (isset($_POST['addpersonsubmitupdate'])) {

 	if ($summary == "" ) {
		$sumquery = new ColumnFamily($conn,'PERSON');
		$index_exp = CassandraUtil::create_index_expression('username', $uname);
		$index_clause = CassandraUtil::create_index_clause(array($index_exp));
		$sumresult = $sumquery->get_indexed_slices($index_clause);

		foreach ($sumresult as $key => $column) {
			$summary = $column['summary'];
		}
		unset($sumresult);
	}
	$insertaddr = new ColumnFamily($conn,'ADDRESS');
	$addrid = exec("python /usr/pysnowflakeclient/pysnowflakeclient/__init__.py");
	$insertaddr->insert($addrid,array('addressid' => $addrid,'street1' => $strt1,'street2' => $street2,'city' => $cty,'state' => $state,'zip' => $zip,'country' => $country,'latitude' => $geocode->latitude,'longitude' => $geocode->longitude));
	unset($idres);
	$cf = new ColumnFamily($conn,'PERSON');
	$index_exp = CassandraUtil::create_index_expression('username',$username);
	$index_clause = CassandraUtil::create_index_clause(array($index_exp));
   $rows = $cf->get_indexed_slices($index_clause);
	
	foreach($rows as $key => $row) {
		$userid  = $row['userid'];
		$cf->insert($row['userid'],array('password' => $pwd,'firstname' => $fname,'lastname' => $lname,'email' => $email,'telephone' => $telephone,'timezone' => $timezone,'ADDRESS_addressid' => $addrid));
		if ($summary != "") {
			$cf->insert($row['userid'],array('summary' => $summary));
		}
	}

}


if ($image_name != "") {

    // 5. Determine the image id.
    $pos=strpos($image_name,'.');
    $img_ext = substr($image_name,$pos,strlen($image_name));
    $modified_image_name = "P".$userid.$img_ext;
    $resourcedir = '/tmp/';
    $imagethumb = "P".$userid."T".$img_ext;
    $user_image_location = $resourcedir . $modified_image_name;
    if (!move_uploaded_file($_FILES['user_image']['tmp_name'], $user_image_location)) {
        throw new Exception("Error moving uploaded file to $user_image_location");
    }

    // 6. Generate the thumbnails.
    $thumb_location = $resourcedir . $imagethumb;
    ImageUtil::createThumb($user_image_location, $thumb_location, 120, 120);

    // 7. Store the image.
    $fs = FileSystem::getInstance();

    if (!$fs->create($user_image_location, "NO_OP", "NO_OP")) {
        error_log("Error copying image " . $user_image_location);
    }
    if (!$fs->create($thumb_location, "NO_OP", "NO_OP")) {
        error_log("Error copying thumb " . $thumb_location);
    }
    unlink($user_image_location);
    unlink($thumb_location);
    // 8. Update the DB.
	$cf = new ColumnFamily($conn,'PERSON');
	$cf->insert($userid,array('imageurl' => $modified_image_name,'imagethumburl' => $imagethumb));
}

header("Location:users.php?username=".$username);

?>
