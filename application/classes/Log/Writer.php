<?php

/**
 * This is only here until Kohana 3.3.1 is released.
 */
class Log_Writer extends Kohana_Log_Writer {

	public function write(array $messages)
	{
		parent::write($messages);
	}

	/**
	 * Formats a log entry.
	 *
	 * @param array $message
	 * @param string $format
	 * @return string
	 */
	public function format_message(array $message, $format = "time --- level: body in file:line")
	{
		$message['time'] = Date::formatted_time('@'.$message['time'], Log_Writer::$timestamp, Log_Writer::$timezone, TRUE);
		$message['level'] = $this->_log_levels[$message['level']];

		$string = strtr($format, array_filter($message, 'is_scalar'));

		if (isset($message['additional']['exception']))
		{
			// Re-use as much as possible, just resetting the body to the trace
			$message['body'] = $message['additional']['exception']->getTraceAsString();
			$message['level'] = $this->_log_levels[Log_Writer::$strace_level];

			$string .= PHP_EOL.strtr($format, array_filter($message, 'is_scalar'));
		}

		return $string;
	}

}

