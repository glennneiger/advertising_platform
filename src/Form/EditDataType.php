<?php
/**
 * EditDataType
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints as CustomAssert;

/**
 * Class EditDataType
 * @package Form
 */
class EditDataType extends AbstractType
{
    /**
     *{@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'email',
            EmailType::class,
            [
                'label' => 'label.email',
                'required' => true,
                'constraints' => [
                    new CustomAssert\UniqueEmail(
                        [
                            'groups' => ['register-default'],
                            'repository' => isset($options['user_repository']) ? $options['user_repository'] : null,
                            'userId' => isset($options['userId']) ? $options['userId'] : null,
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'name',
            TextType::class,
            [
                'label' => 'label.name',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['register-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['register-default'],
                            'min' => 3,
                            'max' => 45,
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'surname',
            TextType::class,
            [
                'label' => 'label.surname',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['register-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['register-default'],
                            'min' => 3,
                            'max' => 45,
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'edit_type';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => 'register-default',
                'user_repository' => null,
                'userId' => null,
            ]
        );
    }
}
