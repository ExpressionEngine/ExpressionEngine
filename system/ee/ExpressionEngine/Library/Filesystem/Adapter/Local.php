<?php

namespace ExpressionEngine\Library\Filesystem\Adapter;

use ExpressionEngine\Dependency\League\Flysystem;
use ExpressionEngine\Service\Validation\ValidationAware;

class Local extends Flysystem\Adapter\Local implements AdapterInterface, ValidationAware
{
    use AdapterTrait;

    protected $rootExists = true;

    protected $_validation_rules = [
        'server_path' => 'required',
        'url' => 'required|validateUrl',
    ];

    /**
     * Constructor.
     *
     * @param string $root
     * @param int    $writeFlags
     * @param int    $linkHandling
     * @param array  $permissions
     *
     * @throws \LogicException
     */
    public function __construct($settings)
    {
        $this->settings = $settings;
        $root = $settings['path'];
        $writeFlags = \LOCK_EX;
        $linkHandling = self::DISALLOW_LINKS;
        $permissions = [];

        $root = \is_link($root) ? \realpath($root) : $root;
        $this->permissionMap = \array_replace_recursive(static::$permissions, $permissions);

        // Overriding parent constructor to remove this behavior of creating the root if it does not exist
        // $this->ensureDirectory($root);
        if (!\is_dir($root) || !\is_readable($root)) {
            //throw an exception if root is not valid, but only if it's not validation request
            if ($this->settings['allow_missing'] ?? false) {
                $this->rootExists = false;
            }else{
                throw new \LogicException('The root path ' . $root . ' is not readable.');
            }
        }
        $this->setPathPrefix($root);
        $this->writeFlags = $writeFlags;
        $this->linkHandling = $linkHandling;

    }

    public static function getSettingsForm($settings)
    {
        return [
            [
                'title' => 'upload_url',
                'desc' => 'upload_url_desc',
                'fields' => [
                    'url' => [
                        'type' => 'text',
                        'value' => $settings['url'] ?? '{base_url}',
                        'required' => true
                    ]
                ]
            ],
            [
                'title' => 'upload_path',
                'desc' => 'upload_path_desc',
                'fields' => [
                    'server_path' => [
                        'type' => 'text',
                        'value' => $settings['server_path'] ?? '{base_path}',
                        'required' => true
                    ]
                ]
            ]
        ];
    }

    /**
     * Make sure URL is not submitted with the default value
     */
    public function validateUrl($key, $value, $params, $rule)
    {
        if ($value == 'http://') {
            $rule->stop();

            return lang('valid_url');
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function has($path)
    {
        return $this->rootExists && parent::has($path);
    }

    /**
     * @inheritdoc
     */
    public function deleteDir($path)
    {
        return $this->attemptFastDelete($path) || parent::deleteDir($path);
    }

    /**
     * Attempt to delete a file using the OS method
     *
     * We can't always do this, but it's much, much faster than iterating
     * over directories with many children.
     *
     * @param bool whether or not the fast system delete could be done
     */
    protected function attemptFastDelete($path)
    {
        $path = $this->applyPathPrefix($path);

        $delete_name = sha1($path . '_delete_' . mt_rand());
        $delete_path = PATH_CACHE . $delete_name;

        // Suppressing potential warning when renaming a directory to one that already exists.
        @rename($path, $delete_path);

        if (file_exists($delete_path) && is_dir($delete_path)) {
            $delete_path = @escapeshellarg($delete_path);

            if (DIRECTORY_SEPARATOR == '/') {
                @exec("rm -rf {$delete_path}");
            } else {
                @exec("rd /s /q {$delete_path}");
            }

            return  !file_exists($delete_path);
        }

        return false;
    }

}
