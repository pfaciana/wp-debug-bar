<?php

namespace DebugBar\Routine;

class Stream
{
	protected int $defaultExecTime;
	protected string $defaultMemoryLimit;
	protected int $startTime;

	public function __construct ( $defaultExecTime = 720, $defaultMemoryLimit = '512M' )
	{
		$this->defaultExecTime    = (int) $defaultExecTime;
		$this->defaultMemoryLimit = $defaultMemoryLimit;
		$this->start_time         = WP_START_TIMESTAMP ?: microtime( TRUE );
	}

	public function start ()
	{
		if ( (int) ini_get( 'memory_limit' ) < (int) $this->defaultMemoryLimit ) {
			ini_set( 'memory_limit', $this->defaultMemoryLimit );
		}

		if ( (int) ini_get( 'max_execution_time' ) < $this->defaultExecTime ) {
			set_time_limit( $this->defaultExecTime );
		}

		$this->output( 'Memory limit set: ' . $this->getMemoryLimit() . '. Max execution time set: ' . $this->getMaxExecutionTime() . ' seconds.', 2 );
	}

	public function stop ()
	{
		$this->output( 'Peak memory usage: ' . $this->getPeakMemory() . ' MB. Execution time: ' . $this->getTimeElapsed() . ' seconds.', TRUE, 2 );
		wp_die();
	}

	public function getTimeElapsed ( $precision = 3, $fallbackStartTime = NULL )
	{
		if ( empty( $this->startTime ) && empty( $fallbackStartTime ) ) {
			return FALSE;
		}

		$timeElapsed = microtime( TRUE ) - ( $this->startTime ?: $fallbackStartTime );

		return $precision === FALSE ? $timeElapsed : number_format( $timeElapsed, $precision ?? 3 );
	}

	public function getTimeRemaining ( $maxExecutionTime = NULL, $fallbackStartTime = NULL )
	{
		if ( ( $timeElapsed = $this->getTimeElapsed( $fallbackStartTime ) ) === FALSE ) {
			return FALSE;
		}

		if ( empty( $maxExecutionTime ) || $maxExecutionTime < 0 ) {
			$maxExecutionTime = $this->getMaxExecutionTime();
		}

		return $maxExecutionTime - $timeElapsed;
	}

	public function getMaxExecutionTime ()
	{
		return (int) ( ini_get( 'max_execution_time' ) ?? $this->defaultExecTime );
	}

	public function getMemory ( $real_usage = FALSE, $inMB = TRUE )
	{
		$memory = memory_get_usage( $real_usage );

		return $inMB ? round( $memory / 1024 / 1024 ) : $memory;
	}

	public function getPeakMemory ( $real_usage = TRUE, $inMB = TRUE )
	{
		$peakMemory = memory_get_peak_usage( $real_usage );

		return $inMB ? round( $peakMemory / 1024 / 1024 ) : $peakMemory;
	}

	public function getMemoryLimit ()
	{
		return ini_get( 'memory_limit' ) ?? $this->defaultMemoryLimit;
	}

	public function send ( $newLineAfter = FALSE, $newLineBefore = FALSE )
	{
		$this->output( ob_get_clean(), $newLineAfter, $newLineBefore );
	}

	public function output ( $content, $newLineAfter = TRUE, $newLineBefore = FALSE )
	{
		if ( !wp_doing_ajax() || wp_doing_cron() ) {
			return ob_clean() && FALSE;
		}

		if ( $newLineBefore ) {
			for ( $i = 0; $i < (int) $newLineBefore; $i++ ) {
				echo "\n";
			}
		}
		echo is_scalar( $content ) ? $content : json_encode( $content, JSON_PRETTY_PRINT );
		if ( $newLineAfter ) {
			for ( $i = 0; $i < (int) $newLineAfter; $i++ ) {
				echo "\n";
			}
		}
		ob_flush();
		flush();

		return TRUE;
	}
}