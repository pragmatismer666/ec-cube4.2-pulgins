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

namespace Plugin\PayJp\Form\Type\Admin;

use Plugin\PayJp\Entity\PayJpConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PayJpConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('public_api_key', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(array(
                            'message' => trans('pay_jp.admin.config.public_api_key.error.blank')
                        )
                    ),
                    new Assert\Regex(array(
                            'pattern' => '/^\w+$/',
                            'match' => true,
                            'message' => trans('pay_jp.admin.config.public_api_key.error.regex')
                        )
                    )
                ],
            ])
            ->add('api_key_secret', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(array(
                        'message' => trans('pay_jp.admin.config.api_key_secret.error.blank')
                    )),
                    new Assert\Regex(array(
                            'pattern' => '/^\w+$/',
                            'match' => true,
                            'message' => trans('pay_jp.admin.config.api_key_secret.error.regex')
                        )
                    )
                ],
            ])
            ->add('is_auth_and_capture_on', ChoiceType::class,[
                'choices' => [
                    'pay_jp.admin.config.authorize' => false, 
                    'pay_jp.admin.config.authorize_and_capture' => true
                ]
            ])
            ->add('payjp_fees_percent', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Regex(array(
                            'pattern' => '/^100$|^\d{0,2}(\.\d{1,2})? *%?$/',
                            'match' => true,
                            'message' => trans('pay_jp.admin.config.payjp_fees_percent.error.regex')
                        )
                    )
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PayJpConfig::class,
        ]);
    }
}