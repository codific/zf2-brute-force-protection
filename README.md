# zf2-brute-force-protection
Automatic brute force attack prevention module for use within Zend Framework 2. Stores all failed login attempts site-wide in a database and compares the number of recent failed attempts against a set threshold. Responds with time delay between login requests.

Implementation by Team CODIFIC • We code terrific.

Inspired by the work of Evan Francis, https://github.com/ejfrancis/brute-force-block.
Inspired by the Angular JS implementation, https://www.npmjs.com/package/express-brute

MIT License http://opensource.org/licenses/MIT.

# Installation
Add the plugin to your composer.json by using the following line:
```json
"codific/zf2-brute-force-protection": "dev-master"
```
and run 
```bash
php composer.phar update
```

# Setup
1. Import the user_failed_login.sql file to your database
2. 
- If you are using a local.php configuration file stored in data/local.php then the plugin works as it is.
- Otherwise please set the $databaseConfig array.
```php
$databaseConfig = array(
     'host' => 'localhost',
     'port' = > 3306,
     'dbname' => 'database_name',
     'username' => 'username',
     'password' => 'password');
```

# Usage
In the LoginController (or whatever controller is responsible for the login business logic):

## Before running the authentication
Before actually running the provided authentication credentials use the following code (or alike) to check whether there are too many requests:
```php
  $delay = \Codific\BruteForce::getLoginDelay();
  if($delay > 0)
  {
      $this->cache->error = "Too Many Requests. Please wait $delay seconds before next try.";
      return $this->redirect()->toUrl("/admin/login/index");
  }
```

You can also return HTTP code 429 that is probably a more systematic solution:
```php
  if(\Codific\BruteForce::getLoginDelay() > 0)
  {
      return $this->getResponse()->setStatusCode(429);
  }
```

## If the login fails
If the login with the provided authentication credentials fails, then add the failed attempt via the following code: 
```php
  \Codific\BruteForce::addFailedLogin($username);
```
  
That's it.


