<?php

namespace App\Form;

use App\Entity\Formation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class FormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la formation',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer le nom de la formation',
                    ]),
                ],
                'attr' => ['placeholder' => 'Ex : Développement Web']
            ])
            ->add( 'description', TextareaType::class, [
                'label' => 'Description (facultatif)',
                'required' => false,
                'attr' => ['placeholder' => 'Détails de la formation...',
                    'rows' => 4,
                    'class' => 'large-textarea' 
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Formation::class,
        ]);
    }
}
