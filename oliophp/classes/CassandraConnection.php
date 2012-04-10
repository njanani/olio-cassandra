<?php


class CassandraConnection
{
	function cassandra_query($sql)
	{
		require_once('phpcassa/connection.php');
		require_once('phpcassa/columnfamily.php');

	   $servers[0]['host'] = '127.0.0.1';
		    $servers[0]['port'] = '9160';
		    $conn = new Connection('Keyspace1', $servers);


		$sql_stmt = explode(" ",$sql);
		$cf = $sql_stmt[3];
		$uname = explode("=",$sql_stmt[5]);
		$passwd= explode("=",$sql_stmt[7]);

		echo $cf." ".str_replace("'","",$uname[1])." ".str_replace("'","",$passwd[1])." ".$uname[0]." ".$passwd[0];
		$column_family = new ColumnFamily($conn,$cf);
		echo $cf." ".str_replace("'","",$uname[1])." ".str_replace("'","",$passwd[1])." ".$uname[0]." ".$passwd[0];
		$index_exp = CassandraUtil::create_index_expression($uname[0],str_replace("'","",$uname[1]) );
		$index_clause = CassandraUtil::create_index_clause(array($index_exp));
		$rows = $column_family->get_indexed_slices($index_clause);

		foreach($rows as $key => $columns) {
			if($columns[$passwd[0]]==str_replace("'","",$passwd[1]))
			{
	//			echo $columns;
				return $columns;
			}
			else
				return NULL;
		}
	}
}

?>
