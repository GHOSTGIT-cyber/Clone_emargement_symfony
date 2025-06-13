<?php

namespace App\Form;

use App\Entity\Formation;
use App\Entity\Salle;
use App\Entity\Session;
use App\Entity\User;
use App\Entity\Groupe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SessionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la session :',
                'attr' => ['id' => 'nom_session']
            ])
            ->add('formation', EntityType::class, [
                'class' => Formation::class,
                'choice_label' => 'nom',
                'label' => 'Formation :',
                'placeholder' => 'Sélectionner une formation',
                'attr' => [ 'id' => 'formation_id']
            ])
            ->add('formateur', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn(User $u) => $u->getFirstname() . ' ' . $u->getLastname(),
                'label' => 'Formateur :',
                'placeholder' => 'Sélectionner un formateur',
                'query_builder' => fn($er) => $er->createQueryBuilder('u')
                    ->where('u.role = :role')
                    ->setParameter('role', 'formateur'),
                'attr' => ['class' => 'choices-select', 'id' => 'formateur_id']
            ])
            ->add('salleNom', TextType::class, [
                'label' => 'Salle :',
                'mapped' => false,
                'attr' => [
                    'list' => 'salles',
                    'placeholder' => 'Sélectionner ou entrer une salle',
                    'required' => true,
                    'id' => 'salle_nom',
                ]
            ])
            ->add('dateDebut', DateTimeType::class, [
                'label' => 'Date de début :',
                'widget' => 'single_text',
                'attr' => ['id' => 'date_debut']
            ])
            ->add('dateFin', DateTimeType::class, [
                'label' => 'Date de fin :',
                'widget' => 'single_text',
                'attr' => ['id' => 'date_fin']
            ]);
            
            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Session::class,
        ]);
    }
}