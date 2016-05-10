<?php
/**
 * BruteForce
 * prevent user to send too many requests to server
 *
 * PHP version 5
 *
 * @author Aram Hovsepyan < aram@codific.eu >
 * @author Nikolay Dyakov < nikolay@codific.eu >
 * @link   https://codific.eu
 */
namespace Codific;

/**
 * BruteForce
 * prevent user to send too many requests to server
 *
 * @author Aram Hovsepyan < aram@codific.eu >
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
     *       'port' => 3307,
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
                    "mysql:host=".self::$databaseConfig['host'].";port=".self::$databaseConfig['port'].";dbname=".self::$databaseConfig['dbname'],
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
     * Add failed login record to the database.
     * @param string $username the username to add (for accounting reasons)
     * @return void
     */
    public static function addFailedLogin($username="")
    {
        if(!$username)
            $username = "anonymous";
        $db = self::_db();
        $stmt = $db->prepare("INSERT INTO user_failed_login(`username`,`ip`, `timestamp`) VALUES(:username,:ip, NOW())");
        $stmt->execute(array(
                ':username'=>$username,
                ':ip'=>self::getIP(),
        ));
    }

    /**
     * Get login delay in seconds
     * This function returns a 0 if the login can proceed, or returns a positive integer if the login should be delayed by X seconds
     * @param String $filter use IP(Default) or USERNAME or BOTH to choice how to check for login delay
     * @param String $username if you choice USERNAME or BOTH for filter you must supply user name here
     * @return integer
     */
    public static function getLoginDelay($filter = 'IP', $username="")
    {
        // Get db connection
        $db = self::_db();
        // Get number of failed attempts and timestap for last failed attempt
        $where = "";
        $params = array(':timeframe'=>self::$timeFrame);
        if(strtoupper($filter) == 'IP')
        {
            $where = "ip = :ip";
            $params[':ip'] = self::getIP();
        }
        if(strtoupper($filter) == 'USERNAME')
        {
            $where = "username = :username";
            $params[':username'] = $username;
        }
        if(strtoupper($filter) == 'BOTH')
        {
            $where = "ip = :ip AND username = :username";
            $params[':ip'] = self::getIP();
            $params[':username'] = $username;
        }
        $stmt = $db->prepare("SELECT COUNT(id) as `count`, MAX(timestamp) as `lastDate` FROM user_failed_login WHERE $where AND timestamp > DATE_SUB(NOW(), INTERVAL :timeframe MINUTE)");
        $stmt->execute($params);
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