# zf2-brute-force-protection
Automatic brute force attack prevention module for use within Zend Framework 2. Stores all failed login attempts site-wide in a database and compares the number of recent failed attempts against a set threshold. Responds with time delay between login requests.

Implementation by Team CODIFIC • We code terrific.

Inspired by the work of Evan Francis, https://github.com/ejfrancis/brute-force-block.
Inspired by the Angular JS implementation, https://www.npmjs.com/package/express-brute

MIT License http://opensource.org/licenses/MIT.

# Installation
Add the plugin to your composer.json and run php composer.phar update

# Setup
1. Import the user_failed_login.sql file to your database
2. If you are using a local.php configuration file stored in data/local.php then the plugin works as it is.
3. Otherwise please set the $databaseConfig array.
$databaseConfig = array(
     'host' => 'localhost',
     'dbname' => 'database_name',
     'username' => 'username',
     'password' => 'password');

# Usage
In the LoginController (or whatever controller is responsible for the login business logic):
1. Before actually running the provided authentication credentials use the following code (or alike) to check whether there are too many requests:

  if(\CODIFIC\BruteForce::getLoginStatus() === false)
  {
      return $this->getResponse()->setStatusCode(429); //return code 429 for too many requests
  }
  
2. If the login with the provided authentication credentials fails, then add the failed attempt via the following code: 
  \CODIFIC\BruteForce::addFailedLogin($post['username']);
  
That's it.
  

