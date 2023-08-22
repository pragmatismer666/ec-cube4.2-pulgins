<?php

namespace Plugin\PayJp\Service\PayJpLib;

use Payjp\ApiResource;
use Payjp\Util;
class TdCharge extends ApiResource {
    public static function tdsFinish($id) {
        list($response, $opts) = self::_staticRequest('post', '/v1/charges/' . $id . '/tds_finish', [], null);
        return Util\Util::convertToPayjpObject($response, $opts);
    }
}