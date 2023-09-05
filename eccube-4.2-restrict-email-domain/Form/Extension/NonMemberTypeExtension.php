<?php
/*
* Plugin Name : RestrictEmailDomain
*
* Copyright (C) 2018 Subspire Inc. All Rights Reserved.
* http://www.subspire.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\RestrictEmailDomain\Form\Extension;

use Doctrine\ORM\EntityRepository;
use Eccube\Entity\Order;
use Eccube\Form\Type\Shopping\OrderType;
use Eccube\Repository\PaymentRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Eccube\Form\Type\Front\NonMemberType;
use Plugin\RestrictEmailDomain\Form\Extension\AbstractFormTypeExtension;

class NonMemberTypeExtension extends AbstractFormTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return NonMemberType::class;
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [NonMemberType::class];
    }

}
