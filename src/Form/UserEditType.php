<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class UserEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'disabled' => true,
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom',
                'disabled' => true,
            ])

            ->add('username', TextType::class, [
                'label' => 'Nom d’utilisateur',
                'disabled' => true, // Le nom d'utilisateur ne peut pas être modifié
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'disabled' => true,
            ])

            

            ->add('profilePicture', FileType::class, [
                'label' => 'Photo de profil (JPG/PNG)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Veuillez uploader une image JPG ou PNG valide.',
                    ])
                ],
            ])

             ->add('removeProfilePicture', CheckboxType::class, [
        'mapped' => false,
        'required' => false,
        'label' => 'Supprimer la photo actuelle',
    ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}