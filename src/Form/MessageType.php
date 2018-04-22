<?php

/**
 * Message type.
 */

namespace Form;

use Symfony\Component\Form\AbstractType;
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
class MessageType extends AbstractType
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
                'required' => true,
                'attr' => [
                    'disabled' => true,
                ],
            ]
        );
        $builder->add(
            'content',
            TextareaType::class,
            [
                'label' => 'label.message',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['message-default']]
                    ),
                ],
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
                'validation_groups' => 'message-default',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'message_type';
    }
}
