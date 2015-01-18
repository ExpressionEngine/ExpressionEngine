<?php

namespace EllisLab\ExpressionEngine\Service\Event;

/**
 * Interface to implement if you want to support the mixin's reflexive
 * events, where an event fired is automatically forwarded to on<EventName>
 * on your object.
 */
interface Reflexive {

	public function getEvents();

}