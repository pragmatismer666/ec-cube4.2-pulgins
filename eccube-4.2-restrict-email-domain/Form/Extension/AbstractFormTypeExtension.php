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
use Plugin\RestrictEmailDomain\Repository\ConfigRepository;
use Symfony\Component\Form\FormError;

abstract class AbstractFormTypeExtension extends AbstractTypeExtension
{
    protected $configRepository;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $restrictEmailDomainConfig = $this->configRepository->get();
                if ($restrictEmailDomainConfig != null) {
                    $invalidDomains = explode("\n",$restrictEmailDomainConfig->getRestrictedEmailDomains());
                    if(count($invalidDomains)>0) {
                        $invalidDomains=array_map('trim',$invalidDomains);
                        $form = $event->getForm();
                        $fieldToCheck = 'email';
                        if ($form->has($fieldToCheck)) {
                            if (count($form[$fieldToCheck]->getErrors()) == 0) {
                                $inputEmail = $form[$fieldToCheck]->getData();
                                if (!empty($inputEmail)) {//Validate only if not empty
                                    str_replace($invalidDomains, "", $inputEmail, $matchCount);
                                    if ($matchCount > 0) {
                                        $form[$fieldToCheck]->addError(new FormError(trans('restrict_email_domain.field.restricted_domains.error')));
                                        if(isset($form[$fieldToCheck]['first']) || array_key_exists('first',$form[$fieldToCheck])) {
                                            $form[$fieldToCheck]['first']->addError(new FormError(trans('restrict_email_domain.field.restricted_domains.error')));
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            });
    }
}
