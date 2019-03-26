<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', null, ['label' => 'Name'])
            ->add('icon', null, ['label' => 'Icon'])
            ->add('luxury', null, ['label' => 'Luxus'])
            ->add('income', null, ['label' => 'Einnahmenkategorie'])
            ->add('shared', null, ['label' => 'Haushaltskasse'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
