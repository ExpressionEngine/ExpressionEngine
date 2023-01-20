<?php
/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Service\Addon;

use ExpressionEngine\Service\Addon as Core;
use ExpressionEngine\Addons\Pro\Service\Dashboard\DashboardWidgetInterface;
use ExpressionEngine\Addons\Pro\Service\Prolet\ProletInterface;
use ExpressionEngine\Addons\Pro\Service\Prolet\InitializableProletInterface;

/**
 * Addon Service
 */
class Addon extends Core\Addon
{
    /**
     * Has a pro.* file?
     *
     * @return bool TRUE of it does, FALSE if not
     */
    public function hasProlet()
    {
        $files = $this->getFilesMatching('pro.*.php');
        $this->requireProlets($files);

        return ! empty($files);
    }

    /**
     * Gets an array of the prolet classes
     *
     * @return array An array of classes
     */
    public function getProletClasses()
    {
        $files = $this->getFilesMatching('pro.*.php');

        return $this->requireProlets($files);
    }

    /**
     * Get an associative array of names of each prolet. Maps the prolet's
     * shortname to it's display name. The provider file is first checked for
     * the display name in the `prolet` key, falling back on the `getName()`
     * method.
     *
     * @return array An associative array of shortname to display name for each prolet.
     */
    public function getProletNames()
    {
        $names = array();

        $prolets = $this->get('prolets');

        foreach ($this->getFilesMatching('pro.*.php') as $path) {
            $prolet_name = preg_replace('/pro.(.*?).php/', '$1', basename($path));
            $names[$prolet_name] = (isset($prolets[$prolet_name]['name'])) ? $prolets[$prolet_name]['name'] : $this->getName();
        }

        return $names;
    }

    /**
     * Includes each prolet via PHP's `require_once` command and returns an
     * array of the classes that were included.
     *
     * @param array $files An array of file names
     * @return array An array of classes
     */
    protected function requireProlets(array $files)
    {
        $classes = array();

        foreach ($files as $path) {
            require_once $path;
            $class = preg_replace('/pro.(.*?).php/', '$1', basename($path));
            $classes[$class] = $this->getFullyQualified(ucfirst($class) . '_pro');
        }

        return $classes;
    }

    /**
     * Install, update or remove dashboard widgets provided by add-on
     */
    public function updateDashboardWidgets($remove_all = false)
    {
        //build the widgets list out of present files
        $widget_source = $this->getProvider()->getPrefix();
        $widgets = [];
        foreach ($this->getFilesMatching('widgets/*.*') as $path) {
            if (preg_match('/widgets\/(.*).(html|php)/', $path, $matches)) {
                $widgets[$matches[1] . '.' . $matches[2]] = [
                    'widget_file' => $matches[1],
                    'widget_type' => $matches[2],
                    'widget_source' => $widget_source
                ];
            }
        }

        //is anything already installed?
        //if something is not in the list, remove it
        $widgets_q = ee()->db->select('widget_id, widget_type, widget_file')
            ->from('dashboard_widgets')
            ->where('widget_source', $widget_source)
            ->get();
        if ($widgets_q->num_rows() > 0) {
            foreach ($widgets_q->result_array() as $row) {
                $key = $row['widget_file'] . '.' . $row['widget_type'];
                if (!isset($widgets[$key]) || $remove_all) {
                    ee()->db->where('widget_id', $row['widget_id']);
                    ee()->db->delete('dashboard_widgets');

                    ee()->db->where('widget_id', $row['widget_id']);
                    ee()->db->delete('dashboard_layout_widgets');
                } else {
                    unset($widgets[$key]);
                }
            }
        }

        //is still something in the list? install those
        if (!$remove_all && !empty($widgets)) {
            foreach ($widgets as $widget) {
                ee()->db->insert('dashboard_widgets', $widget);
                $layout = [
                    'widget_id' => ee()->db->insert_id()
                ];
                $layouts_q = ee()->db->select('layout_id')->from('dashboard_layouts')->get();
                foreach ($layouts_q->result_array() as $row) {
                    ee()->db->insert('dashboard_layout_widgets', array_merge($layout, ['layout_id' => $row['layout_id']]));
                }
            }
        }
    }

    /**
     * Install, update or remove prolets provided by add-on
     */
    public function updateProlets($remove_all = false)
    {
        $prolets = [];
        $source = $this->getProvider()->getPrefix();

        //build the prolets list out of present files
        if ($this->hasProlet()) {
            $classes = $this->getProletClasses();
            foreach ($classes as $class) {
                if (self::implementsProletInterface($class)) {
                    $prolets[$class] = [
                        'source' => $source,
                        'class' => $class
                    ];
                }
            }
        }

        //is anything already installed?
        //if something is not in the list, remove it
        $existingProlets = ee('Model')->get('pro:Prolet')->filter('source', $source)->all();
        foreach ($existingProlets as $existingProlet) {
            if (!isset($prolets[$existingProlet->class]) || $remove_all) {
                $existingProlet->delete();
            } else {
                unset($prolets[$existingProlet->class]);
            }
        }

        //is still something in the list? install those
        if (!$remove_all && !empty($prolets)) {
            //make sure we have dock
            $dock = ee('Model')->get('pro:Dock')->first();
            if (empty($dock)) {
                $dock = ee('Model')->make('pro:Dock', ['site_id' => ee()->config->item('site_id')])->save();
            }

            foreach ($prolets as $prolet) {
                $proletModel = ee('Model')->make('pro:Prolet');
                $proletModel->set($prolet);
                $proletModel->Dock = $dock;
                $proletModel->save();
            }
        }
    }

    /**
     * Returns whether or not a given class implements DashboardWidgetInterface
     *
     * @param string Full class name
     * @return boolean
     */
    public static function implementsDashboardWidgetInterface($class)
    {
        if (!class_exists($class)) {
            return false;
        }

        $interfaces = class_implements($class);

        return isset($interfaces[DashboardWidgetInterface::class]);
    }

    /**
     * Returns whether or not a given class implements ProletInterface
     *
     * @param string Full class name
     * @return boolean
     */
    public static function implementsProletInterface($class)
    {
        $interfaces = class_implements($class);

        return isset($interfaces[ProletInterface::class]);
    }

    /**
     * Returns whether or not a given class implements InitializableProletInterface
     *
     * @param string Full class name
     * @return boolean
     */
    public static function implementsInitializableProletInterface($class)
    {
        $interfaces = class_implements($class);

        return isset($interfaces[InitializableProletInterface::class]);
    }
}

// EOF
