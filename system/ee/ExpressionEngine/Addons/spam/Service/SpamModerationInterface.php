<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Spam\Service;

/**
 * Spam Moderation
 */
interface SpamModerationInterface
{
    /**
     * Approve items in the queue (mark as HAM)
     *
     * @param  object $entity model object for the entity in question
     * @param  mixed $optional_data optional data stored with the item when moderated as spam
     * @return void
     */
    public function approve($entity, $optional_data);

    /**
     * Reject items in the queue (mark as SPAM)
     *
     * @param  object $entity model object for the entity in question
     * @param  mixed $optional_data optional data stored with the item when moderated as spam
     * @return void
     */
    public function reject($entity, $optional_data);
}
