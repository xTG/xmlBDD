<?php

/**
 * class xmlBDDException
 * @author Baptiste ROUSSEL
 * @version 1.0
 */

class xmlBDDException extends Exception
{
	public function __construct($message)
	{
		$args = func_get_args();
		$vals = array();
		foreach($args as $key => $arg)
		{
			$vals[] = "%$key";
		}
		$message = str_replace($vals,$args,$message);
		parent::__construct($message);
	}
}

?>