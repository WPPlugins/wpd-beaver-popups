<?php

namespace WPD\BeaverPopups\Helpers;

/**
 * Class OptionsHelper simplifies access to options.
 * Wraps get_option, update_option, delete_option.
 * We need it to simplify encryption of wp options
 * in case if customer's wp db is compromised.
 *
 * @package WPD\BeaverPopups\Helpers
 */
class OptionsHelper
{

    /**
     * Namespace of our options inside wp_options table.
     */
    const PREFIX = 'WPD\BeaverPopups:';

    /**
     * Check if option requires encryption/decryption
     *
     * @param string $key
     *
     * @return bool
     */
    protected static function isEncryptedOption($key)
    {
        return substr($key, 0, 1) === '_';
    }

    /**
     * Gets option value.
     * If $key value is prefixed with '_' (e.g. '_something'),
     * decryption will be applied.
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    public static function get($key, $default = '')
    {
        $needEncryption = self::isEncryptedOption($key);
        $key = preg_replace('/^_/', '', $key);
        $value = get_option(self::PREFIX . $key, $default);

        if ($value && $needEncryption) {
            $value = EncryptionHelper::decrypt($value);
        }

        return $value;
    }

    /**
     * Sets option value explicitly.
     * If $key value is prefixed with '_' (e.g. '_something'),
     * encryption will be applied.
     *
     * @param string $key
     * @param string $value
     *
     * @return bool
     */
    public static function set($key, $value)
    {
        $needEncryption = self::isEncryptedOption($key);
        $key = preg_replace('/^_/', '', $key);

        if ($value && $needEncryption) {
            $value = EncryptionHelper::encrypt($value);
        }

        return update_option(self::PREFIX . $key, $value);
    }

    /**
     * Delete option
     *
     * @param $key
     *
     * @return bool
     */
    public static function del($key)
    {
        $key = preg_replace('/^_/', '', $key);
        return delete_option(self::PREFIX . $key);
    }

    /**
     * Updates option value.
     * Sets non empty $value, or removes option if $value is empty.
     * If $key value is prefixed with '_' (e.g. '_something'),
     * encryption will be applied.
     *
     * @param $key
     * @param $value
     *
     * @return bool
     */
    public static function update($key, $value)
    {
        return $value ? self::set($key, $value) : self::del($key);
    }
}
