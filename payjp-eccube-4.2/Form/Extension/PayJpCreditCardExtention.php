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

namespace Plugin\PayJp\Form\Extension;

use Eccube\Entity\Order;
use Eccube\Form\Type\Shopping\OrderType;
use Eccube\Repository\PaymentRepository;
use Plugin\PayJp\Service\Method\PayJpCreditCard;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;

class PayJpCreditCardExtention extends AbstractTypeExtension
{
    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    public function __construct(PaymentRepository $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            /** @var Order $data */
            $data = $event->getData();
            $form = $event->getForm();

            // 支払い方法が一致する場合
            if ($data->getPayment()->getMethodClass() === PayJpCreditCard::class) {
                $form->add('pay_jp_token', HiddenType::class, [
                    'required' => false,
                    'mapped' => true
                ]);

                $form->add('is_save_card_on', CheckboxType::class, [
                    'required' => false,
                    'mapped' => true
                ]);
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $options = $event->getForm()->getConfig()->getOptions();

            // 注文確認->注文処理時はフォームは定義されない.
            if ($options['skip_add_form']) {

                // サンプル決済では使用しないが、支払い方法に応じて処理を行う場合は
                // $event->getData()ではなく、$event->getForm()->getData()でOrderエンティティを取得できる

                /** @var Order $Order */
                $Order = $event->getForm()->getData();
                $Order->getPayment()->getId();

                return;
            } else {

                $Payment = $this->paymentRepository->findOneBy(['method_class' => PayJpCreditCard::class]);

                $data = $event->getData();
                $form = $event->getForm();

                // 支払い方法が一致しなければremove
                if ($Payment->getId() != $data['Payment']) {
                    $form->remove('pay_jp_token');
                    $form->remove('is_save_card_on');
                }

            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return OrderType::class;
    }

    /**
     * Return the class of the type being extended.
     */
    public static function getExtendedTypes(): iterable
    {
        return [OrderType::class];
    }
}