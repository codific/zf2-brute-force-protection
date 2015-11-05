<?php
/**
 * BruteForce
 * prevent user to send to meny requests to server
 *
 * PHP version 5
 *
 * @author Nikolay Dyakov < nikolay@codific.eu >
 * @link   https://codific.eu
 */
namespace Codific;

/**
 * BruteForce
 * prevent user to send to meny requests to server
 *
 * @author Nikolay Dyakov < nikolay@codific.eu >
 * @link   https://codific.eu
 */
class BruteForce
{
    /**
     * Use when retrieving the number of recent failed logins.
     * In minutes.
     * @var int
     */
    private static $timeFrame = 10;
    
    /**
     * Threshold values
     * Example: for 5 failed logins user will be not allowed to login for next 60 seconds.
     * @var array
     */
    private static $threshold = array(
            5 => 60,
            10 => 120,
            20 => 240,
    );
    
    /**
     * database configuration
     * Example:
     * $databaseConfig = array(
     *       'host' => 'localhost',
     *       'dbname' => 'database name',
     *       'username' => 'username',
     *       'password' => 'password',
     * );
     * @var array
     */
    private static $databaseConfig = array();
    
    /**
     * Init database connection
     * @return \PDO
     */
    private static function _db()
    {
        $db = null;
        if(sizeof(self::$databaseConfig)>0)
            $db = new \PDO(
                    "mysql:host=".self::$databaseConfig['host'].";dbname=".self::$databaseConfig['dbname'],
                    self::$databaseConfig['username'],
                    self::$databaseConfig['password']
            );
        else
        {
            $local = include($_SERVER['DOCUMENT_ROOT'].'/../config/autoload/local.php');
            $db = new \PDO($local['db']['dsn'],$local['db']['username'],$local['db']['password']);
        }
        $db->setAttribute(\PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES \'UTF8\'');
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        return $db;
    }
    
    /**
     * Return user ip address
     * @return string
     */
    private static function getIP()
    {
        $ipaddress = '';
        if(getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if(getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
    
    /**
     * Add failed login record to database.
     * @param string $username Username.
     * @return void
     */
    public static function addFailedLogin($username)
    {
        $db = self::_db();
        $stmt = $db->prepare("INSERT INTO user_failed_login(`username`,`ip`, `timestamp`) VALUES(:username,:ip, NOW())");
        $stmt->execute(array(
                ':username'=>$username,
                ':ip'=>self::getIP(),
        ));
    }
    
    /**
     * Get login delay in seconds
     * @return integer
     */
    public static function getLoginDelay() 
    {
        // Get db connection
        $db = self::_db();
        // Get number of failed attempts and timestap for last failed attempt
        $stmt = $db->prepare("SELECT COUNT(id) as `count`, MAX(timestamp) as `lastDate` FROM user_failed_login WHERE ip = :ip AND timestamp > DATE_SUB(NOW(), INTERVAL :timeframe MINUTE)");
        $stmt->execute(array(
                ':ip'=>self::getIP(),
                ':timeframe'=>self::$timeFrame,
        ));
        $row = $stmt->fetch();
        // Get count of last failed logins
        $failedAttempts = $row['count'];
        // Get timestamp of last failed login
        $lastFailedTimestamp = strtotime($row['lastDate']);
        
        krsort(self::$threshold);
        foreach (self::$threshold as $attempts => $delay)
            if ($failedAttempts > $attempts && time() < ($lastFailedTimestamp+$delay))
                    return ($lastFailedTimestamp+$delay) - time();
        
        return 0;
    }
}