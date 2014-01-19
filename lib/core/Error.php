<?php

namespace Core;

abstract class ErrorAbstract {
    private $parent;

	public final function __construct(&$parent){
		$this->parent = $parent;
	}

    /**
     * Halt on error...
     * @abstract
     * @param $details Details of the error
     * @param $stop Should we halt execution?
     * @return void
     */
    public abstract function halt($details, $stop);

    /**
     * Exception fail?
     * @abstract
     * @param Exception $exception Final exception
     * @return void
     */
    public abstract function haltException(\Exception $exception);
}

class ErrorLive extends ErrorAbstract {
    public function halt($details, $stop) {
        $this->publicDisplay();
        exit();
    }

    public function haltException(\Exception $exception) {
        $this->publicDisplay();
        exit();
    }

    public function publicDisplay() {
        ob_clean();
        header('HTTP/1.1 500 Internal Server Error');
        echo "<h2>", Config::SHORT_NAME," has encountered a fatal error</h2>";
        echo "<b>Please try refreshing the page.</b>";
        echo "<b>The system administrators have been informed.</b>";
    }
}

class ErrorDevelopment extends ErrorAbstract {
	public function halt($details, $stop){
		// Uh oh.. An error has occurred somewhere on the website. Clean the pipes..
		ob_clean();
		// Ready the troops!
		echo "<h2>Uh oh! A problem has been encountered...</h2>";
		echo "<b>Core Error:</b> $details<br/><br/>";
		echo "<b>Trace:</b><br>";
		echo "<table style='margin-left:5px'>";
        array_walk( debug_backtrace(), function($a,$b) {
                $fPath = basename( $a['file'] );
                $fLine = $a['line'];
                $fFunction = $a['function'];
                $fFile = $a['file'];
                print "
                <tr>
                    <td>$fPath</td>
                    <td><span style=\"color:red;\">$fLine</span>
                    <td><span style=\"color:green;\">$fFunction</span>
                    <td>$fFile</td>
                </tr>";
            });
		echo "</table>";

		if($stop)
			die;
	}

    public function haltException(\Exception $exception) {
		// Uh oh.. An error has occurred somewhere on the website. Clean the pipes..
		//ob_clean();
		// Ready the troops!
        
		echo "<h2>Uh oh! A problem has been encountered...</h2>";
        
        $previousEx = $exception;

        while (!is_null($previousEx)) {
            $message = $previousEx->getCode() . ': ' . $previousEx->getMessage();
            echo "<b>Core Exception:</b> $message<br/><br/>";
            echo "<b>Trace:</b><br><pre>";
            echo $previousEx->getTraceAsString();
            echo "</pre>";
            echo "<br><br>";
            $previousEx = $exception->getPrevious();
        }

        // Always die: the exception should never reach this point.
        die;
    }
	
}

if(Config::DEBUG_MODE) {
    class Error extends ErrorDevelopment {}
} else {
    class Error extends ErrorLive {}
}

?>