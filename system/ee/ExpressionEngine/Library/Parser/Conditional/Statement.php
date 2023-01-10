<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Parser\Conditional;

/**
 * Core Conditional Statement
 *
 * This class is used to correctly group the different parts of an if
 * statement and to allow for intelligent removal of any branch where
 * possible.
 */
class Statement
{
    protected $parser;

    protected $last_could_eval = true;
    protected $all_previous_could_eval = true;

    protected $last_result = true;
    protected $output_has_if = false;
    protected $encountered_true_condition = false;

    protected $done = false;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Add the if portion of the statement
     *
     * @param String $condition	The boolean condition
     * @param Bool   $can_eval	Is evaluatable?
     * @return Bool  Should the body of this branch be output?
     */
    public function addIf(BooleanExpression $expression)
    {
        $can_eval = $expression->canEvaluate();

        if ($can_eval) {
            $this->evaluate($expression);
        } else {
            $this->parser->outputLastAnnotation();
            $this->outputCondition($expression->stringify());
        }

        $this->setLastCouldEval($can_eval);

        return $this->shouldAddBody();
    }

    /**
     * Add the elseif portion of the statement
     *
     * @param String $condition	The boolean condition
     * @param Bool   $can_eval	Is evaluatable?
     * @return Bool  Should the body of this branch be output?
     */
    public function addElseIf(BooleanExpression $expression)
    {
        if ($this->isDone() || $this->encountered_true_condition) {
            return false;
        }

        $can_eval = $expression->canEvaluate();

        if ($can_eval) {
            $result = $this->evaluate($expression);

            // If not all previous ones have evaluated, then we can't
            // make a determination on a true branch since a previous one may
            // also be true. However, we do know that subsequent ones cannot be
            // reached, so we can remove them. This then becomes the else branch,
            // making it easy to parse on subsequent passes.
            if (! $this->all_previous_could_eval && $result == true) {
                $this->parser->output('{if:else}');
            }
        } else {
            $this->parser->outputLastAnnotation();
            $this->outputCondition($expression->stringify());
        }

        $this->setLastCouldEval($can_eval);

        return $this->shouldAddBody();
    }

    /**
     * Add the else portion of the statement
     *
     * @return Bool  Should the body of this branch be output?
     */
    public function addElse()
    {
        // Don't process if done or we've encountered a condition
        // that evaluated to TRUE. Even if other ones have not been
        // evaluated, that one will shortcut our else, so we prune
        // the else branch.
        if ($this->isDone() || $this->encountered_true_condition) {
            return false;
        }

        if (! $this->all_previous_could_eval) {
            $this->parser->output('{if:else}');
        }

        $this->last_result = true;
        $this->setLastCouldEval(true);

        return $this->shouldAddBody();
    }

    /**
     * Check our state to figure out if this last statement resulted in
     * the branch being pruned. If so, we won't output.
     *
     * @return Bool  Should the body of this branch be output?
     */
    public function shouldAddBody()
    {
        // done? definitely don't add the body
        if ($this->done) {
            return false;
        }

        // eval'd and false? don't show the body
        if ($this->last_could_eval == true && $this->last_result == false) {
            return false;
        }

        return true;
    }

    /**
     * Close the conditional
     *
     * @return void
     */
    public function closeIf()
    {
        if ($this->output_has_if) {
            $this->parser->output('{/if}');
        }
    }

    /**
     * Re-output an EE condition
     *
     * Re-outputs a boolean expression as an ee conditional, with
     * a check to see if the if branch was pruned. In that case the
     * first elseif beomes an if.
     *
     * @param String $condition The boolean expression
     * @return void
     */
    protected function outputCondition($condition)
    {
        // otherwise we print it.
        if (! $this->output_has_if) {
            $this->output_has_if = true;
            $this->parser->output('{if ' . $condition . '}');
        } else {
            $this->parser->output('{if:elseif ' . $condition . '}');
        }
    }

    /**
     * Check if a branch evaluated as true. If so, we don't need to
     * output anything else.
     *
     * @return bool No further work needed?
     */
    protected function isDone()
    {
        // Everything has eval'd and we've hit a true one?
        // That means we're done here.
        if ($this->all_previous_could_eval && $this->last_could_eval && $this->last_result == true) {
            $this->done = true;
        }

        return $this->done;
    }

    /**
     * Set the evaluation state of the last expression.
     *
     * Also track if all previous expressions could evaluate. This
     * metric lets us know if we can prune branches.
     *
     * @return bool No further work needed?
     */
    protected function setLastCouldEval($value)
    {
        $this->last_could_eval = $value;

        if ($value === false) {
            $this->all_previous_could_eval = false;
        }
    }

    /**
     * Evaluate a boolean expression
     *
     * @param String $condition The expression to evaluate
     * @return Bool  The result
     */
    protected function evaluate($expression)
    {
        $result = (bool) $expression->evaluate();

        if ($result === true) {
            $this->encountered_true_condition = true;
        }

        $this->last_result = $result;

        return $result;
    }
}

// EOF
