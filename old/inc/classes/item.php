<?php
/**
 * Overseer v2 PHP Class: Item
 *
 * File containing the Item class.
 *
 * PHP Version 7
 *
 * @category Overseer
 * @package  Overseer
 * @author   Difarem <difarem@gmail.com>
 * @license  http://overseer2.com/license.txt Fail License 2015
 * @link     http://overseer2.com/ Project Site
 */

namespace Overseer;
use \PDO;

/**
 * Item data handling class
 *
 * Class that dynamically loads up item data.
 *
 * @category Overseer
 * @package  Overseer\Item
 * @author   Difarem <difarem@gmail.com>
 * @license  http://overseer2.com/license.txt Fail License 2015
 * @link     http://overseer2.com/ Project Site
 */
class Item {
    public $id, $gristCost;

    /**
     * Class initialization function
     *
     * Automatically calls load() if there is an item ID passed during
     * creation of the class.
     *
     * @param PDO     $dbhandle The global PDO object for the database.
     * @param integer $initid   The item ID to start with.
     *
     * @access public
     */
    function __construct($dbhandle, $initid=-1) {
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
    public function __get($name) {
        switch ($name) {
        default:
            if (array_key_exists($name, $this->_data)) {
                return $this->_data[$name];
            }
            break;
        }
        return null;
    }

    /**
     * Load an item
     *
     * This function loads an item from the database.  It is the function
     * that is wholly responsible for populating every single aspect of data
     * served from this class.
     *
     * @param integer $itemID The ID of the item that needs to be loaded.
     *
     * @return null
     *
     * @access public
     */
    public function load($itemID) {
        // Get the item's row to load it into the object
        $itemquery = $this->_dbhandle->prepare(
            'SELECT * FROM `Captchalogue` WHERE `ID` = :itemid'
        );
        $itemquery->bindParam(':itemid', $itemID);
        $itemquery->execute();

        // Check that there is only one item returned from the query
        if ($itemquery->rowcount() != 1) {
            throw new \Exception('Item could not be found.');
        }
        $itemrow = $itemquery->fetch(PDO::FETCH_ASSOC);
        unset($itemquery);
        $this->id = $itemrow['ID'];

        // Turn all strings and integers directly into data keys
        $this->_data = array(
            //'code'                      => $itemrow['code'],
            'name'                      => $itemrow['name'],
            'description'               => $itemrow['description'],
            'consumable_effects'        => $itemrow['consumable'],
            'session'                   => $itemrow['session'],
            'power'                     => $itemrow['power'],
            'art'                       => $itemrow['art'],
            'artCredit'                 => $itemrow['credit'],
            'abstratus'                 => $itemrow['abstratus'],
            'wearable'                  => $itemrow['wearable'],
            'size'                      => $itemrow['size'],
            'effects'                   => $itemrow['effects'],
            'statusEffects'             => $itemrow['status'],
            'notes'                     => $itemrow['notes'],
            'nonsense'                  => $itemrow['nonsense'],
        );

        // Command bonuses
        $this->_data['bonuses'] = array(
            'aggrieve'        => $itemrow['aggrieve'],
            'aggress'         => $itemrow['aggress'],
            'assail'          => $itemrow['assail'],
            'assault'         => $itemrow['assault'],
            'abuse'           => $itemrow['abuse'],
            'accuse'          => $itemrow['accuse'],
            'abjure'          => $itemrow['abjure'],
            'abstain'         => $itemrow['abstain'],
        );

		// Captchalogue code
		$this->code = new Code($itemrow['code']);

        // Enumerate boolean numerics
        $booleans = array(
            'base' => 'base',
            'loot' => 'loot',
            'refrance' => 'reference',
            'old' => 'old',
        );

        // Convert boolean numerics to booleans and store as data keys
        foreach ($booleans as $dbkey => $datakey) {
            if ($itemrow[$dbkey] == 1) {
                $convertedvalue = true;
            } elseif ($itemrow[$dbkey] == 0) {
                $convertedvalue = false;
            } else {
                throw new \Exception(
                    'Non-boolean numeric value found in dbkey '.$dbkey.'.'
                );
            }
            $this->_data[$datakey] = $convertedvalue;
            unset($convertedvalue);
        }

        // Grist costs
        $this->gristCost = new Grist();
        $this->gristCost->importOld($itemrow['gristcosts']);
    }
}

?>
