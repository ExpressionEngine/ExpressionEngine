<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\ConditionalFields;

/**
 * Conditional Fields Factory
 */
class Factory
{
    protected static $evaluationRules;
    protected static $installedFieldtypes;

    /**
     * Make new or use existing evaluation rule
     *
     * @param string $ruleName
     * @param string $fieldTypeName
     * @return EvaluationRules\EvaluationRuleInterface
     */
    public function make($ruleName, $fieldTypeName = '')
    {
        $ruleClass = '';
        // addons can overwrite the rules, so we check those first
        if ($fieldTypeName != '') {
            if (empty(self::$installedFieldtypes)) {
                ee()->load->library('api');
                ee()->legacy_api->instantiate('channel_fields');
                self::$installedFieldtypes = ee()->api_channel_fields->fetch_all_fieldtypes();
            }
            $addon = ee('Addon')->get(self::$installedFieldtypes[$fieldTypeName]['package']);
            if (!empty($addon)) {
                $ruleClass = $addon->getEvaluationRuleClass($ruleName);
            }
            if (isset($evaluationRules[$ruleClass])) {
                return $evaluationRules[$ruleClass];
            }
        }

        if (empty($ruleClass) || !class_exists($ruleClass)) {
            $ruleClass = "\\ExpressionEngine\\Service\\ConditionalFields\\EvaluationRules\\" . ucfirst($ruleName);
            if (isset($evaluationRules[$ruleClass])) {
                return $evaluationRules[$ruleClass];
            }
        }

        if (!class_exists($ruleClass)) {
            throw new \Exception(
                sprintf('Conditional Evaluation Rule "%s" does not exist', ucfirst($ruleName))
            );
        }

        if (!self::implementsInterface($ruleClass)) {
            throw new \Exception(
                sprintf('Conditional Evaluation Rule "%s" is not supported', ucfirst($ruleName))
            );
        }

        $evaluationRules[$ruleClass] = new $ruleClass();

        return $evaluationRules[$ruleClass];
    }

    /**
     * Returns whether or not a given class implements EvaluationRuleInterface
     *
     * @param string Full class name
     * @return boolean
     */
    private static function implementsInterface($class)
    {
        $interfaces = class_implements($class);

        return isset($interfaces[EvaluationRules\EvaluationRuleInterface::class]);
    }
}

// EOF
