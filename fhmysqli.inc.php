<?php

class SqlException extends Exception
{
}

class FH_mysqli extends mysqli
{
	const OUTPUT_QUERY = TRUE;

	public function queryElseDie($query, $resultmode = MYSQLI_STORE_RESULT)
	{
		$result = parent::query($query, $resultmode);
		if ($result === false)
		{
			throw new SqlException($this->buildExceptionMessage($query));
		}
		return $result;
	}

	public function prepareElseDie($query)
	{
		$result = parent::prepare($query);
		if ($result === false)
		{
			throw new SqlException($this->buildExceptionMessage($query));
		}
		return $result;
	}

	protected function buildExceptionMessage(&$query)
	{
		$ret = 'SQL-Error: (' . $this->errno . ') ' . $this->error;
		if (self::OUTPUT_QUERY)
		{
			$ret .= ' (Query: \"' . $query . '\")';
		}
		return $ret;
	}
}
