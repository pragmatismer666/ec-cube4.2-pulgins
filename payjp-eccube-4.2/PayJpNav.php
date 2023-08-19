<?php
/*
* Plugin Name : PayJp
*
* Copyright (C) 2018 Subspire Inc. All Rights Reserved.
* http://www.subspire.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\PayJp;

use Eccube\Common\EccubeNav;

class PayJpNav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'pay_jp' => [
                'name' => 'pay_jp.admin.nav.pay_jp',
                'icon' => 'fa-credit-card',
                'children' => [
                    'pay_jp_log' => [
                        'name' => 'pay_jp.admin.nav.pay_jp_log',
                        'url' => 'pay_jp_admin_log',
                    ],
                    'pay_jp_config' => [
                        'name' => 'pay_jp.admin.nav.pay_jp_config',
                        'url' => 'pay_jp_admin_config',
                    ],
                    'pay_jp_3d_config' => [
                        'name'  =>  'pay_jp.admin.nav.pay_jp_3d_config',
                        'url'   =>  'pay_jp_admin_3d_config'
                    ]
                ],
            ],
        ];
    }
}