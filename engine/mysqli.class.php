<?php
/**
 * @In the name of God!
 * @author: Apadana CMS Development Team
 * @email: info@apadanacms.ir
 * @link: http://www.apadanacms.ir
 * @license: http://www.gnu.org/licenses/
 * @copyright: Copyright © 2012-2014 ApadanaCms.ir. All rights reserved.
 * @Apadana CMS is a Free Software
 */
//NEW FILE (IN 1.2)
defined('security') or exit('Direct Access to this location is not allowed.');

class database
{
    public $connect = null;
    public $query_id = null;
    public $num_queries = 0;
    public $query_time = 0;
    public $error_reporting = false;
    public $prefix = null;
    public $result = null;
    public $charset = null;
    public $save_queries = false;
    public $queries = array();

	public function connect($db) 
	{
		$this->save_queries = defined('database_save_queries') && database_save_queries? true : false;
		
		if (error_reporting) 
		{
			$this->connect = mysqli_connect($db['host'], $db['user'], $db['password'], $db['name']);
			$this->error_reporting = true;
		} 
		else 
		{
			$this->connect = @mysqli_connect($db['host'], $db['user'], $db['password'], $db['name']);
		}

		if (!$this->connect) 
		{
			exit('Unable to establish connection to mysqli!');
			return;
		}
		@mysqli_query($this->connect,'SET CHARACTER SET '.$this->charset.';');
		@mysqli_query($this->connect,'SET SESSION collation_connection="'.$this->charset.'_general_ci"');		
		mysqli_set_charset($this->connect,'utf8');
		register_shutdown_function(array(&$this, 'close'));
	    $this->prefix = $db['prefix'];
	    $this->charset = $db['charset'];
		$db = null;
	}
	
	public function select($name) 
	{
	/*	if (!@mysqli_select_db($name, $this->connect)) 
		{
			exit('Unable to select mysqli database!');
		}*/
		@mysqli_query($this->connect,'SET CHARACTER SET '.$this->charset.';');
		@mysqli_query($this->connect,'SET SESSION collation_connection="'.$this->charset.'_general_ci"');		
		mysqli_set_charset($this->connect,'utf8');
		register_shutdown_function(array(&$this, 'close'));
	}	

	protected function replace_prefix($sql, $prefix = '#__') 
	{
		$done = null;
		$single = false;
		$double = false;
		$found = false;
		$i = 0;
		while (strlen($sql) > 0)
		{
			if ($sql[$i] == null) return $done.$sql;
			if (($sql[$i] == "'") && $sql[$i-1] !='\\')
				$single = !$single;
			if (($sql[$i] == '"') && $sql[$i-1] !='\\')
				$double = !$double;

			if ($sql[$i] == $prefix[0] && !$single && !$double)
			{
				$found = true;
				for ($j=0; $j < strlen($prefix); $j++)
				{
					if ($sql[$i+$j] != $prefix[$j])
					{
						$found = false;
						break;
					}
				}
			}
			if ($found)
			{
				$done .= substr($sql, 0, $i).$this->prefix;
				$sql = substr($sql, $i+$j);
				$found = false;
				$i = 0;
			}
			else
				$i++;
			if ($i >= strlen($sql)) return $done.$sql;
		}
		return $done;
	}	
	
	public function query($string = null, $hide_errors = true, $prefix = '#__')
	{
		unset($this->result);

		if ($string != '')
        {
		    $this->get_execution_time();
			$this->result = @mysqli_query($this->connect,$this->replace_prefix($string, $prefix));
			
		    if ($this->error_number() && ($this->error_reporting || !$hide_errors))
		    {
			    $this->error($string);
		    }
			
		    $query_time = $this->get_execution_time();
			
			if ($this->save_queries)
			{$string=$this->replace_prefix($string, $prefix);
			    $this->queries[] = array(
			        'query' => preg_replace('/([0-9a-f]){32}/', '********************************', $string), /* Hides all hashes */
			        'result' => $this->result,
			        'execution_time' => $query_time,
			        'error_number' => $this->error_number(),
			        'error_string' => $this->error_string()
			    );
			}
			
		    $this->query_time += $query_time;
            $this->num_queries++;
		}

		if ($this->result)
		{
			return $this->result;
		}
		
		return false;
	}

	public function fetch($query_id = false, $fetch = 'assoc', $set_query = false)
	{
		if ( !($fetch == 'array' || $fetch == 'assoc' || $fetch == 'object' || $fetch == 'row') )
		{
			echo ' <b>Fetch type must be one of: array, assoc, object, row</b>';
			return false;
		}
		$fetch = 'mysqli_fetch_'.$fetch;
			
		if (!$query_id)
		{
			$query_id = $this->result;
		}
		
		if ($set_query)
		{
			$query_id = $this->query($query_id);
		}
		
		if ($query_id)
		{
			return @$fetch($query_id);
		}
		else
		{
			return false;
		}
	}

	public function get_row($query, $fetch = 'assoc', $index = false)
	{
        $this->query($query);

		if ($this->numRows() <= 0)
		{
		    return false;
		}		
		
		$rows = array();
        while ($row = $this->fetch($this->result, $fetch))
        {
		    if (empty($index) || (!empty($index) && (($fetch == 'object' && !isset($row->$index)) || ($fetch != 'object' && !isset($row[$index])))))
		    {
		        $rows[] = $row;
		    }
			else
			{
		        
		        $rows[$fetch == 'object'? $row->$index : $row[$index]] = $row;
			}
        }		
		
        $this->freeResult();
		return $rows;
	}	
	
	public function insert($table, $array, $escape = true)
	{
		if (!is_array($array))
		{
			return false;
		}

		if ($escape)
		{
			$array = $this->escapeString($array);
		}
		
		$fields = "`".implode("`,`", array_keys($array))."`";
		$values = implode("','", $array);
		$this->query("
			INSERT
			INTO {$this->prefix}{$table} (".$fields.")
			VALUES ('".$values."')
		");
		
		return $this->insertID();
	}

	public function update($table, $array, $where = null, $limit = null, $escape = true, $no_quote = false)
	{
		if (!is_array($array))
		{
			return false;
		}

		if ($escape)
		{
			$array = $this->escapeString($array);
		}
	
		$comma = null;
		$query = null;
		$quote = "'";

		if ($no_quote == true)
		{
			$quote = "";
		}

		foreach ($array as $field => $value)
		{
			$query .= $comma."`".$field."`={$quote}{$value}{$quote}";
			$comma = ', ';
		}

		if (!empty($where))
		{
			$query .= " WHERE {$where}";
		}

		if (!empty($limit))
		{
			$query .= " LIMIT {$limit}";
		}

		return $this->query("
			UPDATE {$this->prefix}{$table}
			SET {$query}
		");
	}

	public function delete($table, $where = null, $limit = null)
	{
		$query = "";
		if (!empty($where))
		{
			$query .= " WHERE {$where}";
		}

		if (!empty($limit))
		{
			$query .= " LIMIT {$limit}";
		}

		return $this->query("
			DELETE
			FROM {$this->prefix}{$table}
			{$query}
		");
	}

	public function numRows($query_id = false, $set_query = false)
	{
		if (!$query_id)
		{
			$query_id = $this->result;
		}
		
		if ($set_query)
		{
			$query_id = $this->query($query_id);
		}
		
		if ($query_id)
		{
			$result = @mysqli_num_rows($query_id);
			return $result;
		}
		else
		{
			return false;
		}
	}

	public function affectedRows()
	{
		if ($this->connect)
		{
			return @mysqli_affected_rows($this->connect);
		}
		else
		{
			return false;
		}
	}

	public function freeResult($query_id = false)
    {
		if ( !$query_id )
		{
			$query_id = $this->result;
		}

		if ( $query_id )
		{
			@mysqli_free_result($query_id);
			return true;
		}
		else
		{
			return false;
		}
	}

	public function insertID()
    {
		if ($this->connect)
        {
			return @mysqli_insert_id($this->connect);
		}
        else
        {
			return false;
		}
	}	

	public function escapeString($string)
    {
        if (is_array($string))
		{
		    foreach ($string as $key => $str)
				$string[$key] = $this->escapeString($str);
		}
        elseif (is_object($string))
		{
		    foreach ($string as $key => $str)
				$string->{$key} = $this->escapeString($str);
		}
		else
		{
		    // if (get_magic_quotes_gpc())
		    // {
		    	// $string = stripslashes($string);
		    // }
		    //check if this function exists
		    if (function_exists('mysqli_real_escape_string') && $this->connect)
		    {       
		    	$string = mysqli_real_escape_string($this->connect,$string);
		    }
		    //for PHP version < 4.3.0 use addslashes
		    else
		    {
		    	$string = addslashes($string);
		    }
		}
		return $string;		
	}	
	
	public function get_execution_time()
    {
		static $time_start;
		$time = microtime(true);
		// Just starting timer, init and return
		if (!$time_start)
		{
			$time_start = $time;
			return;
		}
		// Timer has run, return execution time
		else
		{
			$total = $time-$time_start;
			$time_start = 0;
			if ($total < 0) $total = 0;
			return $total;
		}
	}	

	public function error_number()
    {
		if ($this->connect)
		{
			return @mysqli_errno($this->connect);
		}
		else
		{
			return @mysqli_errno();
		}
	}

	public function error_string()
    {
		if ($this->connect)
		{
			return @mysqli_error($this->connect);
		}
		else
		{
			return @mysqli_error();
		}
	}

	public function error($string = '')
	{
		if ($this->error_reporting)
		{
		    $string = trim($string);
		    $string = explode("\n", $string);
		    $string = array_map("trim", $string);
		    $string = implode("\n", $string);
			echo("\n<!-- ERROR SQL! -->\n<div style='background:#FFCCCC;border:#FF6A6A 1px solid;padding:5px;margin:5px;direction:ltr;text-align:left'>\n<strong>[SQL] [".$this->error_number()."] ".$this->error_string()."</strong><br />\n<b>STRING:</b><pre style=\"direction:ltr;text-align:left;overflow-x:auto;\">{$string}</pre>\n</div>\n<!-- Apadana CMS! -->\n\n");
		}
		else
		{
			return false;
		}
	}

	public function version() 
	{
		return preg_replace('/[^0-9.].*/', '', mysqli_get_server_info($this->result));
	}

	public function close() 
	{
		if ($this->connect)
		{
			if ($this->result)
			{
				@mysqli_free_result($this->result);
			}
			return @mysqli_close($this->connect);
		}
		else
		{
			return false;
		}		
	}	
}

?>