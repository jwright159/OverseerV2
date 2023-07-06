<?php
/**
 * Overseer v2 PHP Class: Character
 *
 * File containing the Character class.
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
 * Character data handling class
 *
 * Class that dynamically loads up character information and simplifies
 * varied methods of working with character data.
 *
 * @category Overseer
 * @package  Overseer\Character
 * @author   Kitty Cat <ki@tty.sh>
 * @license  http://overseer2.com/license.txt Fail License 2015
 * @link     http://overseer2.com/ Project Site
 */
class Character
{

    public $id, $grist, $wakeself, $dreamself, $strife;

    private $_dbhandle;
    
    // Gate  height definitions, defined in reverse.
    private $_gates = array(
                       7 => 24000000,
                       6 => 11111100,
                       5 => 1111100,
                       4 => 111100,
                       3 => 11100,
                       2 => 1100,
                       1 => 100,
                       0 => 0,
                      );

    private $_data = array(), $_datamod = array();


    /**
     * Class initialization function
     *
     * Automatically calls load() if there is a character ID passed during
     * creation of the class.
     *
     * @param PDO     $dbhandle The global PDO object for the database.
     * @param integer $initid   The character ID to start with.
     *
     * @access public
     */
    function __construct(PDO $dbhandle, $initid=-1)
    {

        $this->_dbhandle = $dbhandle;
        $this->id        = $initid;
        if ($this->id != -1) {
            $this->load($this->id);
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
        case "gatesreached":
            foreach ($this->_gates as $gate => $reqheight) {
                if ($this->house_build >= $reqheight) {
                    return $gate;
                    break;
                }
            }
            break;

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
            $this->_data[$name] = $value;
            if (!in_array($name, $this->_datamod)) {
                $this->_datamod[] = $name;
            }
        }

    }//end __set()


    /**
     * Load a character
     *
     * This function loads a character from the database.  It is the function
     * that is wholly responsible for populating every single aspect of data
     * served from this class.
     *
     * @param integer $characterID The ID of the character that needs to be loaded.
     *
     * @return null
     *
     * @access public
     */
    public function load($characterID)
    {

        // Get the character's row to load it into the object.
        $charquery = $this->_dbhandle->prepare(
            'SELECT * FROM `Characters` WHERE `ID` = :charid'
        );
        $charquery->bindParam(':charid', $characterID);
        $charquery->execute();

        // Check that there is only one character returned from the query.
        if ($charquery->rowcount() != 1) {
            throw new Exception('Character could not be found.');
        }

        // Load the character row as an associateive array.
        $charrow = $charquery->fetch(PDO::FETCH_ASSOC);

        // Unset the query object to free memory.
        unset($charquery);

        // Set the class ID variable to the ID loaded from the database.
        // There is no real reason that it should differ from what was passed
        // in the original argument, but oh well.
        $this->id = $charrow['ID'];

        // Start with an empty data table.
        $this->_data = array();

        // Enumerate direct values (strings and numbers).
        $directs = array(
                    'name',
                    'symbol',
                    'owner',
                    'server',
                    'client',
                    'land1',
                    'land2',
                    'consort',
                    'class',
                    'aspect',
                    'echeladder',
                    'godtier',
                    // The 'aspectpatterns' column should probably be abstracted.
                    'aspectpatterns',
                    'fraymotifs',
                    'colour',
                    'dreamer',
                    'dreamingstatus',
                    'wakefatigue',
                    'dreamfatigue',
                    'fatiguetimer',
                    'exploration',
                    'dungeon',
                    'dungeoncoords',
                    'olddungeoncoords',
                    'proto_preentry',
                    'proto_obj1',
                    'proto_obj2',
                    // The 'proto_effects' column should probably be abstracted.
                    'proto_effects',
                    'sprite',
                    'house_build',
                    'gatescleared',
                    'boondollars',
                    'hascomputer',
                    'newmessage',
                    'captchalogues',
                    'encounters',
                    'encountersspent',
                    'lasttick',
                    // The 'inventory' column should have its own class.
                    'inventory',
                    // The 'metadata' key should be worked into the inventory class.
                    'metadata',
                    'strifedeck',
                    'modus',
                   );
        
        // Enumerate boolean numerics.
        $booleans = array(
                     'inmedium',
                     'firstaspectuse',
                     'denizendown',
                     'down',
                     'dreamdown',
                    );

        // Enumerate lists, stored as numeric arrays.
        $lists = array(
                  'grist_type',
                  'abilities',
                  'strifedeck',
                 );

        // Store direct values directly as data keys.
        foreach ($directs as $dbkey) {
            $this->_data[$dbkey] = $charrow[$dbkey];
        }
        
        // Convert boolean numerics to booleans and store as data keys.
        foreach ($booleans as $dbkey) {
            // Convert 1 to true and 0 to false.
            if ($charrow[$dbkey] == 1) {
                $convertedvalue = true;
            } elseif ($charrow[$dbkey] == 0) {
                $convertedvalue = false;
            } else {
                // Cry because we're not working with booleans.
                throw new Exception(
                    'Non-boolean numeric value found in dbkey '.$dbkey.'.'
                );
            }

            // Everything went fine converting the value, so set it in the
            // internal datastore.
            $this->_data[$dbkey] = $convertedvalue;

            // Unset our used variables to save memory.
            unset($convertedvalue);
        }
        
        // Convert lists to arrays and store into data keys.
        foreach ($lists as $dbkey) {
            $this->_data[$dbkey] = array();
            foreach (explode('|', $charrow[$dbkey]) as $listNode) {
                if (!empty($listNode)) {
                    $this->_data[$dbkey][] = $listNode;
                }
            }
        }

        // New things should probably be added right around here.
        // All of the custom datatype columns and whatnot.
        true;

        // Instantiate the grist object and load it from the db.
        $this->grist = new \Overseer\Grist;
        $this->grist->importOld($charrow['grists']);

        // Initialize the strifer objects for wakeself and dreamself.
        $this->wakeself  = new \Overseer\Strifer(
            $this->_dbhandle,
            $charrow['wakeself']
        );
        $this->dreamself = new \Overseer\Strifer(
            $this->_dbhandle,
            $charrow['dreamself']
        );

        // Check the dreamingstatus and alias the 'strife' variable accordingly.
        if ($this->dreamingstatus == "Awake") {
            $this->strife = &$this->wakeself;
        } else {
            $this->strife = &$this->dreamself;
        }

    }//end load()


    /**
     * Dynamic character row saving
     *
     * A fancy save function that detects which variables have been changed and
     * dynamically assembles an SQL query for them.  Also kicks off the save
     * function for associated sub-objects such as strife.
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

        // Save the strifer rows since they should handle themselves.
        //$this->wakeself->save();
        //$this->dreamself->save();
        
        // Check if the grist array has been modified, export it if so.
        if ($this->grist->modified) {
            $updatepairs[]          = 'grists';
            $updatevalues['grists'] = $this->grist->exportOld();
            // Since we're committing the gristarray in the query to follow,
            // reset the modified status of the grist object just in case
            // it's used again after we save.
            $this->grist->modified = false;
        }
        
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
                    // Arrays must be converted to "old" implode format.
                    case 'array':
                        // An array handler still needs to be written.
                        true;
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
            $updatechar = $this->_dbhandle->prepare(
                'UPDATE Characters SET ' . implode(',', $querypairs) .
                ' WHERE ID=:charid'
            );

            // Bind all of the values that have direct variable associations.
            foreach ($updatebinds as $bindvar => $bindval) {
                $updatechar->bindParam(':' . $bindvar, $bindval);
            }

            // Bind all of the values that are changed during the save.
            foreach ($updatevalues as $bindvar => $bindval) {
                $updatechar->bindValue(':' . $bindvar, $bindval);
            }

            $updatechar->bindParam(':charid', $this->id);
            $updatechar->execute();
        }//end if

    }//end save()


}//end class
