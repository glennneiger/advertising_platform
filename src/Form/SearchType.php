<?php

/**
 * Search type.
 */

namespace Form;

use Repository\AdvertRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class TagType.
 *
 * @package Form
 */
class SearchType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'topic',
            TextType::class,
            [
                'label' => 'label.topic',
                'required' => false,
                'attr' => [
                    'max_length' => 45,
                ],
            ]
        );
        $builder->add(
            'city',
            TextType::class,
            [
                'label' => 'label.city',
                'required' => false,
                'attr' => [
                    'max_length' => 45,
                ],
            ]
        );
        $builder->add(
            'price_from',
            TextType::class,
            [
                'label' => 'label.price_from',
                'required' => false,
                'constraints' => [
                    new Assert\Type(
                        [
                            'type' => 'numeric',
                            'groups' => ['advert-default'],
                        ]
                    )
                ],
            ]
        );
        $builder->add(
            'price_to',
            TextType::class,
            [
                'label' => 'label.price_to',
                'required' => false,
                'constraints' => [
                    new Assert\Type(
                        [
                            'type' => 'numeric',
                            'groups' => ['advert-default'],
                        ]
                    )
                ],
            ]
        );
        $builder->add(
            'type',
            ChoiceType::class,
            [
                'label' => 'label.type',
                'required' => false,
                'choices' => [
                    'advert.type.purchase.label' => AdvertRepository::PURCHASE_TYPE,
                    'advert.type.sale.label' => AdvertRepository::SALE_TYPE,
                    'advert.type.return.label' => AdvertRepository::RETURN_TYPE,
                    'advert.type.swap.label' => AdvertRepository::SWAP_TYPE,
                ],
            ]
        );
        $builder->add(
            'category_id',
            ChoiceType::class,
            [
                'label' => 'label.category',
                'required' => false,
                'choices' => isset($options['category_repository'])
                    ? $options['category_repository']->getChoices()
                    : [],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => 'advert-default',
                'category_repository' => null,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'search_type';
    }
}
