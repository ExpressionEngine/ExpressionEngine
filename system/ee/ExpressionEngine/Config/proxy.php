<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Reverse Proxy / Load Balancer
 * List of IP addresses or IP ranges / masks 
 *
 * If the server is behind reverse proxy or load balancer,
 * the system would need special configuration to discover user's real IP address.
 * If the IP address as passed in by server is one of the values below,
 * the system will start looking into headers to determine real IP.
 */

return array(
    //'103.21.244.0/22',
    //'2400:cb00::/32',
);

// EOF
