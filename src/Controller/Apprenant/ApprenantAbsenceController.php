<?php

namespace App\Controller\Apprenant;

use App\Entity\SignatureSession;
use App\Form\JustificationAbsenceType;
use App\Repository\SignatureSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/apprenant')]
class ApprenantAbsenceController extends AbstractController
{
    #[Route('/justifier-absence', name: 'apprenant_justifier_absence')]
    public function justifierAbsence(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        SignatureSessionRepository $signatureSessionRepo
    ): Response {
        $user = $this->getUser();

        $sessionsPassees = $signatureSessionRepo->findSessionsPasseesNonJustifiees($user);
        $sessionsFutures = $signatureSessionRepo->findSessionsFutures($user);

        $form = $this->createForm(JustificationAbsenceType::class, null, [
            'sessions_passees' => $sessionsPassees,
            'sessions_futures' => $sessionsFutures,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedPassees = $form->get('sessionsPassees')->getData();
            $selectedFutures = $form->get('sessionsFutures')->getData();

            $allSessions = array_merge($selectedPassees->toArray(), $selectedFutures->toArray());
            $motif = $form->get('motifDetails')->getData();
            $document = $form->get('document')->getData();

            $filename = null;
            if ($document) {
                $originalFilename = pathinfo($document->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $filename = $safeFilename . '-' . uniqid() . '.' . $document->guessExtension();

                try {
                    $document->move($this->getParameter('justifications_directory'), $filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du fichier justificatif.');
                    return $this->redirectToRoute('apprenant_justifier_absence');
                }
            }

            if (count($allSessions) === 0) {
                $this->addFlash('error', 'Aucune session sélectionnée.');
            } else {
               foreach ($allSessions as $signature) {
    $signature->setJustifie(true);
    $signature->setMotifAbsence('justifié');
    $signature->setMotifDetails($motif);

    if ($filename) {
        $signature->setDocument($filename);
    }

                    if ($filename) {
                        $signature->setDocument($filename);
                    }

                    $em->persist($signature);
                }

                $em->flush();

                $this->addFlash('success', 'Vos justifications ont bien été enregistrées.');
                return $this->redirectToRoute('apprenant_justifier_absence');
            }
        }

        return $this->render('apprenant/apprenant_justifier_absence.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}