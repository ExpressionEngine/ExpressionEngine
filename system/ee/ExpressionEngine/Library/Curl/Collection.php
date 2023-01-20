<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Curl;

use ExpressionEngine\Library\Data\Collection;

/**
 * Curl Request Collection
 */
class RequestCollection extends Collection
{
    public $window = INF;
    public $callback = null;

    public function __construct($requests, $config = array())
    {
        $collection = array();
        $objs = array();
        $urls = array();

        foreach ($requests as $request) {
            if (is_subclass_of($request, 'Request')) {
                $objs[] = $request;

                continue;
            }

            if (filter_var($request, FILTER_VALIDATE_URL) === false) {
                throw new \Exception('Invalid request URL');
            } else {
                $urls[] = $request;
            }
        }

        if (! (empty($urls) || empty($objs))) {
            throw new \Exception('Cannot mix data types when instantiating RequestCollection');
        }

        if (! empty($urls)) {
            $collection = array_map(function ($url) {
                $method = empty($config['method']) ? 'GetRequest' : ucfirst(strtolower($config['method'])) . 'Request';
                $request = new $method($url, $config['data']);

                if (isset($config['async']) && $config['async'] === true) {
                    $request = new AsyncRequest($request);
                }

                return $request;
            }, $urls);
        }

        if (! empty($objs)) {
            $collection = $objs;
        }

        return parent::__construct($collection);
    }

    public function exec($callback = null)
    {
        if (! empty($callback)) {
            $this->callback = $callback;
        }

        $async = array();
        $sync = array();

        foreach ($this->collection as $request) {
            if ($request instanceof AsyncRequest) {
                $async[] = $request;
            } else {
                $sync[] = $request;
            }
        }

        if (! empty($async)) {
            $this->rollingCurl($async);
        }

        if (! empty($sync)) {
            array_walk($sync, function ($request) {
                if (! empty($callback)) {
                    $request->callback = $callback;
                }

                $request->exec();
            });
        }
    }

    public function setWindow($size)
    {
        $this->window = $size;
    }

    public function rollingCurl($requests)
    {
        $window = (sizeof($urls) < $this->window) ? sizeof($urls) : $this->window;
        $master = curl_multi_init();
        $curl_arr = array();
        $options = ($custom_options) ? ($std_options + $custom_options) : $std_options;

        for ($i = 0; $i < $window; $i++) {
            $ch = curl_init();
            curl_setopt_array($ch, $request[$i]->config);
            curl_multi_add_handle($master, $ch);
        }

        while ($running === true) {
            while (($execrun = curl_multi_exec($master, $running)) == CURLM_CALL_MULTI_PERFORM);
            {
                if ($execrun != CURLM_OK) {
                    break;
                }
            }

            while ($done = curl_multi_info_read($master)) {
                $info = curl_getinfo($done['handle']);
                $output = curl_multi_getcontent($done['handle']);

                if (! empty($this->callback)) {
                    call_user_func($this->callback, $output);
                } else {
                    call_user_func($requests[$i]->callback, $output);
                }

                if (isset($requests[$i + 1])) {
                    $ch = curl_init();
                    curl_setopt_array($ch, $requests[$i++]->config);
                    curl_multi_add_handle($master, $ch);
                }

                curl_multi_remove_handle($master, $done['handle']);
            }

            usleep(10000);
        }

        curl_multi_close($multi);
    }
}

// EOF
