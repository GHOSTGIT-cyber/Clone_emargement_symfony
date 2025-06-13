<?php

namespace App\Controller;

use App\Entity\Groupe;
use App\Form\GroupeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminGroupeController extends AbstractController
{
    #[Route('/admin/create-groupe', name: 'admin_create_groupe', methods: ['GET', 'POST'])]
    public function createGroupe(Request $request, EntityManagerInterface $em): Response
    {
        $groupe = new Groupe();

        $form = $this->createForm(GroupeType::class, $groupe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Traitement des groupes parents : on ajoute leurs apprenants
            $groupesParents = $form->get('groupesParents')->getData();

            foreach ($groupesParents as $parent) {
                foreach ($parent->getApprenants() as $apprenant) {
                    $groupe->addApprenant($apprenant);
                }
            }

            $em->persist($groupe);
            $em->flush();

            $this->addFlash('success', 'Groupe créé avec succès.');
            return $this->redirectToRoute('admin_contacts', ['tab' => 'groupes']);
        }

        return $this->render('admin/create_groupe.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}