<?php

namespace WPD\BeaverPopups\Helpers;

/**
 * Class InputHelper contains a set of methods to filter user input from HTTP request.
 *
 * @package WPD\BeaverPopups\Helpers
 */
class InputHelper
{

    /**
     * Unfiltered input hash map
     *
     * @var array
     */
    protected static $input;

    /**
     * Array of param names that are allowed to contain HTML code
     *
     * @var array
     */
    protected static $htmlAllowed = array();

    /**
     * Array of param names that should preserve slashes.
     * No stripslashes performed on those params.
     *
     * @var array
     */
    protected static $slashesPreserved = array('JSON');

    /**
     * Set input buffer.
     * If buffer not set $_REQUEST is used by default
     *
     * @param $input
     */
    public static function setInput($input)
    {
        self::$input = $input;
    }

    /**
     * Define input params that are allowed to contain HTML
     *
     * @param string/array $htmlAllowed 'post_content, comment_content' or ['post_content', 'comment_content']
     */
    public static function permitHtml($htmlAllowed)
    {
        if (is_string($htmlAllowed)) {
            $htmlAllowed = preg_split('%\s*,\s*%', $htmlAllowed);
        }
        self::$htmlAllowed = array_merge(self::$htmlAllowed, $htmlAllowed);
    }

    /**
     * Define input params that should preserve slashes
     *
     * @param string/array $htmlAllowed 'post_content, comment_content' or ['post_content', 'comment_content']
     */
    public static function preserveSlashes($slashesPreserved)
    {
        if (is_string($slashesPreserved)) {
            $slashesPreserved = preg_split('%\s*,\s*%', $slashesPreserved);
        }
        self::$slashesPreserved = array_merge(self::$slashesPreserved, $slashesPreserved);
    }

    /**
     * Inject input param value to current input buffer
     *
     * @param $param
     * @param $value
     */
    public static function setParam($param, $value)
    {
        if (self::$input) {
            self::$input[$param] = $value;
        } else {
            $_REQUEST[$param] = $value;
        }
    }

    /**
     * Inject input params to current input buffer
     *
     * @param array $params
     */
    public static function setParams($params)
    {
        foreach ($params as $key => $value) {
            self::setParam($key, $value);
        }
    }

    /**
     * Get filtered param value from current input buffer
     *
     * @param $param
     * @param string $default
     * @return mixed
     */
    public static function getParam($param, $default = '')
    {
        $input = self::$input ? self::$input : $_REQUEST;
        $value = Util::getItem($input, $param, $default);
        return self::filter($value, $param);
    }

    /**
     * Get assoc array of filtered values from current input buffer.
     * If $omitStandard then 'controller', 'action', 'module' will be omitted
     *
     * @param bool $omitStandard
     * @return array
     */
    public static function getParams($omitStandard = false)
    {
        $input = self::$input ?self::$input : $_REQUEST;
        $result = array();
        $standard = array('action', 'controller', 'module');
        foreach ($input as $key => $value) {
            if (!$omitStandard || !in_array($key, $standard)) {
                $result[$key] = self::getParam($key);
            }
        }

        return $result;
    }

    /**
     * Filter $value (trim, strip_slashes, strip_tags).
     * If $key is in array of html allowed params, then tags won't be stripped
     *
     * @param string|array|object $value
     * @param string $key
     * @return string|array
     */
    public static function filter($value, $key = '')
    {
        if (is_array($value)) {
            return self::filterArray($value);
        }
        if (is_object($value)) {
            return self::filterArray(get_object_vars($value));
        }
        if (is_int($value) || is_float($value)) {
            return $value;
        }
        if (!in_array($key, self::$htmlAllowed)) {
            $filter = FILTER_SANITIZE_STRING;
            $options = FILTER_FLAG_NO_ENCODE_QUOTES;
            $value = filter_var($value, $filter, $options);
        }
        return in_array($key, self::$slashesPreserved) ? rtrim($value) : rtrim(stripslashes($value));
    }

    /**
     * Filter $values (trim, strip_slashes, strip_tags).
     *
     * @param array(string) $values
     * @return array(string)
     */
    public static function filterArray($values)
    {
        foreach ($values as $k => $v) {
            $values[$k] = self::filter($v, $k);
        }
        return $values;
    }

    /**
     * Capture request from "php://input" and return as an assoc array
     *
     * @return array
     */
    public static function captureInput()
    {
        $fp = fopen("php://input", "r");
        $req = '';
        while ($data = fread($fp, 1024)) {
            $req.=$data;
        }
        fclose($fp);
        $params = array();
        if ($req) {
            $params = json_decode($req, true);
            if (!$params) {
                parse_str($req, $params);
            }
            if ($params) {
                self::setParam('JSON', $params);
                foreach ($params as $key => $value) {
                    self::setParam($key, $value);
                }
            }
        }

        return self::filter($params, 'JSON');
    }
}
