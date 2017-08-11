<?php

namespace WPD\BeaverPopups\Helpers;

/**
 * Interface JsonReady allows to customize json output when object is included into a payload
 * when JsonHelper is used to perform API res
 *
 * @package WPD\BeaverPopups\Helpers
 */
interface JsonReady
{

    /**
     * Returns assoc array to be packed into json payload
     *
     * @return array($key=>$value);
     */
    public function packJsonItem();

    /**
     * Assigns inner values from the ones provided in $data
     *
     * @param $data
     *
     * @return self
     */
    public static function unpackJsonItem($data);
}
