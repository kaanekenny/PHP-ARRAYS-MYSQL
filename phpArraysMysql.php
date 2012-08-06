<?php
/*

  @copyright Kenneth Kaane https://github.com/kaanekenny
  @license http://www.gnu.org/copyleft/gpl.html
 
*/

class DB{
	var $host, $user, $password, $db, $error = "";
	
	function DB($db, $host = "localhost", $user = "root", $password = ""){
		$this->host = $host;
		$this->user = $user;
		$this->password = $password;
		$this->db = $db;
		return $this->_getDB();
	}
	
	private function _connect(){
		$dbconnection = @mysql_connect($this->host, $this->user, $this->password);
		return $dbconnection;
	}

	private function _getDB(){
		if(!$dbconnection = $this->_connect()): echo $this->error = mysql_error(); return false;
		elseif(@mysql_select_db($this->db, $dbconnection)): return true; 
		else: echo $this->error = mysql_error(); return false;
		endif;
	}
	
	function getTable($tablename){
		if($this->error): echo $this->error; return false;
		else: return new Table($tablename);
		endif;
	}

}

class Table{

	var $tableName, $error = "", $columns = array('*');
	
	//Allowed Clauses using MySQL 5.5 
	
	var $allowedSelectClauses = array("SELECT","ALL","DISTINCT","DISTINCTROW","FROM","WHERE","GROUP BY","HAVING","ORDER BY","LIMIT","PROCEDURE","INTO OUTFILE","FOR UPDATE","LOCK IN SHARE MODE");
		
	var $allowedUpdateClauses = array("LOW_PRIORITY","IGNORE","SET","WHERE","ORDER BY","LIMIT");
	
	var $allowedDeleteClauses = array("");
	
	var $allowedInsertClauses = array("");
	
	var $clauses = Array();
	
	function Table($tableName){
	
		$this->tableName = $tableName;
			
	}
	
	function setClause($clause){
		
		$fullclause = trim($clause);
		$pos = strpos($fullclause, ' ');
		if($pos): $clause = substr($clause, 0, $pos); endif;
		$key = array_search($clause, array_merge ($this->allowedSelectClauses,$this->allowedUpdateClauses,$this->allowedDeleteClauses,$this->allowedInsertClauses));
		if($key !== false): 
			$this->clauses[] = $fullclause;
		endif;
		
		
	}
	
	function extractData($columns = array('*')){
	
		
		$sql = "";
		
		$columns = implode(",", $columns);
		
		$this->setClause('SELECT '.$columns);
		$this->setClause('FROM '.$this->tableName);
		
		foreach ($this->allowedSelectClauses as $clause) {

			$searchedclauses = preg_grep("/^$clause/", $this->clauses);
			
			if($searchedclauses): $sql .= current($searchedclauses).' '; endif;
			
		}

		try{
			return queryMYSQL($sql);
		}
		catch(Exception $e){
		  echo 'Error: ' .$e->getMessage();
		}
		
		
	}
	
	function updateData(array $data){
		$column_data = "";
		
		while ($value = current($data)) {
			$column_data .= key($data)." = '$value'";
			if(next($data) !== false) $column_data .= ", ";
		}
		
		$sql = "UPDATE $this->tableName SET $column_data";
		echo $sql.'<br />';
		try{
			return queryMYSQL($sql);
		}
		catch(Exception $e){
		  echo 'Error: ' .$e->getMessage();
		}
	}
	
	function deleteData(){
		$sql = "DELETE FROM $this->tableName";
		
		try{
			return queryMYSQL($sql);
		}
		catch(Exception $e){
		  echo 'Error: ' .$e->getMessage();
		}
	
	}
	
	function insertData(array $data){
		$sql = "INSERT INTO $this->tableName";
		
		$columns = implode(", ", array_keys($data));
		$column_data = "";
		
		foreach ($data as $value) {
			$column_data .= "'$value'";
			if(next($data) !== false) $column_data .= ", ";
		}
		
		if(isAssoc($data)){
			$sql .= " ($columns) VALUES ($column_data)";
			
		}else{
			$sql .= " VALUES ($column_data)";
		}
		
		echo $sql .= '</br>';
		try{
			return queryMYSQL($sql);
		}
		catch(Exception $e){
		  echo 'Error: ' .$e->getMessage();
		}

	}
	
}

//Utilities

/*check if array is associative*/
function isAssoc(array $array){
	return  !ctype_digit( implode('', array_keys($array) ) );
}

/*run sql queries and return array*/
function queryMYSQL($sql){
	$resultset = mysql_query($sql);
	
	if($resultset):
 
		if($resultset === true):
			return true;
		elseif(mysql_num_rows($resultset) > 0):
		
			while($result = mysql_fetch_assoc($resultset)){
				$results[] = $result;
			}
			mysql_free_result($resultset);
			
			return $results;
		else:
			throw new Exception(mysql_error());
		endif;
	else:
		throw new Exception(mysql_error());
	endif;
}

/*print array as table */
function html_show_array($array){

	$colmuns = array_keys($array[0]);
	
	$numberOfColmuns = count($colmuns);
	

	echo '<table border="1">';
	
	echo '<tr>';
		for($i = 0; $i < $numberOfColmuns; $i++) {
			echo '<td>' . $colmuns[$i] . '</td>';
		}
	echo '</tr>'; 
		
	for($rows = 0; $rows < count($array); $rows++) {
		echo '<tr>';
			for($i = 0; $i < $numberOfColmuns; $i++) {
				echo '<td>' . $array[$rows][$colmuns[$i]] . '</td>';
			}
		echo '</tr>';       
	}
	echo '</table>';

} 

?>