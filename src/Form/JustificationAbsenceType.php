<?php

namespace App\Form;

use App\Entity\SignatureSession;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class JustificationAbsenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sessionsPassees', EntityType::class, [
                'class' => SignatureSession::class,
                'choices' => $options['sessions_passees'],
                'choice_label' => function (SignatureSession $signature) {
                    $session = $signature->getSession();
                    if (!$session) return 'Session inconnue';
                    $date = $session->getDateDebut()?->format('d/m/Y') ?? 'Date inconnue';
                    $formation = $session->getFormation()?->getNom() ?? 'Formation inconnue';
                    return $date . ' – ' . $formation;
                },
                'multiple' => true,
                'expanded' => false,
                'mapped' => false, // Ce champ est traité manuellement dans le contrôleur
                'required' => false,
                'label' => 'Absences passées non justifiées',
                'attr' => [
                    'id' => 'sessions-passees',
                    'class' => 'form-control choices-js',
                ],
            ])
            ->add('sessionsFutures', EntityType::class, [
                'class' => SignatureSession::class,
                'choices' => $options['sessions_futures'],
                'choice_label' => function (SignatureSession $signature) {
                    $session = $signature->getSession();
                    if (!$session) return 'Session inconnue';
                    $date = $session->getDateDebut()?->format('d/m/Y') ?? 'Date inconnue';
                    $formation = $session->getFormation()?->getNom() ?? 'Formation inconnue';
                    return $date . ' – ' . $formation;
                },
                'multiple' => true,
                'expanded' => false,
                'mapped' => false, // Ce champ est traité manuellement dans le contrôleur
                'required' => false,
                'label' => 'Absences futures à prévenir',
                'attr' => [
                    'id' => 'sessions-futures',
                    'class' => 'form-control choices-js',
                ],
            ])
            ->add('motifDetails', TextareaType::class, [
                'label' => 'Motif détaillé',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'class' => 'form-control',
                ],
            ])
            ->add('document', FileType::class, [
                'label' => 'Document justificatif (PDF, image, etc.)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control-file',
                ],
            ])
            ->add('rgpdConsent', CheckboxType::class, [
                'label' => 'J’accepte le traitement de mes données personnelles',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'sessions_passees' => [],
            'sessions_futures' => [],
        ]);
    }
}