<?php
class BaseObject
{
	private $_parent;
	
	public function getType()
	{
		return getVarType($this);
	}	

	private function getVars($private=false)
	{
		return objToArray($this, $private);
	}	
		
	public function getMethods()
	{
		return get_class_methods($this->getType());
	}

	public function get($name)
	{
		$vars=$this->getVars(true);
		return $vars[$name];
	}

//merge properties from array
	public function getMultiple($keys)
	{
		if(!$keys) return false;

		$data=array();
		if(is_string($keys))
			$keys = explode(",", $keys);

		foreach ($keys as $key)
			if(property_exists($this, $key))
				$data[$key] = $this->$key;

		return $data;
	}


	public function setMultiple($data)
	{
		if(!$data) return false;
		foreach ($data as $key => $value)
			$this->$key = $value;
	}
	
	//Serialize to XML. output private variables?
	public function toXml($indent=0,$includeEmpty=false,$private=true, $outputAttributes=true)
	{
		return xmlObject("", $this, $indent, $includeEmpty, $private, $outputAttributes);
	}
	
	//Serialize to JSON. output private variables?
	public function toJson($indent=0,$includeEmpty=false,$private=true)
	{
		return jsObject($this, $indent, $includeEmpty, $private);
	}
}
?>