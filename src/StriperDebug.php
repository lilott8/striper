<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 4/4/17
 * Time: 9:40 PM
 */

namespace Drupal\striper;


class StriperDebug {

    public static function vd() {
        ob_start();
        var_dump(func_get_args());
        return ob_get_clean();
    }

}