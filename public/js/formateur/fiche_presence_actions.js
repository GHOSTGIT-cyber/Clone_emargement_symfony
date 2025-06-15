/**
 * =========================================================================
 * JAVASCRIPT FICHE DE PRÉSENCE FORMATEUR
 * =========================================================================
 * 
 * Ce fichier gère toutes les interactions de la fiche de présence :
 * - Signature du formateur (popup signature)
 * - Menu déroulant des actions apprenants
 * - Signature manuelle des apprenants
 * - Gestion des retards, commentaires, absences
 * 
 * IMPORTANT : Code archivé et commenté pour présentation orale
 * Chaque fonction est expliquée pour faciliter la compréhension
 * 
 * Fichier : public/js/formateur/fiche_presence_actions.js
 * =========================================================================
 */

// =========================================================================
// VARIABLES GLOBALES
// =========================================================================

/**
 * Variables pour la gestion de la signature
 * - signaturePad : objet SignaturePad pour dessiner les signatures
 * - signatureContext : contexte actuel ('formateur' ou 'apprenant')
 * - currentSignatureId : ID de la signature en cours de traitement
 */
let signaturePad = null;
let signatureContext = 'formateur';
let currentSignatureId = null;

/**
 * Éléments DOM fréquemment utilisés
 * Stockés en variables pour éviter les recherches répétées
 */
let popupSignature = null;
let canvasSignature = null;
let btnValiderSignature = null;
let btnEffacerSignature = null;
let btnRetourSignature = null;

// =========================================================================
// INITIALISATION AU CHARGEMENT DE LA PAGE
// =========================================================================

/**
 * Point d'entrée principal
 * Se déclenche quand le DOM est complètement chargé
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Fiche Présence] Initialisation de la page');
    console.log('[Debug] Vérification des éléments DOM...');
    
    // Debug : vérification des éléments essentiels
    const popup = document.getElementById('signature-popup');
    const canvas = document.getElementById('signature-canvas');
    const btnOuvrir = document.getElementById('btn-ouvrir-signature');
    
    console.log('[Debug] Popup trouvé:', !!popup);
    console.log('[Debug] Canvas trouvé:', !!canvas);
    console.log('[Debug] Bouton ouvrir trouvé:', !!btnOuvrir);
    console.log('[Debug] SignaturePad disponible:', typeof SignaturePad !== 'undefined');
    
    if (!popup) {
        console.error('[ERREUR] Popup signature introuvable !');
        return;
    }
    
    if (!canvas) {
        console.error('[ERREUR] Canvas signature introuvable !');
        return;
    }
    
    if (typeof SignaturePad === 'undefined') {
        console.error('[ERREUR] SignaturePad non chargé !');
        alert('Erreur : Bibliothèque de signature non disponible');
        return;
    }
    
    // Initialise toutes les fonctionnalités dans l'ordre
    initialiserSignaturePad();
    initialiserMenusActions();
    initialiserCheckboxes();
    initialiserActionsApprenants();
    
    console.log('[Fiche Présence] Initialisation terminée');
});

// =========================================================================
// 1. GESTION DE LA SIGNATURE (FORMATEUR ET APPRENANTS)
// =========================================================================

/**
 * Initialise le système de signature avec SignaturePad
 * Gère la popup de signature pour le formateur et les apprenants
 */
function initialiserSignaturePad() {
    console.log('[Signature] Initialisation du système de signature');
    
    // Vérification que la librairie SignaturePad est chargée
    if (typeof SignaturePad === 'undefined') {
        console.error('[Signature] ERREUR : SignaturePad non chargé !');
        alert('Erreur : Système de signature non disponible');
        return;
    }
    
    // Récupération des éléments de la popup de signature
    popupSignature = document.getElementById('signature-popup');
    canvasSignature = document.getElementById('signature-canvas');
    btnValiderSignature = document.getElementById('btn-valider');
    btnEffacerSignature = document.getElementById('btn-effacer');
    btnRetourSignature = document.getElementById('btn-retour');
    
    // Vérification que tous les éléments sont présents
    if (!canvasSignature) {
        console.error('[Signature] ERREUR : Canvas de signature introuvable');
        return;
    }
    
    // Création de l'objet SignaturePad avec les paramètres optimaux
    signaturePad = new SignaturePad(canvasSignature, {
        backgroundColor: 'rgba(255,255,255,1)', // Fond blanc pour l'export
        penColor: 'rgb(0,0,0)',                 // Stylo noir
        minWidth: 2,                            // Largeur minimum du trait
        maxWidth: 4,                            // Largeur maximum du trait
        throttle: 16,                           // Fluidité du dessin (60fps)
        minDistance: 5                          // Réduction des points pour performance
    });
    
    console.log('[Signature] SignaturePad créé avec succès');
    
    // Configuration du redimensionnement automatique du canvas
    configurerRedimensionnementCanvas();
    
    // Initialisation des événements de la popup
    initialiserEvenementsSignature();
}

/**
 * Configure le redimensionnement automatique du canvas
 * Important pour les écrans Retina et les changements d'orientation
 */
function configurerRedimensionnementCanvas() {
    /**
     * Fonction qui ajuste la taille du canvas selon l'écran
     * Prend en compte le devicePixelRatio pour les écrans haute résolution
     */
    function redimensionnerCanvas() {
        console.log('[Signature] Redimensionnement du canvas');
        
        // Calcul du ratio de pixels pour les écrans Retina
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        
        // Ajustement de la taille du canvas
        canvasSignature.width = canvasSignature.offsetWidth * ratio;
        canvasSignature.height = canvasSignature.offsetHeight * ratio;
        
        // Mise à l'échelle du contexte de dessin
        canvasSignature.getContext('2d').scale(ratio, ratio);
        
        // Effacement après redimensionnement pour éviter les décalages
        signaturePad.clear();
    }
    
    // Redimensionnement initial
    redimensionnerCanvas();
    
    // Redimensionnement automatique lors du changement de taille de fenêtre
    window.addEventListener('resize', redimensionnerCanvas);
}

/**
 * Initialise tous les événements liés à la popup de signature
 */
function initialiserEvenementsSignature() {
    console.log('[Signature] Initialisation des événements de signature');
    
    // Bouton pour ouvrir la popup de signature formateur
    const btnOuvrirSignature = document.getElementById('btn-ouvrir-signature');
    if (btnOuvrirSignature) {
        btnOuvrirSignature.addEventListener('click', function() {
            console.log('[Signature] Ouverture popup signature formateur');
            ouvrirPopupSignature('formateur', null, 'Signature du formateur');
        });
    }
    
    // Bouton "Retour" - Ferme la popup sans sauvegarder
    if (btnRetourSignature) {
        btnRetourSignature.addEventListener('click', function() {
            console.log('[Signature] Annulation de la signature');
            fermerPopupSignature();
        });
    }
    
    // Bouton "Effacer" - Vide le canvas
    if (btnEffacerSignature) {
        btnEffacerSignature.addEventListener('click', function() {
            console.log('[Signature] Effacement du canvas');
            signaturePad.clear();
        });
    }
    
    // Bouton "Valider" - Sauvegarde la signature
    if (btnValiderSignature) {
        btnValiderSignature.addEventListener('click', validerSignature);
    }
}

/**
 * Ouvre la popup de signature avec le contexte approprié
 * @param {string} contexte - 'formateur' ou 'apprenant'
 * @param {string|null} signatureId - ID de la signature (pour apprenants)
 * @param {string} titre - Titre à afficher dans la popup
 */
function ouvrirPopupSignature(contexte, signatureId, titre) {
    console.log(`[Signature] Ouverture popup pour ${contexte}`, { signatureId, titre });
    
    // Définition du contexte global
    signatureContext = contexte;
    currentSignatureId = signatureId;
    
    // Mise à jour du titre de la popup
    const titreElement = popupSignature.querySelector('h2');
    if (titreElement) {
        titreElement.textContent = titre;
    }
    
    // Effacement du canvas et affichage de la popup
    signaturePad.clear();
    popupSignature.classList.add('active');
    
    // Focus sur le canvas pour l'accessibilité
    canvasSignature.focus();
}

/**
 * Ferme la popup de signature et remet les valeurs par défaut
 */
function fermerPopupSignature() {
    console.log('[Signature] Fermeture de la popup');
    
    // Masquage de la popup
    popupSignature.classList.remove('active');
    
    // Remise à zéro des variables globales
    signatureContext = 'formateur';
    currentSignatureId = null;
    
    // Remise du titre par défaut
    const titreElement = popupSignature.querySelector('h2');
    if (titreElement) {
        titreElement.textContent = 'Signature du formateur';
    }
}

/**
 * Valide et sauvegarde la signature
 * Gère à la fois les signatures formateur et apprenant
 */
function validerSignature() {
    console.log('[Signature] Validation de la signature');
    
    // Vérification qu'une signature a été dessinée
    if (signaturePad.isEmpty()) {
        console.warn('[Signature] Tentative de validation sans signature');
        alert('Veuillez signer avant de valider.');
        return;
    }
    
    // Conversion de la signature en image base64
    const signatureData = signaturePad.toDataURL('image/png');
    console.log('[Signature] Signature capturée :', signatureData.slice(0, 50) + '...');
    
    // Désactivation du bouton pendant la sauvegarde
    btnValiderSignature.disabled = true;
    btnValiderSignature.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sauvegarde...';
    
    // Choix de l'URL selon le contexte
    let urlSauvegarde;
    if (signatureContext === 'apprenant' && currentSignatureId) {
        // URL pour signature manuelle d'apprenant
        urlSauvegarde = window.routes.signatureManuelle.replace('SIGNATURE_ID', currentSignatureId);
        console.log('[Signature] Sauvegarde signature apprenant');
    } else {
        // URL pour signature formateur
        urlSauvegarde = window.routes.signatureFormateur;
        console.log('[Signature] Sauvegarde signature formateur');
    }
    
    // Envoi de la signature au serveur
    fetch(urlSauvegarde, {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'signature_data=' + encodeURIComponent(signatureData)
    })
    .then(response => {
        console.log('[Signature] Réponse serveur reçue');
        return response.json();
    })
    .then(data => {
        console.log('[Signature] Données serveur :', data);
        
        if (data.success) {
            console.log('[Signature] Signature sauvegardée avec succès');
            afficherToast('Signature enregistrée avec succès', 'success');
            fermerPopupSignature();
            
            // Rechargement de la page après un délai
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            console.error('[Signature] Erreur serveur :', data.message);
            alert('Erreur : ' + (data.message || 'Erreur inconnue'));
            restaurerBoutonValidation();
        }
    })
    .catch(error => {
        console.error('[Signature] Erreur réseau :', error);
        alert('Erreur de connexion. Veuillez réessayer.');
        restaurerBoutonValidation();
    });
}

/**
 * Restaure l'état normal du bouton de validation après une erreur
 */
function restaurerBoutonValidation() {
    btnValiderSignature.disabled = false;
    btnValiderSignature.innerHTML = '<i class="fas fa-check"></i> Valider la signature';
}

// =========================================================================
// 2. GESTION DES MENUS DÉROULANTS D'ACTIONS
// =========================================================================

/**
 * Initialise la gestion des menus déroulants pour chaque apprenant
 * Chaque ligne du tableau a un menu avec différentes actions possibles
 */
function initialiserMenusActions() {
    console.log('[Menus] Initialisation des menus d\'actions');
    
    // Création de l'overlay pour fermer les menus en cliquant ailleurs
    creerOverlayMenus();
    
    // Récupération de tous les boutons d'actions - SÉLECTEUR CORRIGÉ
    const boutonsActions = document.querySelectorAll('.action-btn');
    console.log(`[Menus] ${boutonsActions.length} menus d'actions trouvés`);
    
    // Initialisation de chaque bouton
    boutonsActions.forEach((bouton, index) => {
        bouton.addEventListener('click', function(event) {
            // Empêche la propagation pour éviter la fermeture immédiate
            event.stopPropagation();
            
            const nomEtudiant = bouton.getAttribute('data-student-name');
            console.log(`[Menus] Clic sur menu pour ${nomEtudiant}`);
            
            // Récupération du menu associé - SÉLECTEUR CORRIGÉ
            const menu = bouton.parentElement.querySelector('.dropdown-menu');
            if (!menu) {
                console.error('[Menus] Menu déroulant introuvable');
                return;
            }
            
            // Gestion de l'ouverture/fermeture
            const estOuvert = menu.classList.contains('active');
            
            // Fermeture de tous les autres menus
            fermerTousLesMenus();
            
            // Ouverture du menu si il était fermé
            if (!estOuvert) {
                ouvrirMenu(menu, bouton);
            }
        });
    });
    
    // Fermeture des menus avec la touche Échap
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            console.log('[Menus] Fermeture des menus avec Échap');
            fermerTousLesMenus();
        }
    });
} d\'actions');
    
    // Création de l'overlay pour fermer les menus en cliquant ailleurs
    creerOverlayMenus();
    
    // Récupération de tous les boutons d'actions
    const boutonsActions = document.querySelectorAll('.actions-trigger');
    console.log(`[Menus] ${boutonsActions.length} menus d'actions trouvés`);
    
    // Initialisation de chaque bouton
    boutonsActions.forEach((bouton, index) => {
        bouton.addEventListener('click', function(event) {
            // Empêche la propagation pour éviter la fermeture immédiate
            event.stopPropagation();
            
            const nomEtudiant = bouton.getAttribute('data-student-name');
            console.log(`[Menus] Clic sur menu pour ${nomEtudiant}`);
            
            // Récupération du menu associé
            const menu = bouton.parentElement.querySelector('.actions-dropdown');
            if (!menu) {
                console.error('[Menus] Menu déroulant introuvable');
                return;
            }
            
            // Gestion de l'ouverture/fermeture
            const estOuvert = menu.classList.contains('active');
            
            // Fermeture de tous les autres menus
            fermerTousLesMenus();
            
            // Ouverture du menu si il était fermé
            if (!estOuvert) {
                ouvrirMenu(menu, bouton);
            }
        });
    });
    
    // Fermeture des menus avec la touche Échap
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            console.log('[Menus] Fermeture des menus avec Échap');
            fermerTousLesMenus();
        }
    });
}

/**
 * Crée un overlay invisible pour fermer les menus en cliquant ailleurs
 */
function creerOverlayMenus() {
    // Vérification qu'il n'existe pas déjà
    if (document.querySelector('.dropdown-overlay')) {
        return;
    }
    
    const overlay = document.createElement('div');
    overlay.className = 'dropdown-overlay';
    document.body.appendChild(overlay);
    
    // Fermeture des menus au clic sur l'overlay
    overlay.addEventListener('click', function() {
        console.log('[Menus] Fermeture via overlay');
        fermerTousLesMenus();
    });
}

/**
 * Ouvre un menu déroulant avec vérification de position
 * @param {HTMLElement} menu - Element du menu à ouvrir
 * @param {HTMLElement} bouton - Bouton qui a déclenché l'ouverture
 */
function ouvrirMenu(menu, bouton) {
    console.log('[Menus] Ouverture d\'un menu');
    
    // Affichage du menu
    menu.classList.add('active');
    
    // Activation de l'overlay
    const overlay = document.querySelector('.dropdown-overlay');
    if (overlay) {
        overlay.classList.add('active');
    }
    
    // Vérification et ajustement de la position pour éviter le débordement
    ajusterPositionMenu(menu);
    
    // Focus sur le premier élément du menu pour l'accessibilité
    const premierItem = menu.querySelector('.dropdown-item');
    if (premierItem) {
        premierItem.focus();
    }
}

/**
 * Ajuste la position du menu pour éviter qu'il sorte de l'écran
 * @param {HTMLElement} menu - Menu à ajuster
 */
function ajusterPositionMenu(menu) {
    const rectMenu = menu.getBoundingClientRect();
    const largeurEcran = window.innerWidth;
    const hauteurEcran = window.innerHeight;
    
    // Ajustement horizontal si le menu dépasse à droite
    if (rectMenu.right > largeurEcran - 10) {
        console.log('[Menus] Ajustement position : menu trop à droite');
        menu.style.right = '0';
        menu.style.left = 'auto';
    }
    
    // Ajustement vertical si le menu dépasse en bas
    if (rectMenu.bottom > hauteurEcran - 10) {
        console.log('[Menus] Ajustement position : menu trop en bas');
        menu.style.top = 'auto';
        menu.style.bottom = '100%';
    }
}

/**
 * Ferme tous les menus déroulants ouverts
 */
function fermerTousLesMenus() {
    console.log('[Menus] Fermeture de tous les menus');
    
    // Fermeture de tous les menus actifs - SÉLECTEUR CORRIGÉ
    const menusOuverts = document.querySelectorAll('.dropdown-menu.active');
    menusOuverts.forEach(menu => {
        menu.classList.remove('active');
        
        // Remise à zéro des styles de position
        menu.style.right = '';
        menu.style.left = '';
        menu.style.top = '';
        menu.style.bottom = '';
    });
    
    // Désactivation de l'overlay
    const overlay = document.querySelector('.dropdown-overlay');
    if (overlay) {
        overlay.classList.remove('active');
    }
}

/**
 * Initialise la signature manuelle pour les apprenants
 * Permet au formateur de signer à la place d'un apprenant
 */
function initialiserSignatureManuelle() {
    console.log('[Actions] Initialisation signature manuelle');
    
    // Récupération de tous les boutons de signature manuelle - SÉLECTEUR CORRIGÉ
    const boutonsSignature = document.querySelectorAll('.sign-manual');
    console.log(`[Actions] ${boutonsSignature.length} boutons signature manuelle trouvés`);
    
    boutonsSignature.forEach(bouton => {
        bouton.addEventListener('click', function() {
            // Récupération des données nécessaires
            const signatureId = bouton.getAttribute('data-signature-id');
            const nomEtudiant = bouton.getAttribute('data-student-name');
            
            console.log(`[Actions] Signature manuelle demandée pour ${nomEtudiant}`, { signatureId });
            
            // Vérification des données
            if (!signatureId) {
                console.error('[Actions] ID de signature manquant');
                alert('Erreur : Impossible de procéder à la signature manuelle');
                return;
            }
            
            // Fermeture du menu et ouverture de la popup de signature
            fermerTousLesMenus();
            ouvrirPopupSignature('apprenant', signatureId, `Signature manuelle de ${nomEtudiant}`);
        });
    });
}

/**
 * Initialise la gestion des retards
 * Permet d'enregistrer un motif de retard pour un apprenant
 */
function initialiserGestionRetards() {
    console.log('[Actions] Initialisation gestion des retards');
    
    // SÉLECTEUR CORRIGÉ
    const boutonsRetard = document.querySelectorAll('.mark-late');
    console.log(`[Actions] ${boutonsRetard.length} boutons retard trouvés`);
    
    boutonsRetard.forEach(bouton => {
        bouton.addEventListener('click', function() {
            const signatureId = bouton.getAttribute('data-signature-id');
            const sessionId = bouton.getAttribute('data-session-id');
            const nomEtudiant = bouton.closest('tr').querySelector('.student-name')?.textContent || 'Étudiant';
            
            console.log(`[Actions] Retard demandé pour ${nomEtudiant}`, { signatureId, sessionId });
            
            // Vérification des données
            if (!signatureId || !sessionId) {
                console.error('[Actions] Données manquantes pour le retard');
                alert('Erreur : Impossible d\'enregistrer le retard');
                return;
            }
            
            // Fermeture du menu
            fermerTousLesMenus();
            
            // Utilisation du prompt simple pour l'instant (à remplacer par modal si besoin)
            const motif = prompt(`Motif de retard pour ${nomEtudiant} :`);
            if (motif && motif.trim() !== '') {
                enregistrerRetard(signatureId, sessionId, motif, nomEtudiant);
            }
        });
    });
}

/**
 * Initialise la gestion des commentaires
 * Permet d'ajouter un commentaire à un apprenant
 */
function initialiserGestionCommentaires() {
    console.log('[Actions] Initialisation gestion des commentaires');
    
    // SÉLECTEUR CORRIGÉ
    const boutonsCommentaire = document.querySelectorAll('.add-comment');
    console.log(`[Actions] ${boutonsCommentaire.length} boutons commentaire trouvés`);
    
    boutonsCommentaire.forEach(bouton => {
        bouton.addEventListener('click', function() {
            const signatureId = bouton.getAttribute('data-signature-id');
            const nomEtudiant = bouton.closest('tr').querySelector('.student-name')?.textContent || 'Étudiant';
            
            console.log(`[Actions] Commentaire demandé pour ${nomEtudiant}`, { signatureId });
            
            // Vérification des données
            if (!signatureId) {
                console.error('[Actions] ID de signature manquant pour commentaire');
                alert('Erreur : Impossible d\'ajouter le commentaire');
                return;
            }
            
            // Fermeture du menu
            fermerTousLesMenus();
            
            // Utilisation du prompt simple pour l'instant
            const commentaire = prompt(`Commentaire pour ${nomEtudiant} :`);
            if (commentaire && commentaire.trim() !== '') {
                enregistrerCommentaire(signatureId, commentaire, nomEtudiant);
            }
        });
    });
}

/**
 * Enregistre un commentaire en base de données
 * @param {string} signatureId - ID de la signature
 * @param {string} commentaire - Texte du commentaire
 * @param {string} nomEtudiant - Nom de l'étudiant (pour les logs)
 */
function enregistrerCommentaire(signatureId, commentaire, nomEtudiant) {
    console.log(`[Actions] Enregistrement commentaire pour ${nomEtudiant}`, { commentaire });
    
    // Construction de l'URL
    const url = window.routes.ajouterCommentaire.replace('SIGNATURE_ID', signatureId);
    
    // Envoi de la requête
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `commentaire=${encodeURIComponent(commentaire)}`
    })
    .then(response => {
        console.log('[Actions] Réponse serveur pour commentaire reçue');
        if (response.ok) {
            console.log('[Actions] Commentaire enregistré avec succès');
            afficherToast(`Commentaire ajouté pour ${nomEtudiant}`, 'success');
            
            // Rechargement de la page après un délai
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            throw new Error(`Erreur HTTP ${response.status}`);
        }
    })
    .catch(error => {
        console.error('[Actions] Erreur lors de l\'enregistrement du commentaire :', error);
        afficherToast('Erreur lors de l\'enregistrement du commentaire', 'error');
    });
}

// =========================================================================
// 5. FONCTIONS UTILITAIRES
// =========================================================================

/**
 * Ouvre une modal de saisie de texte personnalisée
 * Remplace les alert() et prompt() natifs par une interface plus moderne
 * 
 * @param {string} titre - Titre de la modal
 * @param {string} message - Message à afficher
 * @param {function} callback - Fonction appelée avec le texte saisi
 */
function ouvrirModalSaisie(titre, message, callback) {
    console.log(`[Modal] Ouverture modal de saisie : ${titre}`);
    
    // Création de la modal
    const modal = document.createElement('div');
    modal.className = 'modal-saisie-overlay';
    modal.innerHTML = `
        <div class="modal-saisie">
            <h3>${titre}</h3>
            <p>${message}</p>
            <textarea id="modal-saisie-input" rows="4" placeholder="Saisissez votre texte ici..."></textarea>
            <div class="modal-saisie-actions">
                <button type="button" class="btn-modal-annuler">Annuler</button>
                <button type="button" class="btn-modal-confirmer">Confirmer</button>
            </div>
        </div>
    `;
    
    // Ajout au DOM
    document.body.appendChild(modal);
    
    // Récupération des éléments
    const input = modal.querySelector('#modal-saisie-input');
    const btnAnnuler = modal.querySelector('.btn-modal-annuler');
    const btnConfirmer = modal.querySelector('.btn-modal-confirmer');
    
    // Focus sur le champ de saisie
    setTimeout(() => input.focus(), 100);
    
    // Fonction de fermeture
    function fermerModal() {
        document.body.removeChild(modal);
    }
    
    // Gestion des événements
    btnAnnuler.addEventListener('click', fermerModal);
    
    btnConfirmer.addEventListener('click', function() {
        const valeur = input.value.trim();
        console.log(`[Modal] Valeur saisie : "${valeur}"`);
        callback(valeur);
        fermerModal();
    });
    
    // Fermeture avec Échap
    modal.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            fermerModal();
        } else if (event.key === 'Enter' && event.ctrlKey) {
            btnConfirmer.click();
        }
    });
    
    // Fermeture en cliquant sur l'overlay
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            fermerModal();
        }
    });
}

/**
 * Affiche un toast de notification
 * @param {string} message - Message à afficher
 * @param {string} type - Type de notification ('success', 'error', 'warning', 'info')
 */
function afficherToast(message, type = 'info') {
    console.log(`[Toast] Affichage ${type} : ${message}`);
    
    // Création du toast
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    // Icône selon le type
    let icone = 'fas fa-info-circle';
    switch (type) {
        case 'success': icone = 'fas fa-check-circle'; break;
        case 'error': icone = 'fas fa-exclamation-triangle'; break;
        case 'warning': icone = 'fas fa-exclamation-circle'; break;
    }
    
    toast.innerHTML = `
        <i class="${icone}"></i>
        <span>${message}</span>
    `;
    
    // Ajout au DOM
    document.body.appendChild(toast);
    
    // Animation d'entrée
    setTimeout(() => toast.classList.add('toast-show'), 100);
    
    // Suppression automatique
    setTimeout(() => {
        toast.classList.remove('toast-show');
        setTimeout(() => {
            if (document.body.contains(toast)) {
                document.body.removeChild(toast);
            }
        }, 300);
    }, 4000);
}

// =========================================================================
// 6. CONFIGURATION DES ROUTES (À DÉFINIR DANS LE TEMPLATE)
// =========================================================================

/**
 * Routes utilisées par le JavaScript
 * Ces URLs doivent être définies dans le template Twig
 * 
 * Exemple d'utilisation dans le template :
 * <script>
 * window.routes = {
 *     signatureFormateur: "{{ path('formateur_sauvegarder_signature', {'id': app.request.get('id')}) }}",
 *     signatureManuelle: "{{ path('apprenant_signature_manual_sign', {'id': 'SIGNATURE_ID'}) }}",
 *     marquerRetard: "{{ path('signature_session_late', {'id': 'SIGNATURE_ID'}) }}",
 *     ajouterCommentaire: "{{ path('signature_session_comment', {'id': 'SIGNATURE_ID'}) }}"
 * };
 * </script>
 */
if (!window.routes) {
    console.error('[Routes] ERREUR : Routes non définies !');
    console.error('[Routes] Veuillez ajouter les routes dans le template Twig');
    
    // Routes par défaut (à adapter selon votre configuration)
    window.routes = {
        signatureFormateur: '/formateur/sauvegarder-signature/' + window.sessionId,
        signatureManuelle: '/apprenant/signature-session/SIGNATURE_ID/manual-sign',
        marquerRetard: '/signature-session/SIGNATURE_ID/late',
        ajouterCommentaire: '/signature-session/SIGNATURE_ID/comment'
    };
}

/**
 * Styles CSS pour les éléments créés dynamiquement
 * (Modal et toasts)
 */
function ajouterStylesDynamiques() {
    if (document.querySelector('#styles-dynamiques')) {
        return; // Déjà ajoutés
    }
    
    const style = document.createElement('style');
    style.id = 'styles-dynamiques';
    style.textContent = `
        /* Modal de saisie */
        .modal-saisie-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10001;
        }
        
        .modal-saisie {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 500px;
            width: 90vw;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        
        .modal-saisie h3 {
            margin: 0 0 1rem 0;
            color: #0E1E5B;
            font-size: 1.3rem;
        }
        
        .modal-saisie p {
            margin-bottom: 1.5rem;
            color: #6c757d;
        }
        
        .modal-saisie textarea {
            width: 100%;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            font-family: inherit;
            font-size: 1rem;
            resize: vertical;
            margin-bottom: 1.5rem;
        }
        
        .modal-saisie textarea:focus {
            outline: none;
            border-color: #e85c33;
        }
        
        .modal-saisie-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        
        .btn-modal-annuler,
        .btn-modal-confirmer {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-modal-annuler {
            background: #6c757d;
            color: white;
        }
        
        .btn-modal-confirmer {
            background: #e85c33;
            color: white;
        }
        
        .btn-modal-annuler:hover {
            background: #5a6268;
        }
        
        .btn-modal-confirmer:hover {
            background: #d64520;
        }
        
        /* Toasts */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            padding: 1rem 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            z-index: 10002;
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s ease;
            max-width: 400px;
        }
        
        .toast.toast-show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .toast-success {
            border-left: 4px solid #4CAF50;
            color: #4CAF50;
        }
        
        .toast-error {
            border-left: 4px solid #F44336;
            color: #F44336;
        }
        
        .toast-warning {
            border-left: 4px solid #FF9800;
            color: #FF9800;
        }
        
        .toast-info {
            border-left: 4px solid #2196F3;
            color: #2196F3;
        }
    `;
    
    document.head.appendChild(style);
}

// Ajout des styles au chargement
ajouterStylesDynamiques();

console.log('[Fiche Présence] JavaScript chargé et prêt');

/**
 * =========================================================================
 * FIN DU FICHIER JAVASCRIPT FICHE DE PRÉSENCE
 * =========================================================================
 * 
 * Ce fichier contient toute la logique JavaScript pour la fiche de présence.
 * Il est organisé en sections claires et fortement commenté pour faciliter
 * la compréhension et la maintenance.
 * 
 * Pour votre présentation orale, vous pouvez expliquer :
 * 1. L'architecture modulaire du code
 * 2. La séparation des responsabilités
 * 3. La gestion des erreurs et du feedback utilisateur
 * 4. L'accessibilité et l'UX
 * 5. La communication avec le serveur (API)
 * =========================================================================
 */