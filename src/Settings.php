<?php

namespace Markofly\Settings;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Class Settings
 * @package Markofly\Settings
 */
class Settings
{

    /**
     * @var
     */
    protected $database;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $fields;

    /**
     * @var null|array
     */
    protected $allFields = null;

    /**
     * Settings constructor.
     * @param $database
     * @param array $config
     */
    public function __construct($database, $config = [])
    {
        $this->config = $config;
        $this->database = $database;
        $this->fields = $this->getConfigSettingsFields();
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        $value = $this->getValue($key);

        if ($value === null) {
            $value = $default;
        }

        return $value;
    }

    /**
     * @param $key
     * @param $value
     */
    public function save($key, $value)
    {
        $this->storeValueToDatabase($key, $value);
    }

    /**
     * @param $key
     * @return mixed|null
     */
    protected function getValue($key)
    {
        // Get value from cache if exists
        $cacheValue = $this->getValueFromCache($key);
        if ($cacheValue) {
            return $cacheValue;
        }

        // Get value from database if exists
        $databaseValue = $this->getValueFromDatabase($key);
        if ($databaseValue) {
            return $databaseValue;
        }

        // Get value from config if exists
        $configValue = $this->getValueFromConfig($key);
        if ($configValue) {
            return $configValue;
        }

        return null;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function getValueFromCache($key)
    {
        if (!$this->isCacheEnabled()) {
            return null;
        }

        $cacheKey = $this->getCachePrefix() . $key;

        if (!Cache::has($cacheKey)) {
            return null;
        }

        return Cache::get($cacheKey);
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function getValueFromDatabase($key)
    {
        $value = $this->database->table($this->getDatabaseTableName())->where('key', $key)->first(['value']);

        if (!$value) {
            return null;
        }

        $this->storeValueToCache($key, $value->value);

        return $value->value;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function getValueFromConfig($key)
    {
        $fields = $this->fields;

        if (!array_key_exists($key, $fields)) {
            return null;
        }

        $fieldData = $fields[$key];
        if (!array_key_exists('default', $fieldData)) {
            return null;
        }

        $this->storeValueToCache($key, $fieldData['default']);

        return $fieldData['default'];
    }

    /**
     * @param $key
     * @param $value
     */
    protected function storeValueToDatabase($key, $value)
    {
        $keyField = $this->database->table($this->getDatabaseTableName())->where('key', $key)->first();

        if ($keyField) {
            $this->database->table($this->getDatabaseTableName())->where('key', $key)->update(['value' => $value]);
        } else {
            $this->database->table($this->getDatabaseTableName())->insert(
                ['key' => $key, 'value' => $value]
            );
        }

        $this->removeItemFromCache($key);
    }

    /**
     * @return string
     */
    protected function getDatabaseTableName()
    {
       return isset($this->config['settings_table_name']) ? $this->config['settings_table_name'] : 'markofly_settings';
    }

    /**
     * @return bool
     */
    protected function isCacheEnabled()
    {
        return (isset($this->config['use_caching']) ? $this->config['cache_prefix'] : false) === true;
    }

    /**
     * @return string
     */
    protected function getCachePrefix()
    {
        return isset($this->config['cache_prefix']) ? $this->config['cache_prefix'] : 'markofly' . '_';
    }

    /**
     * @param $key
     * @return string
     */
    protected function getCacheKey($key)
    {
        return $this->getCachePrefix() . $key;
    }

    /**
     * @param $key
     * @param $value
     */
    protected function storeValueToCache($key, $value)
    {
        if ($this->isCacheEnabled() === true) {
            $cacheKey = $this->getCacheKey($key);
            Cache::forever($cacheKey, $value);
        }
    }

    /**
     * @param $key
     */
    protected function removeItemFromCache($key)
    {
        Cache::forget($this->getCacheKey($key));
    }

    /**
     * @return array
     */
    protected function getConfigSettingsFields()
    {
        return isset($this->config['fields']) ? $this->config['fields'] : [];
    }

    /**
     * @return array
     */
    protected function getConfigSettingsKeys()
    {
        return array_keys($this->getConfigSettingsFields());
    }

    /**
     * @return Collection
     */
    protected function getDatabaseSettingsFields()
    {
        return $this->database->table($this->getDatabaseTableName())->get();
    }

    /**
     * @return array
     */
    protected function getDatabaseSettingsKeys()
    {
        return $this->database->table($this->getDatabaseTableName())->select('key')->pluck('key')->toArray();
    }

    /**
     * Get all settings from database and config
     * @return array
     */
    public function getAllSettings()
    {
        if ($this->allFields !== null) {
            return $this->allFields;
        }

        $configFieldsKeys = $this->getConfigSettingsKeys();
        $databaseFieldsKeys = $this->getDatabaseSettingsKeys();

        $configFiedlsNotInDatabase = array_diff($configFieldsKeys, $databaseFieldsKeys);

        $configValues = $this->getConfigSettingsFields();
        $databaseValues = $this->getDatabaseSettingsFields();

        foreach ($databaseValues as $databaseValue) {

            $this->allFields[$databaseValue->key] = [
                'value' => $databaseValue->value,
                'default' => isset($configValues[$databaseValue->key]['default']) ? $configValues[$databaseValue->key]['default'] : null,
                'group' => [
                    'label' => isset($configValues[$databaseValue->key]['group']['label']) ? $configValues[$databaseValue->key]['group']['label'] : 'No group',
                    'slug' => isset($configValues[$databaseValue->key]['group']['slug']) ? $configValues[$databaseValue->key]['group']['slug'] : 'no-group',
                ],
            ];
        }

        foreach ($configFiedlsNotInDatabase as $item) {
            $this->allFields[$item] = [
                'value' => null,
                'default' => isset($configValues[$item]['default']) ? $configValues[$item]['default'] : null,
                'group' => [
                    'label' => isset($configValues[$item]['group']['label']) ? $configValues[$item]['group']['label'] : 'No group',
                    'slug' => isset($configValues[$item]['group']['slug']) ? $configValues[$item]['group']['slug'] : 'no-group',
                ],
            ];
        }

        return $this->allFields;
    }

}
