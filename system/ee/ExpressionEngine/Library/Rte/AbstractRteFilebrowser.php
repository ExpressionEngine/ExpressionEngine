<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Rte;

/**
 * Abstract RTE File Browser class
 */
abstract class AbstractRteFilebrowser implements RteFilebrowserInterface
{
    /**
     * Function which will be called when displaying field
     * Should include all necessary JS/CSS and define `window.Rte_browseImages` JS function
     *
     * @param String $uploadDir Upload directory identifier
     * @return void
     */
    abstract public function addJs($uploadDir);

    /**
     * Function which will replace extra EE tags with their corresponding values
     *
     * @param String $data Field data
     * @return String $data Field data
     */
    public function replaceTags($data)
    {
        return $data;
    }

    /**
     * Function which will replace inserted URLs with special values, if those are needed for storage
     *
     * @param String $data Field data
     * @return String $data Field data
     */
    public function replaceUrls($data)
    {
        return $data;
    }

    /**
     * Return array of upload destinations available
     * [
     *  [destination_id => destination_label]
     * ]
     *
     * @return Array
     */
    abstract public function getUploadDestinations();
}
