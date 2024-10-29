<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\View;

use ExpressionEngine\Core\Provider;

/**
 * Stub
 */
class Stub extends View
{
    /**
     * Name of folder where stubs are located
     *
     * @var string
     */
    public $generatorFolder;

    protected $theme;

    protected $templateEngine;

    protected $templateType = 'webpage';

    /**
     * @var string A copy of the path argument sent to `render()` this avoids
     * a scope issue where `extract()` could override that value and try to
     * include something unintended.
     */
    private $path_for_parse;

    public function setTheme($theme)
    {
        $this->theme = $theme;

        return $this;
    }

    public function setTemplateEngine($engine)
    {
        $this->templateEngine = $engine;

        return $this;
    }

    public function getTemplateEngine()
    {
        return $this->templateEngine;
    }

    public function setTemplateType($type)
    {
        $this->templateType = $type;

        return $this;
    }

    /**
     * Create a new stub object. Because stub path format is different, we need to override this
     *
     * @param  string $view Subview name, potentially with prefix
     * @return Stub         The subview instance
     */
    protected function make($view)
    {
        $provider = $this->provider;
        $generatorFolder = $this->generatorFolder;

        if (strpos($view, ':')) {
            $parts = explode(':', $view, 3);
            $prefix = $parts[0];
            if (isset($parts[2])) {
                $generatorFolder = $parts[1];
                $view = $parts[2];
            } else {
                $generatorFolder = '';
                $view = $parts[1];
            }
            $provider = $provider->make('App')->get($prefix);
        }

        $stub = new static($view, $provider);
        $stub->generatorFolder = $generatorFolder;
        $stub->setTemplateType($this->templateType);

        if ($this->theme) {
            $stub->setTheme($this->theme);
        }

        if ($this->templateEngine) {
            $stub->setTemplateEngine($this->templateEngine);
        }

        return $stub;
    }

    /**
     * Loads, renders, and (optionally) returns a sub-view
     *
     * @param String $view The name of the sub-view
     * @param Array  $vars Additional variables to pass to the sub-view
     * @param bool  $return Whether to return a string or output the results
     * @return String The parsed sub-view
     */
    public function embed($view, $vars = array(), $disable = array())
    {
        if (empty($vars)) {
            $vars = array();
        }

        $vars = array_merge($this->processing, $vars);
        $view = $this->make($view)->disable($disable);

        // Special case for variable modifiers
        if(array_key_exists('modifiers', $vars) && is_array($vars['modifiers'])) {
            $vars['modifiers_string'] = trim(array_reduce(array_keys($vars['modifiers']), function($carry, $modifier) use($vars) {
                $usePrefix = count($vars['modifiers']) > 1;
                $parameters = $vars['modifiers'][$modifier];
                $parameterString = array_reduce(array_keys($parameters), function($carry, $parameter) use($parameters, $usePrefix, $modifier) {
                    return $carry .= (($usePrefix) ? "$modifier:$parameter" : $parameter) . "='{$parameters[$parameter]}'";
                }, '');
                $carry .= ":$modifier $parameterString";
                return $carry;
            }, ''));
        }else{
            $vars['modifiers_string'] = '';
        }

        $out = $view->render($vars);

        //indent everything at the same level
        $indent = 0;
        $buffer = ob_get_contents();
        if (!empty($buffer)) {
            $bufferLines = explode("\n", $buffer);
            $indent = strlen(end($bufferLines));
        }

        $lines = explode("\n", $out);
        foreach ($lines as $i => &$line) {
            if ($i > 0) {
                $line = str_repeat(' ', $indent) . $line;
            }
        }
        $out = implode("\n", $lines);

        ob_start();

        echo $out;

        ob_end_flush();
    }

    /**
     * Load a view file, replace variables, and return the result
     *
     * @param  String $path Full path to a view file
     * @param  Array  $vars Variables to replace in the view file
     * @return String Parsed view file
     */
    protected function parse($path, $vars)
    {
        $this->path_for_parse = $path;

        // Extract all variables to local scope so they can be used in the view
        extract($vars);

        ob_start();

        if ((version_compare(PHP_VERSION, '5.4.0') < 0 && @ini_get('short_open_tag') == false)) {
            echo eval('?>' . preg_replace("/;*\s*\?>/", "; ?>", str_replace('<?=', '<?php echo ', file_get_contents($this->path_for_parse))));
        } else {
            // Include the stub file in a scope with the variables available
            include($this->path_for_parse);
        }

        $buffer = ob_get_contents();
        // clean up extra lines from php tags
        $buffer = preg_replace('/\n[ \t]+\n/i', "\n", $buffer);

        ob_end_clean();

        return $buffer;
    }

    /**
     * Get the full server path to the stub file
     *
     * @return string The full server path
     */
    protected function getPath()
    {
        // do not allow any path traversal
        if (strpos($this->path, '..') !== false) {
            throw new \Exception('Invalid stub path: ' . htmlentities($this->path));
        }

        ee()->load->library('api');
        ee()->legacy_api->instantiate('template_structure');

        // We will look for filenames from most specific to least specific until we find a match
        $paths = ee('View/Stub')->getGeneratorStubPaths($this->provider, $this->generatorFolder, $this->theme);
        $fileNames = array_unique(array_filter([
            $this->path . ee()->api_template_structure->file_extensions($this->templateType, $this->templateEngine),
            $this->templateEngine ? "{$this->path}.{$this->templateEngine}" : null,
            $this->path . ee()->api_template_structure->file_extensions($this->templateType),
            $this->path,
        ]));

        foreach ($paths as $path) {
            $path = rtrim($path, '/');
            foreach($fileNames as $fileName) {
                $fileName = ltrim($fileName, '/');
                $files = [
                    "$path/$fileName.php",
                    "$path/$fileName"
                ];
                // Check with and without the .php extension
                foreach($files as $file) {
                    if ((strpos($file, '..') == false) && file_exists($file)) {
                        // check for template engine agreement modify this stub's engine if the file differs
                        $engine = $this->getEngineFromPath($file);
                        if($this->templateEngine != $engine) {
                            $this->templateEngine = $engine;
                        }

                        return $file;
                    }
                }
            }
        }

        throw new \Exception('Stub file not found: ' . htmlentities($this->path));
    }

    protected function getEngineFromPath($path)
    {
        $path = rtrim($path, '.php');
        $info = ee()->api_template_structure->get_template_file_info($path);

        if(!is_null($info)) {
            return $info['engine'];
        }

        $engines = array_filter(array_keys(ee()->api_template_structure->get_template_engines()));

        foreach($engines as $engine) {
            $extensionLength = strlen($engine);
            if (substr_compare($path, $engine, -$extensionLength) === 0) {
                return $engine;
            }
        }

        return null;
    }
}
// EOF
