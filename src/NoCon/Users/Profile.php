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
 * Profle class provides user profile functions.
 * 
 * This class is used to provide user profile functions such as name, 
 * location, etc. The class extends the User class and can therefore be used
 * in place of the User class for all user functions.
 * 
 * Requires a PDO supported data source with a user profile table:
 * nocon_user_profile
 * -------------
 * username varchar(255), foreign key
 * firstName varchar(255)
 * lastName varchar(255)
 * latitude float
 * longitude float
 * email varchar(255)
 * phone varchar(255)
 * json text
 * 
 * @author Bryan Nielsen <bnielsen1965@gmail.com>
 * @copyright (c) 2015, Bryan Nielsen
 * 
 */
class Profile extends User {
	/**
	 * Class constants
	 */
    
    const USER_PROFILE_TABLE_NAME = 'nocon_user_profile';
    
    
    /**
     * Private properties
     */

    
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
        parent::__construct($config, $userRow);
    }
    
    
    
    
}