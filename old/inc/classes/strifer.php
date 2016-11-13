<?php
/**
 * Overseer v2 PHP Class: Strifer
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
 * Strifer data handling class
 *
 * Class that dynamically loads up strifer data and allows for simplified
 * methods of causing damage to the character and other stat-based operations.
 *
 * @category Overseer
 * @package  Overseer\Strifer
 * @author   Kitty Cat <ki@tty.sh>
 * @license  http://overseer2.com/license.txt Fail License 2015
 * @link     http://overseer2.com/ Project Site
 */
class Strifer
{

    public $id;

    /**
     * Class initialization function
     *
     * Automatically calls load() if there is a strifer ID passed during
     * creation of the class.
     *
     * @param PDO     $dbhandle The global PDO object for the database.
     * @param integer $initid   The strifer ID to start with.
     *
     * @access public
     */
    function __construct($dbhandle, $initid=-1)
    {
        $this->_dbhandle = $dbhandle;
        $this->id = $initid;
        if ($this->id != -1) {
            $this->load($this->id);
        }
    }

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
        switch ($name) {
        case "healthpercent":
            return (($this->health / $this->maxhealth)*100);
            break;
        case "energypercent":
            return (($this->energy / $this->maxenergy)*100);
            break;
        default:
            if (array_key_exists($name, $this->_data)) {
                return $this->_data[$name];
            }
            break;
        }
        return null;
    }

    /**
     * Load a strifer
     *
     * This function loads a strifer from the database.  It is the function
     * that is wholly responsible for populating every single aspect of data
     * served from this class.
     *
     * @param integer $striferID The ID of the strifer that needs to be loaded.
     *
     * @return null
     *
     * @access public
     */
    public function load($striferID)
    {

        // Get the character's row to load it into the object
        $strifequery = $this->_dbhandle->prepare(
            'SELECT * FROM `Strifers` WHERE `ID` = :striferid'
        );
        $strifequery->bindParam(':striferid', $striferID);
        $strifequery->execute();

        // Check that there is only one character returned from the query
        if ($strifequery->rowcount() != 1) {
            throw new \Exception('Strifer could not be found.');
        }
        $striferow = $strifequery->fetch(PDO::FETCH_ASSOC);
        unset($strifequery);
        $this->id = $striferow['ID'];

        // Turn all strings and integers directly into data keys
        $this->_data = array(
            'name'        => $striferow['name'],
            'owner'       => $striferow['owner'],
            'symbol'      => $striferow['strifeID'],
            'side'        => $striferow['side'],
            'teamwork'    => $striferow['teamwork'],
            'grist'       => $striferow['grist'],
            'land'        => $striferow['land'],
            'description' => $striferow['description'],
            'echeladder'  => $striferow['echeladder'],
            'aspect'      => $striferow['aspect'],
            'fatigue'     => $striferow['fatigue'],
            'power'       => $striferow['power'],
            'maxpower'    => $striferow['maxpower'],
            'health'      => $striferow['health'],
            'maxhealth'   => $striferow['maxhealth'],
            'energy'      => $striferow['energy'],
            'maxenergy'   => $striferow['maxenergy'],
            // TODO: more junk, just trying to piece some stuff together atm
        );

        // Enumerate boolean numerics
        $booleans = array(
            'leader'  => 'leader',
            'control' => 'control',
        );

        $lists = array(
            //'grist_type' => 'landGrists',
        );

        // Convert boolean numerics to booleans and store as data keys
        foreach ($booleans as $dbkey => $datakey) {
            if ($striferow[$dbkey] == 1) {
                $convertedvalue = true;
            } elseif ($striferow[$dbkey] == 0) {
                $convertedvalue = false;
            } else {
                throw new \Exception(
                    'Non-boolean numeric value found in dbkey '.$dbkey.'.'
                );
            }
            $this->_data[$datakey] = $convertedvalue;
            unset($convertedvalue);
        }
    }

}
