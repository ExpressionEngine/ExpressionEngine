<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\File;

/**
 * Shared filename rules for generating and deleting dynamic image manipulations.
 */
class ManipulationFilename
{
    /**
     * The source filename stem, adjusted for the output format.
     *
     * @var string
     */
    private $name;

    /**
     * The output extension, including its leading dot when present.
     *
     * @var string
     */
    private $extension;

    /**
     * The image operation used to generate the derivative.
     *
     * @var string
     */
    private $manipulation;

    /**
     * Create filename rules for a source image and manipulation.
     *
     * @param  string  $filename  Source filename, including its original extension.
     * @param  string  $manipulation  Image operation used to generate the derivative.
     * @return void
     */
    public function __construct($filename, $manipulation)
    {
        $extension = strrchr($filename, '.');
        $this->name = ($extension === false) ? $filename : substr($filename, 0, -strlen($extension));
        $this->extension = ($extension === false) ? '' : $extension;
        $this->manipulation = $manipulation;

        if (in_array($manipulation, ['webp', 'avif'])) {
            $this->name .= '_' . $this->extension;
            $this->extension = '.' . $manipulation;
        }
    }

    /**
     * Get the cache filename prefix, truncating the stem to 150 bytes.
     *
     * @return string
     */
    public function getPrefix()
    {
        return substr($this->name, 0, 150) . '_' . $this->manipulation . '_';
    }

    /**
     * Get the cache filename for the supplied processing and output parameters.
     *
     * The parameter order is preserved when calculating the cache hash.
     *
     * @param  array  $params
     * @return string
     */
    public function getFilename(array $params)
    {
        return $this->getPrefix() . md5(serialize($params)) . $this->extension;
    }

    /**
     * Get the manipulation filename without a parameter hash or stem truncation.
     *
     * The fieldtype carries this value between chained modifiers to build subsequent filenames.
     *
     * @return string
     */
    public function getUnhashedFilename()
    {
        return $this->name . '_' . $this->manipulation . $this->extension;
    }

    /**
     * Determine whether a cache filename belongs to this source and manipulation.
     *
     * Any valid parameter hash is accepted so cleanup can remove all variants.
     *
     * @param  string  $filename  Candidate cache filename without its directory.
     * @return bool
     */
    public function matches($filename)
    {
        $pattern = '/\A' . preg_quote($this->getPrefix(), '/') . '[a-f0-9]{32}'
            . preg_quote($this->extension, '/') . '\z/';

        return preg_match($pattern, $filename) === 1;
    }
}
