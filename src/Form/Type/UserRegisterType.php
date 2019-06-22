<?php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class UserRegisterType extends AbstractType{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username',null,array('label'=>'用户名'))
            ->add('email', EmailType::class,array('label'=>'邮箱'))
            ->add('plainPassword',RepeatedType::class,array(
                'type' => PasswordType::class,
                'invalid_message' => '密码不一致。',
                'first_options'  => array('label' => '密码'),
                'second_options' => array('label' => '确认密码'),
                ))
            ->add('identifying_code', null,array('label'=>'验证码',
                'mapped' => false))
            ->add('termsAccepted', CheckboxType::class, array(
                'label'=>'同意服务条款',
                'mapped' => false,
                'constraints' => new IsTrue(),
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'=>'App\Entity\User'
        ));
    }

}