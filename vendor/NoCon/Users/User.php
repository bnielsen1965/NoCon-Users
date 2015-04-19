<?php
/*
* Copyright (C) 2015 Bryan Nielsen - All Rights Reserved
*
* Author: Bryan Nielsen <bnielsen1965@gmail.com>
*
*
* This file is part of the NoCon PHP application framework.
* NoCon is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
* 
* NoCon is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
* 
* You should have received a copy of the GNU General Public License
* along with this application.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace NoCon\Users;

/**
 * User class provides basic user functions.
 * 
 * This class is used to provide basic user account functions such as authentication, 
 * account creation, status, etc.
 * 
 * Requires a PDO supported data source with a users table:
 * users
 * -------------
 * username varchar(255), primary key
 * password varchar(255)
 * created timestamp
 * lastLogin timestamp
 * flags integer
 * 
 * @author Bryan Nielsen <bnielsen1965@gmail.com>
 * @copyright (c) 2015, Bryan Nielsen
 * 
 */
class User {
	/**
	 * Class constants
	 */
	const ADMIN_FLAG = 1;
	const ACTIVE_FLAG = 2;
    const ALL_FLAGS = 3;
    
    const USER_TABLE_NAME = 'nocon_user';
    
    
    /**
     * Private properties
     */
    protected $pdo;
    protected $username;
    protected $passwordHash;
    protected $created;
    protected $lastLogin;
    protected $flags;

    
    /**
     * Construct a User instance connected to the PDO source specified by the
     * passed configuration parameters.
     * 
     * The configuration array is expected to be an associative array with the
     * fields used in the PDO construction...
     * dsn = The dsn string for the PDO connection.
     * username = The username to use in the PDO connection.
     * password = The password to use in the PDO connection.
     * options = An array of PDO connection options.
     * 
     * @param array $config The PDO connection configuration values.
     * @throws \Exception
     */
    public function __construct($config, $userRow = null) {
        $this->errors = array();
        
        try {
            $this->pdo = new \PDO(
                $config['dsn'],
                $config['username'],
                $config['password'],
                $config['options']
            );
        }
        catch (\PDOException $ex) {
            throw new \Exception($ex->getMessage(), $ex->getCode());
        }
        
        if ( $userRow ) {
            $this->username = $userRow['username'];
            $this->passwordHash = $userRow['password'];
            $this->created = $userRow['created'];
            $this->lastLogin = $userRow['lastLogin'];
            $this->flags = $userRow['flags'];
        }
    }
    
    
    /**
     * Check if this user is an admin.
     * 
     * @return boolean Returns true if user is admin, false otherwise.
     */
    public function isAdmin() {
        return $this->isFlagSet(User::ADMIN_FLAG);
    }
    
    
    /**
     * Check if this user is activated.
     * 
     * @return boolean Returns true if user is activated, false otherwise.
     */
    public function isActive() {
        return $this->isFlagSet(User::ACTIVE_FLAG);
    }
    
    
    /**
     * Test if a flag is set or a combination of flags.
     * 
     * @param integer $flag The flag integer to test or a combination of flags.
     * @return boolean Status of flag set.
     */
    public function isFlagSet($flag) {
        return ($this->flags & $flag) ? true : false;
    }
    
    
    /**
     * Set the specified flags for thsi user.
     * 
     * @param integer $flags The flags to set for this user.
     */
    public function setFlags($flags) {
        $this->flags |= ($flags & User::ALL_FLAGS);
    }
    
    
    /**
     * Clear the specified flags for this user.
     * 
     * @param integer $flags The flags to clear for this user.
     */
    public function clearFlags($flags) {
        $this->flags ^= ($flags & $this->flags);
    }
    
    
    /**
     * Create a new user in the database.
     * 
     * @param string $username The username to use for this user.
     * @param string $password The password to use for this user.
     */
    public function create($username, $password) {
        // verify permissions
        if ( !$this->isAdmin() ) {
            throw new \Exception('Permission denied.');
        }
        
        // create user
        $st = $this->pdo->prepare(
                'INSERT INTO ' . self::USER_TABLE_NAME . ' (username, password, created, flags) VALUES (:username, :password, :created, 0)'
        );
        
        if ( false === $st->execute(array(':username' => $username, ':password' => self::hashPassword($password), ':created' => date('Y-m-d H:i:s'))) ) {
            $this->throwStatementException($st);
        }
    }
    
    
    /**
     * Delete a user from the database.
     * 
     * @param string $username The username of the user to delete.
     * @throws \Exception
     */
    public function delete($username) {
        // verify permissions
        if ( !$this->isAdmin() || $username === $this->username ) {
            throw new \Exception('Permission denied.');
        }
        
        // create user
        $st = $this->pdo->prepare(
                'DELETE FROM ' . self::USER_TABLE_NAME . ' WHERE username=:username'
        );
        
        if ( false === $st->execute(array(':username' => $username)) ) {
            $this->throwStatementException($st);
        }
    }
    
    
    /**
     * Search for the existence of a username in the database.
     * 
     * @param string $username The username to search.
     * @return boolean Does the username exist.
     */
    public function usernameExists($username) {
        $st = $this->pdo->prepare(
                'SELECT * FROM ' . self::USER_TABLE_NAME . ' WHERE username=:username'
        );
        
        if ( false === $st->execute(array(':username' => $username)) ) {
            $this->throwStatementException($st);
        }
        
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC);
        
        if ( count($rows) === 0 ) {
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Get a user row from the database.
     * 
     * @param string $username The username to get from the database.
     * @return array The user row from the database.
     * @throws \Exception
     */
    public function getUser($username = null) {
        // verify permissions
        if ( !is_null($username) && !$this->isAdmin() ) {
            throw new \Exception('Permission denied.');
        }
        
        $st = $this->pdo->prepare(
                'SELECT * FROM ' . self::USER_TABLE_NAME . ' WHERE username=:username'
        );
        
        if ( false === $st->execute(array(':username' => (is_null($username) ? $this->username : $username))) ) {
            $this->throwStatementException($st);
        }
        
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC);
        
        if ( count($rows) === 0 ) {
            return false;
        }
        
        return $rows[0];
    }
    
    
    /**
     * Get all user rows from the database.
     * 
     * @return array An array of rows from the database.
     * @throws \Exception
     */
    public function getUsers() {
        // verify permissions
        if ( !$this->isAdmin() ) {
            throw new \Exception('Permission denied.');
        }
        
        $st = $this->pdo->prepare(
                'SELECT * FROM ' . self::USER_TABLE_NAME
        );
        
        if ( false === $st->execute(array()) ) {
            $this->throwStatementException($st);
        }
        
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC);
        
        return $rows;
    }
    
    
    /**
     * Attempts to authenticate the specified user. On success the user's details
     * are loaded into this instance.
     * 
     * @param string $username The username to use for authentication.
     * @param string $password The password to use for authentication.
     * @return boolean Authentication success status.
     */
    public function authenticate($username, $password) {
        $st = $this->pdo->prepare(
                'SELECT * FROM ' . self::USER_TABLE_NAME . ' WHERE username=:username'
        );
        
        if ( false === $st->execute(array(':username' => $username)) ) {
            $this->throwStatementException($st);
        }
        
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC);
        
        if ( count($rows) !== 1 ) {
            return false;
        }
        
        if ( $rows[0]['password'] !== self::hashPassword($password, $rows[0]['password']) || !(self::ACTIVE_FLAG & $rows[0]['flags']) ) {
            return false;
        }
        
        $lastLogin = self::updateLastLogin($username);
        
        $this->username = $username;
        $this->passwordHash = $rows[0]['password'];
        $this->created = $rows[0]['created'];
        $this->lastLogin = $lastLogin;
        $this->flags = $rows[0]['flags'];
        
        return true;
    }
    
    
    /**
     * Update the lastLogin value for the specified username.
     * 
     * @param string $username The username to update lastLogin.
     * @return string The timestamp that was set for this user's lastLogin.
     */
    private function updateLastLogin($username) {
        $st = $this->pdo->prepare(
                'UPDATE ' . self::USER_TABLE_NAME . ' SET lastLogin=:lastLogin WHERE username=:username'
        );
        
        $lastLogin = date('Y-m-d H:i:s');
        
        if ( false === $st->execute(array(':lastLogin' => $lastLogin, ':username' => $username)) ) {
            $this->throwStatementException($st);
        }
        
        return $lastLogin;
    }
    
    
    /**
     * Update a user's password.
     * 
     * @param string $password The new password.
     * @param string $username Username if admin is changing a user's password.
     * @throws \Exception
     */
    public function updatePassword($password, $username = null) {
        // verify permissions
        if ( !is_null($username) && !$this->isAdmin() ) {
            throw new \Exception('Permission denied.');
        }
        
        $passwordHash = self::hashPassword($password);
        
        $st = $this->pdo->prepare(
                'UPDATE ' . self::USER_TABLE_NAME . ' SET password=:password WHERE username=:username'
        );
        
        if ( false === $st->execute(array(':username' => (is_null($username) ? $this->username : $username), ':password' => $passwordHash)) ) {
            $this->throwStatementException($st);
        }
        
        if ( is_null($username) ) {
            $this->passwordHash = $passwordHash;
        }
    }
    
    
    /**
     * Update the permission flags on this user or other user in the database.
     * 
     * @param integer $flags Optional, used to set other user's flags or perform update and simultaneous set.
     * @param string $username Username if admin is changing a user's flags.
     * @throws \Exception
     */
    public function updateFlags($flags = null, $username = null) {
        // verify permissions
        if ( !is_null($username) && !$this->isAdmin() ) {
            throw new \Exception('Permission denied.');
        }
        
        $st = $this->pdo->prepare(
                'UPDATE ' . self::USER_TABLE_NAME . ' SET flags=:flags WHERE username=:username'
        );
        
        if ( false === $st->execute(array(
            ':username' => (is_null($username) ? $this->username : $username), 
            ':flags' => (is_null($flags) ? $this->flags : $flags)
        )) ) {
            $this->throwStatementException($st);
        }
        
        // determine if combined update and set flags on this user
        if ( !is_null($flags) && is_null($username) ) {
            $this->flags = $flags;
        }
    }
    
    
    /**
     * Update the username on this user or other user in the database.
     * 
     * @param string $newUsername The new username to set on a user record.
     * @param string $username Username if admin is changing a user's username.
     * @throws \Exception
     */
    public function updateUsername($newUsername, $username = null) {
        // verify permissions
        if ( !is_null($username) && !$this->isAdmin() ) {
            throw new \Exception('Permission denied.');
        }
        
        $st = $this->pdo->prepare(
                'UPDATE ' . self::USER_TABLE_NAME . ' SET username=:newUsername WHERE username=:username'
        );
        
        if ( false === $st->execute(array(
            ':username' => (is_null($username) ? $this->username : $username), 
            ':newUsername' => $newUsername
        )) ) {
            $this->throwStatementException($st);
        }
        
        // determine if this user's name changed
        if ( is_null($username) ) {
            $this->username = $newUsername;
        }
    }
    
    
    /**
     * Throws an exception based on the error info from the passed PDOStatement.
     * 
     * @param \PDOStatement $statement The statement to use for the exception.
     * @throws \Exception
     */
    private function throwStatementException($statement) {
        $errors = $statement->errorInfo();
        $code = (!empty($errors[1]) ? $errors[1] : 0);
        $error = (!empty($errors[2]) ? $errors[2] : 'Unknown error.');

        throw new \Exception($error, $code);
    }
    
    
	/**
	 * Hash the provided password string.
     * 
	 * @param string $password The plain text user password.
	 * @param string $salt Optional salt to use with crypt. The salt must be provided
	 * when performing a password hash check to make sure the hash values come out the
	 * same. If no salt is provided then the best salt for this server will be generated.
	 * @return string The hashed password.
	 */
	public static function hashPassword($password, $salt = NULL) {
		// if password empty then return empty
		if( empty($password) ) {
            return '';
        }

		return crypt($password, (is_null($salt) ? self::bestSalt() : $salt));
	}
	
	
	/**
	 * Determine the best salt to use for the crypt function on this server.
     * 
	 * @return string The salt to be used with crypt.
	 */
	public static function bestSalt() {
		if( defined('CRYPT_SHA512') && CRYPT_SHA512 ) {
            return '$6$' . self::makePhrase(16) . '$';
        }
        
		if( defined('CRYPT_SHA256') && CRYPT_SHA256 ) {
            return '$5$' . self::makePhrase(16) . '$';
        }
        
		if( defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH ) {
            return '$2a$07$' . base64_encode(self::makePhrase(22)) . '$';
        }
        
		if( defined('CRYPT_MD5') && CRYPT_MD5 ) {
            return '$1$' . self::makePhrase(12) . '$';
        }
        
		if( defined('CRYPT_EXT_DES') && CRYPT_EXT_DES ) {
            return '_' . self::makePhrase(8);
        }
        
		if( defined('CRYPT_STD_DES') && CRYPT_STD_DES ) {
            return self::makePhrase(2);
        }
        
		return '';
	}

    
	/**
	 * Creates a random passphrase.
     * 
	 * @param integer $len The length of the generated pass phrase.
	 * @param boolean $alphanumeric Determines if the generated pass phrase includes only alphanumeric characters.
	 * @return string The generated pass phrase.
	 */
	public static function makePhrase($len = 64, $alphanumeric = FALSE) {
		// determine the character set to use
		if ($alphanumeric === TRUE) {
			$charlist = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
		}
		else
        {
			$charlist = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!@#&*(),.{}[];:";
        }
        
		$phrase = "";
		
		// loop to create random phrase
		do {
			$phrase .= substr($charlist, mt_rand(0, strlen($charlist) - 1), 1);
		} while (--$len > 0);

		return $phrase;
	}
}