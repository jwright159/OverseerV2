<?php
/**
 * Overseer v2 PHP Class: Grist
 *
 * File containing the Overseer v2 PHP Grist Class
 *
 * PHP version 5
 * 
 * @category Overseer
 * @package  Overseer
 * @author   Kitty Cat <ki@tty.sh>
 * @license  http://overseer2.com/license.txt Fail License 2015
 * @link     http://overseer2.com/ Project Site
 */

namespace Overseer;

/**
 * Grist handling class
 * 
 * Made to simplify the handling of grist inside the game, this class
 * allows for simple additions, subtractionss, and counts of grist, 
 * while remaining compatible with both the old format of grist storage
 * as well as the new proposed methods.
 * 
 * @category Overseer
 * @package  Overseer\Grist
 * @author   Kitty Cat <ki@tty.sh>
 * @license  http://overseer2.com/license.txt Fail License 2015
 * @link     http://overseer2.com/ Project Site
 */
class Grist
{

    /**
     * Internal array for storing grist values
     *
     * @var array<string, int>
     */
    private array $_gristArray;
    
    /**
     * Variable that shows if the object was modified after the data
     * has been loaded into it.
     */
    public bool $modified;


    /**
     * Class initialization function
     * 
     * Normally executed by the class automatically when a new Grist
     * object is created, this function handles the optionally specified
     * startup gristArray, and otherwise starts the object off with a
     * blank array.
     * 
     * @param array<string, int> $gristInput an array to initially populate the object.
     *
     * @access public
     */
    public function __construct(array $gristInput = [])
    {

        $this->_gristArray = $gristInput;
        $this->modified    = false;

    }//end __construct()

    
    /**
     * Serialized gristArray import
     *
     * This function accepts a serialized string to populate the
     * gristArray, using the builtin PHP unserialize() function, as
     * proposed for a new data storage method.
     *
     * @param string $gristInput serialized array to populate the gristArray
     *
     * @access public
     */
    public function import(string $gristInput): void
    {

        $this->_gristArray = (array)unserialize($gristInput);
        $this->modified   = false;

    }//end import()

    
    /**
     * Old imploded string gristArray import
     *
     * This function accepts the old storage method of an imploded
     * string, taking the format "Grist_Type:Amount|" and restores it
     * to the object's gristArray.
     *
     * @param string $gristInput imploded array to populate the gristArray
     */
    public function importOld(string $gristInput): void
    {

        // Initialize the grist array.
        $this->_gristArray = [];

        // Break down string and store it in the array.
        foreach (explode('|', $gristInput) as $gristNode) {
            if (!empty($gristNode)) {
                $gristBreakdown = explode(':', $gristNode);
                $this->_gristArray[$gristBreakdown[0]] = (int)$gristBreakdown[1];
                unset($gristBreakdown);
            }
        }

        // We've just imported, so nothing has been changed.
        $this->modified = false;

    }//end importOld()

    
    /**
     * Serialized array export
     * 
     * Exports the array using PHP's build-in serialize() for storage in
     * a database or otherwise.
     * 
     * @return string serialized gristArray
     */
    public function export()
    {
        return serialize($this->_gristArray);
    }//end export()

    
    /**
     * Old imploded string export
     * 
     * Exports the array using the "old" way of imploding the string
     * to be separated using colons and pipes.
     * 
     * @return string imploded gristArray
     */
    public function exportOld()
    {
        // Start with a blank string.
        $gristOutput = "";

        // Go over each grist and append it to the string.
        foreach ($this->_gristArray as $gristName => $gristAmount) {
            $gristOutput .= $gristName.':'.$gristAmount.'|';
        }

        // Finish up by returning the finished string.
        return($gristOutput);
    }//end exportOld()

    
    /**
     * Dump the gristArray as an array
     * 
     * Returns the gristArray as it currently is inside the object.
     * 
     * @return array the raw gristArray.
     * 
     * @access public
     */
    public function dump(): array
    {
        return $this->_gristArray;
    }//end dump()

    
    /**
     * Retrieve the value of the specified grist type.
     * 
     * This function retrieves the current amount of a specific type
     * of grist.  If the grist key is not found, 0 is returned, as
     * there is effectively 0 of that grist anyway.
     * 
     * @param string $gristType type of grist to get a value of
     * 
     * @return int the amount of the specified type of grist
     * 
     * @access public
     */
    public function get(string $gristType): int
    {
        if (isset($this->_gristArray[$gristType])) {
            return $this->_gristArray[$gristType];
        } else {
            return 0;
        }
    }//end get()

    
    /**
     * Add grist to the gristArray
     * 
     * Add the specified amount of the specified grist to the
     * gristArray.
     * 
     * @param string $gristType   type of grist to add
     * @param int    $gristAmount amount of grist to add
     * 
     * @return boolean whether the operation was successful or not
     */
    public function add(string $gristType, int $gristAmount)
    {

        // Check if the passed grist amount is a positive integer.
        if (is_int($gristAmount)
            && $gristAmount >= 1
        ) {
            // If there's already a grist, add to it, otherwise, define it.
            if (isset($this->_gristArray[$gristType])) {
                $this->_gristArray[$gristType] += $gristAmount;
            } else {
                $this->_gristArray[$gristType] = $gristAmount;
            }

            // On the off chance that adding results in 0, clear the key.
            if ($this->_gristArray[$gristType] == 0) {
                unset($this->_gristArray[$gristType]);
            }

            // At this point, we've modified something.
            $this->modified = true;

            // Return true since the operation was successful.
            return true;
        } else {
            // We're not able to do anything since we weren't given a number.
            return false;
        }//end if
    }//end add()

    
    /**
     * Remove grist from the gristArray
     * 
     * Removes the specified amount of the specified grist from the
     * gristArray.
     * 
     * Returns false if the grist is not present to be removed.  This
     * could be useful for transactions as you could put this function
     * in an if statement to act on whether an individual has enough
     * grist or not.
     * 
     * @param string $gristType   type of grist to remove
     * @param int    $gristAmount amount of grist to remove
     * 
     * @return boolean whether the grist could be removed or not
     */
    public function remove(string $gristType, int $gristAmount)
    {

        // Check if the passed grist amount is a positive integer.
        if (is_int($gristAmount)
            && $gristAmount >= 1
        ) {
            // If Artifact grist is removed and doesn't already exist, define it.
            if ($gristType == 'Artifact') {
                $this->_gristArray['Artifact'] = 0;
            }

            // Check if the grist type exists.
            if (isset($this->_gristArray[$gristType])) {
                // Check if we have more than or an equal amount of said grist.
                // Bypass this check if removed grist type is Artifact.
                if ($this->_gristArray[$gristType] >= $gristAmount
                    || $gristType == 'Artifact'
                ) {
                    // Checks have cleared, remove the grist!
                    $this->_gristArray[$gristType] -= $gristAmount;

                    // Check if the remaining amount is 0 so that we can clean up.
                    if ($this->_gristArray[$gristType] == 0) {
                        unset($this->_gristArray[$gristType]);
                    }

                    // We've modified something, so change the modified state.
                    $this->modified = true;

                    // The operation was successful, so return true.
                    return true;
                } else {
                    // Fail because we don't have enough grist.
                    return false;
                }//end if
            } else {
                // Fail because we don't have that grist.
                return false;
            }//end if
        } else {
            // Fail because we weren't given a number.
            return false;
        }//end if
    }//end remove()

    
    /**
     * Remove ALL of a specified grist type
     *
     * Effectively removes all of a specified type of grist by unsetting
     * the specified grist type key.
     *
     * @param string $gristType type of grist to remove
     *
     * @access public
     */
    public function removeAll(string $gristType): bool
    {
        // Check if we have the requested grist.
        if (isset($this->_gristArray[$gristType])) {
            // Since we're removing all of the grist, just unset the key.
            unset($this->_gristArray[$gristType]);

            // Removing things counts as modification, so change the modified state.
            $this->modified = true;

            // We're almost surely successful at removing this grist, so return true.
            return true;
        } else {
            // We don't have that grist, so return false.
            return false;
        }
    }//end removeAll()
}//end class
