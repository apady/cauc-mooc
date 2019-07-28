<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\Type\UpLoadFileType;
use App\Entity\Category;


class TeacherAddCourseType extends AbstractType{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('name',TextType::class,array('label' => '课程名称'))
            ->add('teacher',TextType::class,array('label' => '授课老师','mapped'=>false))
            ->add('info',TextType::class,array('label' => '课程简介'))
            ->add('courseHour',TextType::class,array('label' => '课程时间'))
            ->add('category', EntityType::class, array(
                'class' => 'App:Category',
                'placeholder' => 'Choose an option',
                'choice_label' => 'name',
                'multiple'=>false,
                'label'=>'课程分类'))
            ->add('coverImg',UpLoadFileType::class,array('label' => '课程封面'))
            ->add('立即添加',SubmitType::class)
            ->getForm();

    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'=>'App\Entity\Courses'
        ));
    }

}