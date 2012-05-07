<?php
set_time_limit(0);
$dbcon = mysql_connect("localhost","olio","olio");
if(!$dbcon)
{
	die('couldnt connect to db'.mysql_error());
}
$mydb = mysql_select_db('olio',$dbcon);

if(!$mydb)
{
	die('could not open database'.mysql_error());
}

require_once('phpcassa/connection.php');
require_once('phpcassa/columnfamily.php');
$servers[0]['host'] = '127.0.0.1';
$servers[0]['port'] = '9160';
$conn = new Connection('Keyspace1', $servers);

//populate person table

$query1 = "select * from PERSON";
$result = mysql_query($query1);

$count = mysql_num_rows($result);

$column_family = new ColumnFamily($conn, 'PERSON');
while($row = mysql_fetch_array($result))
{
	$column_family->insert($row['userid'],array('userid' => $row['userid'],'username'=>$row['username'],'password'=>$row['password'],'firstname'=>$row['firstname'],'lastname'=>$row['lastname'],'email'=>$row['email'],'telephone'=>$row['telephone'],'imageurl'=>$row['imageurl'],'imagethumburl'=>$row['imagethumburl'],'summary'=>$row['summary'],'timezone'=>$row['timezone'],'ADDRESS_addressid'=>$row['ADDRESS_addressid']));
}


//populate socialevent

$query1 = "select socialeventid,title,description,submitterUserName,ADDRESS_addressid,totalscore,numberofvotes,imageurl,imagethumburl,literatureurl,telephone,timezone,disabled,eventdate,summary,UNIX_TIMESTAMP(eventtimestamp) AS eventtimestamp,UNIX_TIMESTAMP(createdtimestamp) AS createdtimestamp from SOCIALEVENT";
$result = mysql_query($query1);

$count = mysql_num_rows($result);

$column_family = new ColumnFamily($conn, 'SOCIALEVENT');
while($row = mysql_fetch_array($result))
{

	$column_family->insert($row['socialeventid'],array('socialeventid'=>$row['socialeventid'],'title'=>$row['title'],'description'=>$row['description'],'submitterUserName'=>$row['submitterUserName'],'ADDRESS_addressid'=>$row['ADDRESS_addressid'],'totalscore'=>$row['totalscore'],'numberofvotes'=>$row['numberofvotes'],'imageurl'=>$row['imageurl'],'imagethumburl'=>$row['imagethumburl'],'literatureurl'=>$row['literatureurl'],'telephone'=>$row['telephone'],'timezone'=>$row['timezone'],'eventtimestamp'=>$row['eventtimestamp'],'createdtimestamp'=>$row['createdtimestamp'],'disabled'=>$row['disabled'],'eventdate'=>$row['eventdate'],'summary'=>$row['summary']));

}

//populate address

$query1 = "select * from ADDRESS";
$result = mysql_query($query1);

$count = mysql_num_rows($result);
$column_family = new ColumnFamily($conn, 'ADDRESS');
while($row = mysql_fetch_array($result))
{
	$column_family->insert($row['addressid'],array('ADDRESS_addressid'=>$row['addressid'],'street1'=>$row['street1'],'street2'=>$row['street2'],'city'=>$row['city'],'state'=>$row['state'],'zip'=>$row['zip'],'country'=>$row['country'],'latitude'=>$row['latitude'],'longitude'=>$row['longitude']));

}

//populate socialevent

$query1 = "select * from SOCIALEVENTTAG";
$result = mysql_query($query1);

$count = mysql_num_rows($result);
$column_family = new ColumnFamily($conn, 'SOCIALEVENTTAG');
while($row = mysql_fetch_array($result))
{
	$column_family->insert($row['socialeventtagid'],array('socialeventtagid' => $row['socialeventtagid'],'tag'=>$row['tag'],'refcount'=>$row['refcount']));

}
//populate comment ratings

$query1 = "select * from COMMENTS_RATING";
$result = mysql_query($query1);

$count = mysql_num_rows($result);
$column_family = new ColumnFamily($conn, 'COMMENTS_RATING');
while($row = mysql_fetch_array($result))
{
	$column_family->insert($row['commentid'],array('commentid' => $row['commentid'] ,'username'=>$row['username'],'socialeventid'=>$row['socialeventid'],'comments'=>$row['comments'],'ratings'=>$row['ratings'],'created_at'=>$row['created_at'],'updated_at'=>$row['updated_at']));
}


$query1 = "select * from SOCIALEVENTTAG_SOCIALEVENT";
$result = mysql_query($query1);

$count = mysql_num_rows($result);
$column_family = new ColumnFamily($conn, 'SOCIALEVENTTAG_SOCIALEVENT');

$i=1;

while($row = mysql_fetch_array($result))
{
	$column_family->insert($i,array('id' => $i,'socialeventtagid'=>$row['socialeventtagid'],'socialeventid'=>$row['socialeventid']));
	$i = $i + 1;
}

$query1 = "select * from PERSON_SOCIALEVENT";
$result = mysql_query($query1);


$count = mysql_num_rows($result);
$column_family = new ColumnFamily($conn, 'PERSON_SOCIALEVENT');
$i=1;
while($row = mysql_fetch_array($result))
{
	$column_family->insert($i,array('id' => $i,'username'=>$row['username'],'socialeventid'=>$row['socialeventid']));
	$i = $i + 1;
}

$query1 = "select * from PERSON_PERSON";
$result = mysql_query($query1);

$count = mysql_num_rows($result);
$column_family = new ColumnFamily($conn, 'PERSON_PERSON');
$i=1;
while($row = mysql_fetch_array($result))
{
	$column_family->insert($i,array('id' => $i,'Person_username'=>$row['Person_username'],'friends_username'=>$row['friends_username'],'is_accepted'=>$row['is_accepted']));
	$i = $i + 1 ;
}
mysql_close($dbcon);
?>
