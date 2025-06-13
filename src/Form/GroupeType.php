<?php

namespace App\Form;

use App\Entity\Groupe;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du groupe',
                'attr' => [
                    'placeholder' => 'Entrez le nom du groupe',
                ],
            ])
            ->add('apprenants', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn(User $u) => $u->getFirstname() . ' ' . $u->getLastname(),
                'multiple' => true,
                'expanded' => false,
                'label' => 'Sélectionner les apprenants',
                'query_builder' => fn($er) => $er->createQueryBuilder('u')
                    ->where('u.role = :role')
                    ->setParameter('role', 'apprenant'),
                'attr' => [
                    'class' => 'choices-select',
                ],
            ])

            ->add('groupesParents', EntityType::class, [
                'class' => Groupe::class,
                'choice_label' => 'nom',
                'label' => 'Ajouter à partir d\'autres groupes (optionnel)',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'choices-select',
                    'id' => 'groupes_parents',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Groupe::class,
        ]);
    }
}
