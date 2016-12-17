<?php
/**
 * Overseer v2 PHP Class: User
 *
 * File containing the User class.
 *
 * PHP Version 5
 *
 * @category Overseer
 * @package  Overseer
 * @author   Kitty Cat <ki@tty.sh>
 * @license  http://overseer2.com/license.txt Fail License 2015
 * @link     http://overseer2.com/ Project Site
 */

namespace Overseer;

use \PDO;

/**
 * User data handling class
 *
 * Class that dynamically loads up user information and simplifies
 * varied methods of working with user data.
 *
 * @category Overseer
 * @package  Overseer\User
 * @author   Kitty Cat <ki@tty.sh>
 * @license  http://overseer2.com/license.txt Fail License 2015
 * @link     http://overseer2.com/ Project Site
 */
class User
{

    public $id, $characters;

    private $_dbhandle;

    private $_data = array(), $_datamod = array();


    /**
     * Class initialization function
     *
     * Automatically calls load() if there is a user ID passed during
     * creation of the class.
     *
     * @param PDO     $dbhandle     The global PDO object for the database.
     * @param integer $initid       The user ID to start with.
     * @param boolean $userbystring Use "true" if looking up user by string.
     *
     * @access public
     */
    function __construct(PDO $dbhandle, $initid=-1, $userbystring=false)
    {

        $this->_dbhandle = $dbhandle;
        $this->id        = $initid;
        if ($this->id != -1) {
            $this->load($this->id, $userbystring);
        }

    }//end __construct()


    /**
     * Overloaded variable retrieval
     *
     * Called when an undefined variable is requested from the class.
     * Used to output raw data types directly from the datastore and monitor
     * if they're changed at runtime in combination with __put().
     *
     * @param string $name The variable being requested
     *
     * @return mixed The definition of the variable being requested,
     *               otherwise, null.
     *
     * @access public
     */
    public function __get($name)
    {

        // Check for any special variables that require "thinking".
        switch ($name) {

        // we might have something else here that needs to be loaded

        // Default back to loading a variable from a _data key otherwise.
        default:
            if (array_key_exists($name, $this->_data)) {
                return $this->_data[$name];
            }
            break;
        }

        // Output would have been returned by now, nullifying the function
        // from even getting to this point, so return null.
        return null;

    }//end __get()


    /**
     * Overloaded variable defining
     *
     * Used to set variables that are not defined explicitly by the class.
     * Also used to track which of those variables have been modified so that
     * they can be programatically be saved to the database.
     *
     * @param string $name  The variable to set
     * @param mixed  $value The value to give to the variable
     *
     * @return null
     *
     * @access public
     */
    public function __set($name, $value)
    {

        if (array_key_exists($name, $this->_data)) {
            if ($name == "password") {
                $value = password_hash($value, PASSWORD_DEFAULT);
            }
            $this->_data[$name] = $value;
            if (!in_array($name, $this->_datamod)) {
                $this->_datamod[] = $name;
            }
        }

    }//end __set()


    /**
     * Load a user
     *
     * This function loads a user from the database.  It is the function
     * that is wholly responsible for populating every single aspect of data
     * served from this class.
     *
     * @param integer $userID       The ID of the user that needs to be loaded.
     * @param boolean $userbystring Use "true" if finding username by string.
     *
     * @return null
     *
     * @access public
     */
    public function load($userID, $userbystring=false)
    {

        // Get the user's row to load it into the object.
        if ($userbystring == true) {
            $userquery = $this->_dbhandle->prepare(
                'SELECT * FROM `Users` WHERE `username` = :userid'
            );
        } else {
            $userquery = $this->_dbhandle->prepare(
                'SELECT * FROM `Users` WHERE `ID` = :userid'
            );
        }

        $userquery->bindParam(':userid', $userID);
        $userquery->execute();

        // Check that there is only one user returned from the query.
        if ($userquery->rowcount() != 1) {
            throw new \Exception('User could not be found.');
        }

        // Load the user row as an associative array.
        $userrow = $userquery->fetch(PDO::FETCH_ASSOC);

        // Unset the query object to free memory.
        unset($userquery);

        // Set the class ID variable to the ID loaded from the database.
        // There is no real reason that it should differ from what was passed
        // in the original argument, but oh well.
        $this->id = $userrow['ID'];

        // Start with an empty data table.
        $this->_data = array();

        // Enumerate direct values (strings and numbers).
        $directs = array(
                    'username',
                    'password',
                    'email',
                    // Password recovery should be functionalized below
                    'password_recovery',
                    // I don't even know what's happening with sessions
                    'sessions',
                    'modlevel',
                    'lastchar',
                   );

        // Store direct values directly as data keys.
        foreach ($directs as $dbkey) {
            $this->_data[$dbkey] = $userrow[$dbkey];
        }

        // New things should probably be added right around here.
        // All of the custom datatype columns and whatnot.
        true;

        // Find all characters belonging to the user and load them
        $getcharq = $this->_dbhandle->prepare(
            'SELECT ID FROM `Characters` WHERE owner=:id'
        );
        $getcharq->bindParam(':id', $this->id);
        $getcharq->execute();
        $this->characters = array();
        foreach ($getcharq->fetchAll() as $character) {
            $this->characters[$character['ID']] = new \Overseer\Character(
                $this->_dbhandle, $character['ID']
            );
        }
        unset($getcharq);

    }//end load()

    /**
     * Dynamic user row saving
     *
     * A fancy save function that detects which variables have been changed and
     * dynamically assembles an SQL query for them.  Also kicks off the save
     * function for associated sub-objects.
     *
     * @return null
     *
     * @access public
     */
    public function save()
    {
        // Initialize the query formation arrays.
        $updatepairs  = array();
        $updatebinds  = array();
        $updatevalues = array();

        if (count($this->_datamod) != 0) {
            foreach ($this->_datamod as $modkey) {
                switch ($modkey) {
                // Here would be a perfect place to set custom handlers
                // for custom datatypes.
                // By default, sort out data types by object type.
                default:
                    switch (gettype($this->_data[$modkey])) {
                    // Booleans must be converted to 1's and 0's.
                    case 'boolean':
                        $updatepairs[] = $modkey;
                        if ($this->_data[$modkey] == true) {
                            $updatevalues[$modkey] = 1;
                        } else {
                            $updatevalues[$modkey] = 0;
                        }
                        break;
                    // Anything that is a number or a string is stored directly.
                    case 'integer':
                    case 'double':
                    case 'float':
                    case 'string':
                        $updatepairs[]        = $modkey;
                        $updatebinds[$modkey] = &$this->_data[$modkey];
                        break;
                    }//end switch
                    break;
                }//end switch
            }//end foreach
        }//end if

        // Check if we have anything to submit.
        if (count($updatepairs) != 0) {
            // Create an empty array as a basis.
            $querypairs = array();

            // Iterate over each prepared pair and add it to the pairs array.
            foreach ($updatepairs as $sqlvar) {
                $querypairs[] = $sqlvar . '=:' . $sqlvar;
            }

            // Create the prepared statement object.
            $updateuser = $this->_dbhandle->prepare(
                'UPDATE Users SET ' . implode($querypairs, ',') .
                ' WHERE ID=:userid'
            );

            // Bind all of the values that have direct variable associations.
            foreach ($updatebinds as $bindvar => $bindval) {
                $updateuser->bindParam(':' . $bindvar, $bindval);
            }

            // Bind all of the values that are changed during the save.
            foreach ($updatevalues as $bindvar => $bindval) {
                $updateuser->bindValue(':' . $bindvar, $bindval);
            }

            $updateuser->bindParam(':userid', $this->id);
            $updateuser->execute();
        }//end if

    }//end save()

    /**
     * Password validation
     *
     * Takes an argument of a plaintext password to verify against the
     * stored hash in order to authenticate a user.
     *
     * @param string $passverify The password to check correctness on
     *
     * @return boolean Whether the password is correct or not
     *
     * @access public
     */
    function verifyPassword($passverify)
    {
        return password_verify($passverify, $this->password);
    }

    /**
     * Recovery key generation
     *
     * Generates a random 32 character key to be used for password recovery,
     * and automatically assigns it to the password_recovery variable.
     *
     * @return string The freshly generated password recovery key.
     *
     * @access public
     */
    function generateRecoveryKey()
    {
        // Code taken from http://stackoverflow.com/a/48125
        $genchars = 'abcdefghijklmnopqrstuvwxyz';
        $genchars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $genchars .= '0123456789';
        $genstring = '';
        for ($i = 0; $i < 32; $i++) {
            $genstring .= $genchars[rand(0, strlen($genchars) - 1)];
        }
        $this->password_recovery = $genstring;
        return $this->password_recovery;
    }

    /**
     * Password recovery function
     *
     * Performs validation of a recovery key and conditional setting of
     * a new password.
     *
     * @param string $recoveryKey The challenge recovery key.
     * @param string $newPassword The password to set on success.
     *
     * @return string The freshly generated password recovery key.
     *
     * @access public
     */
    function recoverPassword($recoveryKey, $newPassword)
    {
        // Check that the recovery key matches
        if ($recoveryKey == $this->password_recovery) {
            // The recovery key matched, set the password
            $this->password = $newPassword;
            // Remove the password recovery key so that it cannot be abused.
            $this->password_recovery = '';
            // Password recovery successful, return true.
            return true;
        } else {
            // The recovery key did not match, return false.
            return false;
        }
    }

    /**
     * Password recovery email function
     *
     * Sends an email to the address that the user registered with,
     * automatically generating a recovery key if one is not already
     * stored in the user's record.
     *
     * @return boolean Whether the operation was successful or not.
     *
     * @access public
     */
    function sendRecoveryEmail()
    {
        // First, make sure that the user account has an email address
        if (!$this->email) {
            // Because if they don't, we can't do anything!
            return false;
        }

        // Second, determine if a recovery key has been set or not
        if (!$this->password_recovery) {
            // And if it isn't set, then set it!
            $this->generateRecoveryKey();
        }

        // Third, assemble the outgoing message...
        $mail_subject = "Overseer 2 Password Reset";
        $mail_message = "Hello ".$this->username.",\n".
            "You have requested a password reset on Overseer 2.\n".
            "Your key is: " . $this->password_recovery . "\n".
            "This should be entered on the page where it was requested.\n".
            "Regards, The Overseer 2 Team\n";
        $mail_headers = "From: Overseer 2 <noreply@overseer2.com>\n".
            "Reply-To: noreply@overseer2.com\n".
            "X-Mailer: PHP/" . phpversion();

        // And last but not least, send the message!
        mail($this->email, $mail_subject, $mail_message, $mail_headers);

        // We got this far, so, we're good?
        return true;
    }

}//end class
