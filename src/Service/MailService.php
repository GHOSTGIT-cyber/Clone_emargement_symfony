<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Twig\Environment as TwigEnvironment;
use Psr\Log\LoggerInterface;


class MailService
{
    private MailerInterface $mailer;
    private TwigEnvironment $twig;
    private string $appUrl;
    private LoggerInterface $logger;
    

    public function __construct(MailerInterface $mailer, TwigEnvironment $twig, string $appUrl,  LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->appUrl = $appUrl;
        $this->logger = $logger;
        
}
    

    public function sendResetPasswordEmail(string $to, string $username, string $token): void
    {
        // construis ton lien avec ton APP_URL
        $resetLink = sprintf('%s/reset-password?token=%s', $this->appUrl, $token);

        $htmlContent = $this->twig->render('emails/reset_password.html.twig', [
            'username' => $username,
            'resetLink' => $resetLink
        ]);

        $email = (new Email())
            ->from(new Address('ton.email@gmail.com', 'Gefor Emargement'))
            ->to($to)
            ->subject('Réinitialisation de votre mot de passe')
            ->html($htmlContent)
            ->text(sprintf(
                "Bonjour %s,\n\nCliquez sur ce lien pour réinitialiser votre mot de passe : %s\n\nCe lien expire dans 1 heure.",
                $username,
                $resetLink
            ));

        try {
            $this->mailer->send($email);
        } catch (\Throwable $exception) {
            dump($exception->getMessage()); // Optionnel pour voir en DEV
            $this->logger->error('Erreur Mailer : ' . $exception->getMessage());
        }
}

public function sendAccountActivationEmail(string $to, string $username, string $token): void
{
    $activationLink = sprintf('%s/reset-password?token=%s', $this->appUrl, $token);

    $htmlContent = $this->twig->render('emails/account_activation.html.twig', [
        'username' => $username,
        'resetLink' => $activationLink
    ]);

    $email = (new Email())
        ->from(new Address('noreply@geforschool.com', 'Gefor Emargement'))
        ->to($to)
        ->subject('Activation de votre compte')
        ->html($htmlContent)
        ->text(sprintf(
            "Bonjour %s,\n\nBienvenue sur notre plateforme. Cliquez sur ce lien pour activer votre compte et définir votre mot de passe : %s\n\nCe lien expire dans 1 heure.",
            $username,
            $activationLink
        ));

    try {
        $this->mailer->send($email);
    } catch (\Throwable $exception) {
        $this->logger->error('Erreur lors de l’envoi du mail d’activation : ' . $exception->getMessage());
    }
}

public function sendPresenceNotificationEmail(string $email, string $prenom, string $formationNom, \DateTimeInterface $dateDebut): void
{
        
    $htmlContent = $this->twig->render('emails/presence_notification.html.twig', [
        'prenom' => $prenom,
        'formationNom' => $formationNom,
        'dateDebut' => $dateDebut,
        'app_url' => $this->appUrl . '/apprenant/dashboard' // lien vers l’espace apprenant
    ]);

    $emailMessage = (new Email())
        ->from(new Address('no-reply@gefor.fr', 'Gefor Emargement'))
        ->to($email)
        ->subject('Signature de présence requise')
        ->html($htmlContent)
        ->text(sprintf(
            'Bonjour %s,\n\nLe cours "%s", prévu le %s, débute. Merci de signer la feuille de présence sur votre espace : %s',
            $prenom,
            $formationNom,
            $dateDebut->format('d/m/Y à H:i'),
            $this->appUrl . '/apprenant/dashboard'
        ));

    try {
        $this->mailer->send($emailMessage);
    } catch (\Throwable $exception) {
        $this->logger->error('Erreur lors de l’envoi du mail de présence : ' . $exception->getMessage());
    }
}

}

