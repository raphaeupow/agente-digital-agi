<?php

namespace robot\tools;


/**
* phpagi.php : PHP AGI Functions for Asterisk
* Website: http://phpagi.sourceforge.net/
*
* $Id: phpagi.php,v 2.20 2010/09/30 02:21:00 masham Exp $
*
* Copyright (c) 2003 - 2010 Matthew Asham <matthew@ochrelabs.com>, David Eder <david@eder.us> and others
* All Rights Reserved.
*
* This software is released under the terms of the GNU Lesser General Public License v2.1
* A copy of which is available from http://www.gnu.org/copyleft/lesser.html
*
* We would be happy to list your phpagi based application on the phpagi
* website.  Drop me an Email if you'd like us to list your program.
* 
*
* Written for PHP 4.3.4, should work with older PHP 4.x versions.
*
* Please submit bug reports, patches, etc to http://sourceforge.net/projects/phpagi/
* Gracias. :)
*
*
* @package phpAGI
* @version 2.20
*/


define('AST_CONFIG_DIR', '/etc/asterisk/');
define('AST_SPOOL_DIR', '/var/spool/asterisk/');
define('AST_TMP_DIR',  '/tmp/');
define('DEFAULT_PHPAGI_CONFIG', AST_CONFIG_DIR . '/phpagi.conf');

define('AST_DIGIT_ANY', '0123456789#*');

define('AGIRES_OK', 200);

define('AST_STATE_DOWN', 0);
define('AST_STATE_RESERVED', 1);
define('AST_STATE_OFFHOOK', 2);
define('AST_STATE_DIALING', 3);
define('AST_STATE_RING', 4);
define('AST_STATE_RINGING', 5);
define('AST_STATE_UP', 6);
define('AST_STATE_BUSY', 7);
define('AST_STATE_DIALING_OFFHOOK', 8);
define('AST_STATE_PRERING', 9);

define('AUDIO_FILENO', 3); // STDERR_FILENO + 1

/**
* AGI class
*
* @package phpAGI
* @link http://www.voip-info.org/wiki-Asterisk+agi
* @example examples/dtmf.php Get DTMF tones from the user and say the digits
* @example examples/input.php Get text input from the user and say it back
* @example examples/ping.php Ping an IP address
*/
class Agi
{
    /**
    * Request variables read in on initialization.
    *
    * Often contains any/all of the following:
    *   agi_request - name of agi script
    *   agi_channel - current channel
    *   agi_language - current language
    *   agi_type - channel type (SIP, ZAP, IAX, ...)
    *   agi_uniqueid - unique id based on unix time
    *   agi_callerid - callerID string
    *   agi_dnid - dialed number id
    *   agi_rdnis - referring DNIS number
    *   agi_context - current context
    *   agi_extension - extension dialed
    *   agi_priority - current priority
    *   agi_enhanced - value is 1.0 if started as an EAGI script
    *   agi_accountcode - set by SetAccount in the dialplan
    *   agi_network - value is yes if this is a fastagi
    *   agi_network_script - name of the script to execute
    *
    * NOTE: program arguments are still in $_SERVER['argv'].
    *
    * @var array
    * @access public
    */
    var $request;

    /**
    * Config variables
    *
    * @var array
    * @access public
    */
    var $config;

    /**
    * Asterisk Manager
    *
    * @var AGI_AsteriskManager
    * @access public
    */
    var $asmanager;

    /**
    * Input Stream
    *
    * @access private
    */
    var $in = NULL;

    /**
    * Output Stream
    *
    * @access private
    */
    var $out = NULL;

    /**
    * Audio Stream
    *
    * @access public
    */
    var $audio = NULL;


    /**
    * Application option delimiter
    * 
    * @access public
    */
    public $option_delim = ",";
    
    /**
    * Constructor
    *
    * @param string $config is the name of the config file to parse
    * @param array $optconfig is an array of configuration vars and vals, stuffed into $this->config['phpagi']
    */
    function __construct($config=NULL, $optconfig=array())
    {
        // load config
        if(!is_null($config) && file_exists($config))
          $this->config = parse_ini_file($config, true);
        elseif(file_exists(DEFAULT_PHPAGI_CONFIG))
          $this->config = parse_ini_file(DEFAULT_PHPAGI_CONFIG, true);

        // If optconfig is specified, stuff vals and vars into 'phpagi' config array.
        foreach($optconfig as $var=>$val)
          $this->config['phpagi'][$var] = $val;

        // add default values to config for uninitialized values
        if(!isset($this->config['phpagi']['debug'])) $this->config['phpagi']['debug'] = false;
        if(!isset($this->config['phpagi']['admin'])) $this->config['phpagi']['admin'] = NULL;
        if(!isset($this->config['phpagi']['tempdir'])) $this->config['phpagi']['tempdir'] = AST_TMP_DIR;

        ob_implicit_flush(true);

        // open stdin & stdout
        $this->in = defined('STDIN') ? STDIN : fopen('php://stdin', 'r');
        $this->out = defined('STDOUT') ? STDOUT : fopen('php://stdout', 'w');

        // make sure temp folder exists
        $this->make_folder($this->config['phpagi']['tempdir']);

        // read the request
        $str = fgets($this->in);
        while($str != "\n")
        {
          $this->request[substr($str, 0, strpos($str, ':'))] = trim(substr($str, strpos($str, ':') + 1));
          $str = fgets($this->in);
        }

        // open audio if eagi detected
        if(@$this->request['agi_enhanced'] == '1.0')
        {
          if(file_exists('/proc/' . getmypid() . '/fd/3'))
            $this->audio = fopen('/proc/' . getmypid() . '/fd/3', 'r');
          elseif(file_exists('/dev/fd/3'))
          {
            // may need to mount fdescfs
            $this->audio = fopen('/dev/fd/3', 'r');
          }
          else
            $this->conlog('Unable to open audio stream');

          if($this->audio) stream_set_blocking($this->audio, 0);
        }

        $this->conlog('AGI Request:');
        $this->conlog(print_r($this->request, true));
        $this->conlog('PHPAGI internal configuration:');
        $this->conlog(print_r($this->config, true));
    }

    // *********************************************************************************************************
    // **                             COMMANDS                                                                                            **
    // *********************************************************************************************************

    /**
    * Answer channel if not already in answer state.
    *
    * @link http://www.voip-info.org/wiki-answer
    * @example examples/dtmf.php Get DTMF tones from the user and say the digits
    * @example examples/input.php Get text input from the user and say it back
    * @example examples/ping.php Ping an IP address
    *
    * @return array, see evaluate for return information.  ['result'] is 0 on success, -1 on failure.
    */
    function answer()
    {
        return $this->evaluate('ANSWER');
    }


    public function getPhone()
    {
        return isset($this->request['agi_callerid']) ? $this->request['agi_callerid'] : null;
    }


    public function getUniqueId()
    {
        return isset($this->request['agi_uniqueid']) ? $this->request['agi_uniqueid'] : null;
    }

    public function isTest()
    {
        return $this->get_variable('test')['data'];
    }

    /**
    * Get the status of the specified channel. If no channel name is specified, return the status of the current channel.
    *
    * @link http://www.voip-info.org/wiki-channel+status
    * @param string $channel
    * @return array, see evaluate for return information. ['data'] contains description.
    */
    function channel_status($channel='')
    {
        $ret = $this->evaluate("CHANNEL STATUS $channel");
        switch($ret['result'])
        {
          case -1: $ret['data'] = trim("There is no channel that matches $channel"); break;
          case AST_STATE_DOWN: $ret['data'] = 'Channel is down and available'; break;
          case AST_STATE_RESERVED: $ret['data'] = 'Channel is down, but reserved'; break;
          case AST_STATE_OFFHOOK: $ret['data'] = 'Channel is off hook'; break;
          case AST_STATE_DIALING: $ret['data'] = 'Digits (or equivalent) have been dialed'; break;
          case AST_STATE_RING: $ret['data'] = 'Line is ringing'; break;
          case AST_STATE_RINGING: $ret['data'] = 'Remote end is ringing'; break;
          case AST_STATE_UP: $ret['data'] = 'Line is up'; break;
          case AST_STATE_BUSY: $ret['data'] = 'Line is busy'; break;
          case AST_STATE_DIALING_OFFHOOK: $ret['data'] = 'Digits (or equivalent) have been dialed while offhook'; break;
          case AST_STATE_PRERING: $ret['data'] = 'Channel has detected an incoming call and is waiting for ring'; break;
          default: $ret['data'] = "Unknown ({$ret['result']})"; break;
        }
        return $ret;
    }

    /**
    * Executes the specified Asterisk application with given options.
    *
    * @link http://www.voip-info.org/wiki-exec
    * @link http://www.voip-info.org/wiki-Asterisk+-+documentation+of+application+commands
    * @param string $application
    * @param mixed $options
    * @return array, see evaluate for return information. ['result'] is whatever the application returns, or -2 on failure to find application
    */
    function exec($application, $options)
    {
        if(is_array($options)) $options = join('|', $options);
        return $this->evaluate("EXEC $application $options");
    }

    function channelIsUp():bool
    {
      $this->verbose("Verificando se o canal está ativo : ". $this->channel_status()["code"]);
      if ($this->channel_status()["code"] == 511)
          return true;
      return false;
    }

    /**
    * Fetch the value of a variable.
    *
    * Does not work with global variables. Does not work with some variables that are generated by modules.
    *
    * @link http://www.voip-info.org/wiki-get+variable
    * @link http://www.voip-info.org/wiki-Asterisk+variables
    * @param string $variable name
    * @param boolean $getvalue return the value only
    * @return array, see evaluate for return information. ['result'] is 0 if variable hasn't been set, 1 if it has. ['data'] holds the value. returns value if $getvalue is TRUE
    */
    function get_variable($variable,$getvalue=FALSE)
    {
        $res=$this->evaluate("GET VARIABLE $variable");

        if($getvalue==FALSE)
          return($res);

        return($res['data']);
    }


    /**
    * Hangup the specified channel. If no channel name is given, hang up the current channel.
    *
    * With power comes responsibility. Hanging up channels other than your own isn't something
    * that is done routinely. If you are not sure why you are doing so, then don't.
    *
    * @link http://www.voip-info.org/wiki-hangup
    * @example examples/dtmf.php Get DTMF tones from the user and say the digits
    * @example examples/input.php Get text input from the user and say it back
    * @example examples/ping.php Ping an IP address
    *
    * @param string $channel
    * @return array, see evaluate for return information. ['result'] is 1 on success, -1 on failure.
    */
    function hangup($channel='')
    {
        return $this->evaluate("HANGUP $channel");
    }

    /**
    * Record sound to a file until an acceptable DTMF digit is received or a specified amount of
    * time has passed. Optionally the file BEEP is played before recording begins.
    *
    * @link http://www.voip-info.org/wiki-record+file
    * @param string $file to record, without extension, often created in /var/lib/asterisk/sounds
    * @param string $format of the file. GSM and WAV are commonly used formats. MP3 is read-only and thus cannot be used.
    * @param string $escape_digits
    * @param integer $timeout is the maximum record time in milliseconds, or -1 for no timeout.
    * @param integer $offset to seek to without exceeding the end of the file.
    * @param boolean $beep
    * @param integer $silence number of seconds of silence allowed before the function returns despite the 
    * lack of dtmf digits or reaching timeout.
    * @return array, see evaluate for return information. ['result'] is -1 on error, 0 on hangup, otherwise a decimal value of the 
    * DTMF tone. Use chr() to convert to ASCII.
    */
    function record_file($file,  $escape_digits='', $timeout=-1,  $silence=3, $timeSilenceBetweenSpeech=NULL)
    {
        $cmd = trim("RECORD FILE $file wav \"$escape_digits\" ".$timeout*1000);
        if(!is_null($silence)) $cmd .= " s=$silence ";
        if(!is_null($timeSilenceBetweenSpeech)) $cmd .= " z=$timeSilenceBetweenSpeech";
        return $this->evaluate($cmd);
    }
    /**
    * Sets a variable to the specified value. The variables so created can later be used by later using ${<variablename>}
    * in the dialplan.
    *
    * These variables live in the channel Asterisk creates when you pickup a phone and as such they are both local and temporary. 
    * Variables created in one channel can not be accessed by another channel. When you hang up the phone, the channel is deleted 
    * and any variables in that channel are deleted as well.
    *
    * @link http://www.voip-info.org/wiki-set+variable
    * @param string $variable is case sensitive
    * @param string $value
    * @return array, see evaluate for return information.
    */
    function set_variable($variable, $value)
    {
        $value = str_replace("\n", '\n', addslashes($value));
        return $this->evaluate("SET VARIABLE $variable \"$value\"");
    }

    /**
    * Play the given audio file, allowing playback to be interrupted by a DTMF digit. This command is similar to the GET DATA 
    * command but this command returns after the first DTMF digit has been pressed while GET DATA can accumulated any number of 
    * digits before returning.
    *
    * @example examples/ping.php Ping an IP address
    *
    * @link http://www.voip-info.org/wiki-stream+file
    * @param string $filename without extension, often in /var/lib/asterisk/sounds
    * @param string $escape_digits
    * @param integer $offset
    * @return array, see evaluate for return information. ['result'] is -1 on hangup or error, 0 if playback completes with no 
    * digit received, otherwise a decimal value of the DTMF tone.  Use chr() to convert to ASCII.
    */
    function stream_file($filename, $escape_digits='', $offset=0)
    {
        return $this->evaluate("STREAM FILE $filename \"$escape_digits\" $offset");
    }

 

    /**
    * Sends $message to the Asterisk console via the 'verbose' message system.
    *
    * If the Asterisk verbosity level is $level or greater, send $message to the console.
    *
    * The Asterisk verbosity system works as follows. The Asterisk user gets to set the desired verbosity at startup time or later 
    * using the console 'set verbose' command. Messages are displayed on the console if their verbose level is less than or equal 
    * to desired verbosity set by the user. More important messages should have a low verbose level; less important messages 
    * should have a high verbose level.
    *
    * @link http://www.voip-info.org/wiki-verbose
    * @param string $message
    * @param integer $level from 1 to 4
    * @return array, see evaluate for return information.
    */
    function verbose($message, $level=1)
    {
        foreach(explode("\n", str_replace("\r\n", "\n", print_r($message, true))) as $msg)
        {
          @syslog(LOG_WARNING, $msg);
          $ret = $this->evaluate("VERBOSE \"$msg\" $level");
        }
        return $ret;
    }

    /**
    * Waits up to $timeout milliseconds for channel to receive a DTMF digit.
    *
    * @link http://www.voip-info.org/wiki-wait+for+digit
    * @param integer $timeout in millisecons. Use -1 for the timeout value if you want the call to wait indefinitely.
    * @return array, see evaluate for return information. ['result'] is 0 if wait completes with no 
    * digit received, otherwise a decimal value of the DTMF tone.  Use chr() to convert to ASCII.
    */
    function wait_for_digit($timeout=-1)
    {
        return $this->evaluate("WAIT FOR DIGIT $timeout");
    }


    // *********************************************************************************************************
    // **                             APPLICATIONS                                                                                        **
    // *********************************************************************************************************

 
    /**
    * Executes an AGI compliant application.
    *
    * @param string $command
    * @return array, see evaluate for return information. ['result'] is -1 on hangup or if application requested hangup, or 0 on non-hangup exit.
    * @param string $args
    */
    function exec_agi($command, $args)
    {
        return $this->exec("AGI $command", $args);
    }


    /**
    * Dial.
    *
    * Dial takes input from ${VXML_URL} to send XML Url to Cisco 7960
    * Dial takes input from ${ALERT_INFO} to set ring cadence for Cisco phones
    * Dial returns ${CAUSECODE}: If the dial failed, this is the errormessage.
    * Dial returns ${DIALSTATUS}: Text code returning status of last dial attempt.
    *
    * @link http://www.voip-info.org/wiki-Asterisk+cmd+Dial
    * @param string $type
    * @param string $identifier
    * @param integer $timeout
    * @param string $options
    * @param string $url
    * @return array, see evaluate for return information.
    */
    function exec_dial($type, $identifier, $timeout=NULL, $options=NULL, $url=NULL)
    {
        return $this->exec('Dial', trim("$type/$identifier".$this->option_delim.$timeout.$this->option_delim.$options.$this->option_delim.$url, $this->option_delim));
    }


    /**
    * Make a folder recursively.
    *
    * @access private
    * @param string $folder
    * @param integer $perms
    * @return boolean 
    */
    function make_folder($folder, $perms=0755)
    {
        $f = explode(DIRECTORY_SEPARATOR, $folder);
        $base = '';
        for($i = 0; $i < count($f); $i++)
        {
          $base .= $f[$i];
          if($f[$i] != '' && !file_exists($base)) {
            if(mkdir($base, $perms)==FALSE){
              return(FALSE);
            }
          }
          $base .= DIRECTORY_SEPARATOR;
        }
        return(TRUE);
    }	

    // *********************************************************************************************************
    // **                             PRIVATE                                                                                             **
    // *********************************************************************************************************


    /**
    * Evaluate an AGI command.
    *
    * @access private
    * @param string $command
    * @return array ('code'=>$code, 'result'=>$result, 'data'=>$data)
    */
    function evaluate($command)
    {
        $broken = array('code'=>500, 'result'=>-1, 'data'=>'');

        // write command
        if(!@fwrite($this->out, trim($command) . "\n")) return $broken;
        fflush($this->out);

        // Read result.  Occasionally, a command return a string followed by an extra new line.
        // When this happens, our script will ignore the new line, but it will still be in the
        // buffer.  So, if we get a blank line, it is probably the result of a previous
        // command.  We read until we get a valid result or asterisk hangs up.  One offending
        // command is SEND TEXT.
        $count = 0;
        do
        {
          $str = trim(fgets($this->in, 4096));
        } while($str == '' && $count++ < 5);

        if($count >= 5)
        {
    //          $this->conlog("evaluate error on read for $command");
          return $broken;
        }

        // parse result
        $ret['code'] = substr($str, 0, 3);
        $str = trim(substr($str, 3));

        if($str[0] == '-') // we have a multiline response!
        {
          $count = 0;
          $str = substr($str, 1) . "\n";
          $line = fgets($this->in, 4096);
          while(substr($line, 0, 3) != $ret['code'] && $count < 5)
          {
            $str .= $line;
            $line = fgets($this->in, 4096);
            $count = (trim($line) == '') ? $count + 1 : 0;
          }
          if($count >= 5)
          {
    //            $this->conlog("evaluate error on multiline read for $command");
            return $broken;
          }
        }

        $ret['result'] = NULL;
        $ret['data'] = '';
        if($ret['code'] != AGIRES_OK) // some sort of error
        {
          $ret['data'] = $str;
          $this->conlog(print_r($ret, true));
        }
        else // normal AGIRES_OK response
        {
          $parse = explode(' ', trim($str));
          $in_token = false;
          foreach($parse as $token)
          {
            if($in_token) // we previously hit a token starting with ')' but not ending in ')'
            {
                $ret['data'] .= ' ' . trim($token, '() ');
                if($token[strlen($token)-1] == ')') $in_token = false;
            }
            elseif($token[0] == '(')
            {
                if($token[strlen($token)-1] != ')') $in_token = true;
                $ret['data'] .= ' ' . trim($token, '() ');
            }
            elseif(strpos($token, '='))
            {
                $token = explode('=', $token);
                $ret[$token[0]] = $token[1];
            }
            elseif($token != '')
                $ret['data'] .= ' ' . $token;
          }
          $ret['data'] = trim($ret['data']);
        }

        // log some errors
        if($ret['result'] < 0)
          $this->conlog("$command returned {$ret['result']}");

        return $ret;
    }


    /**
     * Gets the value of the specified SIP header key
     * 
     * @param string $key The SIP header key to retrieve
     * @return string|null The value of the SIP header or null if not found
     */
    function get_sip_header($key)
    {
        $result = $this->evaluate("GET VARIABLE SIP_HEADER($key)");
        if(isset($result['value']))
            return $result['value'];
        return null;
    }

    /**
     * Gets the keyid value from SIP headers
     * 
     * @return string|null The keyid value or null if not found
     */
    function getKeyId()
    {
        return $this->get_variable('key_id')['data'];
    }

    /**
     * Executes a transfer to the specified extension
     * 
     * @param string $extension Extension to transfer to
     * @param string $type Type of transfer (attended/blind)
     * @return array Result of the transfer operation
     */
    function transfer($extension, $type = 'attended')
    {
        return $this->exec('transfer', [$extension, $type]);
    }

    /**
    * Log to console if debug mode.
    *
    * @example examples/ping.php Ping an IP address
    *
    * @param string $str
    * @param integer $vbl verbose level
    */
    function conlog($str, $vbl=1)
    {
        static $busy = false;

        if($this->config['phpagi']['debug'] != false)
        {
          if(!$busy) // no conlogs inside conlog!!!
          {
            $busy = true;
            $this->verbose($str, $vbl);
            $busy = false;
          }
        }
    }
  }