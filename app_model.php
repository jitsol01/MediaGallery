<?php
class AppModel extends Model {

	function checkUnique($myUnique)	{
		$sql = "";
		foreach($myUnique as $name ) {
			if($sql!="") $sql.=' OR ';
			$sql .= $this->name.".$name=\"".$this->data[$this->name][$name].'"';
		}
		
		$found = $this->find($sql);
		$same = isset($this->id) && $found[$this->name]['id'] == $this->id;
		return !$found || $found && $same;
	}
}
?>
