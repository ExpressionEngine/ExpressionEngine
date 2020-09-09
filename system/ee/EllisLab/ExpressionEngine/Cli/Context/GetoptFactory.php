<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace EllisLab\ExpressionEngine\Cli\Context;

use EllisLab\ExpressionEngine\Cli\Context\Getopt;

/**
 *
 * A factory to create Getopt objects.
 *
 * @package Aura.Cli
 *
 */
class GetoptFactory
{
    /**
     *
     * A getopt parser.
     *
     * @var GetoptParser
     *
     */
    protected $getopt_parser;

    /**
     *
     * Constructor.
     *
     * @param GetoptParser $getopt_parser A getopt parser.
     *
     */
    public function __construct(GetoptParser $getopt_parser)
    {
        $this->getopt_parser = $getopt_parser;
    }

    /**
     *
     * Returns the getopt parser.
     *
     * @return GetoptParser
     *
     */
    public function getGetoptParser()
    {
        return $this->getopt_parser;
    }

    /**
     *
     * Returns a new Getopt instance.
     *
     * @param array $input The command line input array, as from $argv.
     *
     * @param array $options An options defintion array.
     *
     * @return Getopt
     *
     */
    public function newInstance(array $input, array $options)
    {
        $this->getopt_parser->setOptions($options);
        $this->getopt_parser->parseInput($input);
        return new Getopt(
            $this->getopt_parser->getValues(),
            $this->getopt_parser->getErrors()
        );
    }
}
