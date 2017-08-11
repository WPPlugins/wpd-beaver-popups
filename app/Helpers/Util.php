<?php

namespace WPD\BeaverPopups\Helpers;

class Util
{
    /**
     * Returns object's property or array's element by key
     * in case of absence returns default value
     *
     * @param array|object $data to extract element from
     * @param string $key
     * @param mixed $defaultValue
     * @return mixed
     */
    public static function getItem($data, $key, $defaultValue = "")
    {
        $value = $defaultValue;
        if (is_object($data) && isset($data->$key)) {
            $value = $data->$key;
        }
        if (is_array($data) && isset($data[$key])) {
            $value = $data[$key];
        }

        return $value;
    }

    /**
     * Organize array of items by some item field
     *
     * @param $array
     * @param $keyOrGetter
     *
     * @return array
     */
    public static function organizeArrayByKey($array, $keyOrGetter)
    {
        $res = [];
        $first = reset($array);
        if ($first) {
            if (is_object($first) && method_exists($first, $keyOrGetter)) {
                foreach ($array as $item) {
                    $key = call_user_func([$item, $keyOrGetter]);
                    $res[$key] = $item;
                }
            } else {
                foreach ($array as $item) {
                    $key = self::getItem($item, $keyOrGetter);
                    $res[$key] = $item;
                }
            }
        }
        return $res;
    }

    /**
     * Hex to RGB(A)
     *
     * @param $color string Hex value
     * @param $opacity int Opacity (0 - 1)
     *
     * @return string
     */
    public static function hex2rgba( $color, $opacity = 1, $include_prefix = true )
    {
        if ( '#' === $color[ 0 ] ) {
            $color = substr( $color, 1 );
        }

        if ( 6 === strlen( $color ) ) {
            list( $r, $g, $b ) = [
                $color[ 0 ] . $color[ 1 ],
                $color[ 2 ] . $color[ 3 ],
                $color[ 4 ] . $color[ 5 ]
            ];
        }
        elseif ( 3 === strlen( $color ) ) {
            list( $r, $g, $b ) = [
                $color[ 0 ] . $color[ 0 ],
                $color[ 1 ] . $color[ 1 ],
                $color[ 2 ] . $color[ 2 ]
            ];
        }
        else {
            return false;
        }

        $r = hexdec( $r );
        $g = hexdec( $g );
        $b = hexdec( $b );

        $rgba = $r . ',' . $g . ',' . $b . ',' . $opacity;

        if ( $include_prefix ) {
            $rgba = 'rgba(' . $rgba . ')';
        }

        return $rgba;
    }

    /**
     * Create a box shadow in PHP for dynamic CSS
     *
     * @param $horizontalLength Horizontal length for box shadow
     * @param $verticalLength Vertical length for box shadow
     * @param $spreadRadius Spread of box shadow
     * @param $blurRadius Blur of box shadow
     * @param $color Box shadow color
     * @param $colorOpacity Box shadow color opacity
     *
     * @return string
     */
    public static function createBoxShadow( $horizontalLength = 0, $verticalLength = 0, $spreadRadius = 0, $blurRadius = 0, $color = '000', $colorOpacity = 0.5 )
    {
        $boxShadowProps = [];
        $boxShadowValue = null;
        $i              = 0;

        $boxShadowProps[ 'horizontalLength' ] = $horizontalLength ? $horizontalLength . 'px' : $horizontalLength;
        $boxShadowProps[ 'verticalLength' ]   = $verticalLength ? $verticalLength . 'px' : $verticalLength;
        $boxShadowProps[ 'spreadRadius' ]     = $spreadRadius ? $spreadRadius . 'px' : $spreadRadius;
        $boxShadowProps[ 'blurRadius' ]       = $blurRadius ? $blurRadius . 'px' : $blurRadius;
        $boxShadowProps[ 'color' ]            = Util::hex2rgba( $color, $colorOpacity, true );

        foreach ( $boxShadowProps as $prop => $value ) :
            if ( $i ) {
                $boxShadowValue .= ' ';
            }

            $boxShadowValue .= $value;

            $i++;
        endforeach;

        return $boxShadowValue;
    }
}
