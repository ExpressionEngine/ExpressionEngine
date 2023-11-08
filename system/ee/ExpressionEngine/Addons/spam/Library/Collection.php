<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Spam\Library;

/**
 * Spam Collection
 */
class Collection
{
    public $documents = array();
    public $vocabulary = array();
    public $vectorizers = array();
    public $idf_lookup = array();
    public $corpus = "";
    public $limit = 1000;

    /**
     * Register the rules we want to use for vectorizing
     *
     * @access public
     * @param array 	 $transformations  The transformations to use when
     * 					 				   calculating the vector
     * @return void
     */
    public function __construct($transformations = array())
    {
        foreach ($transformations as $transformation) {
            $this->register($transformation);
        }
    }

    /**
     * Fit the vectorizer to our collection of sources, will return an array of
     * vectorized sources.
     *
     * @param array $sources Array of source strings to fit
     * @access public
     * @return array
     */
    public function fitTransform($sources)
    {
        $result = array();

        foreach ($sources as $source) {
            $result[] = $this->transform($source);
        }

        return $result;
    }

    /**
     * Computes a vector of feature values suitable for using with Naive Bayes
     *
     * @param string $source The string to vectorize
     * @access public
     * @return array An array of floats
     */
    public function transform($source)
    {
        $vector = array();

        if (! empty($this->vectorizers)) {
            foreach ($this->vectorizers as $transform) {
                $vectorized = $transform->vectorize($source);

                if (! is_array($vectorized)) {
                    $vectorized = array($vectorized);
                }

                $vector = array_merge($vector, $vectorized);
            }
        }

        return $vector;
    }

    /**
     * Register a vectorizer rule
     *
     * @param mixed $class
     * @access public
     * @return void
     */
    public function register($obj)
    {
        if (! $obj instanceof Vectorizer) {
            throw new InvalidArgumentException(get_class($obj) . ' must implement the Vectorizer interface.');
        }

        $this->vectorizers[] = $obj;
    }
}

// EOF
