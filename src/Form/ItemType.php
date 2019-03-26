<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Item;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', null, ['label' => 'Beschreibung'])
            ->add('amount', MoneyType::class, ['label' => 'Betrag'])
            ->add('dateAt', DateType::class, [
                'label' => 'Datum',
                'widget' => 'single_text',
                'format' => 'dd.MM.yyyy',
            ])
            ->add(
                'category',
                EntityType::class,
                [
                    'label' => 'Kategorie',
                    'required' => true,
                    'class' => Category::class,
                    'choice_label' => 'title',
                    'query_builder' => function (EntityRepository $er) use ($options) {
                        $where = !$options['shared'] ? 'c.user = :user AND c.shared = 0' : 'c.shared = 1 AND :user = :user';

                        return $er
                            ->createQueryBuilder('c')
                            ->where('c.income = :income')
                            ->andWhere($where)
                            ->andWhere('c.shared = :shared')
                            ->setParameter('income', $options['direction'] === 'income')
                            ->setParameter('user', $options['user'])
                            ->setParameter('shared', $options['shared'])
                            ->orderBy('c.title', 'ASC');
                    },
                ]
            )
            ->add(
                'description',
                null,
                [
                    'label' => 'Notiz',
                    'attr' => [
                        'rows' => '5',
                    ],
                    'required' => false,
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Item::class,
            'direction' => '',
            'shared' => false,
            'user' => 0,
        ]);
    }
}
