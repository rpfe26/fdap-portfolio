<?php
/**
 * Référentiel CAP EPC (Équipier Polyvalent du Commerce)
 * Mis à jour selon le référentiel officiel (referentielepc.pdf)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function fdap_get_referentiel_cap() {
    static $referentiel = null;
    if ($referentiel !== null) {
        return $referentiel;
    }

    $referentiel = [
        [
            'id' => 'P1',
            'label' => 'Pôle 1 : Recevoir et suivre les commandes',
            'metaCompetences' => [
                [
                    'id' => 'M1.1',
                    'label' => 'Participer à la passation des commandes fournisseurs',
                    'subCompetences' => [
                        'Surveiller l’état des stocks',
                        'Préparer les propositions de commandes',
                        'Utiliser le mode de transmission adapté',
                        'Transmettre la commande après validation',
                        'Assurer le suivi des commandes'
                    ]
                ],
                [
                    'id' => 'M1.2',
                    'label' => 'Réceptionner',
                    'subCompetences' => [
                        'Identifier les documents de livraison et de traçabilité',
                        'Contrôler la qualité et la quantité',
                        'Comparer le bon de commande et le bon de livraison',
                        'Relever les anomalies éventuelles et les transmettre au responsable',
                        'Classer les documents de réception et de traçabilité'
                    ]
                ],
                [
                    'id' => 'M1.3',
                    'label' => 'Stocker',
                    'subCompetences' => [
                        'Utiliser le matériel de manutention adapté',
                        'Ranger les produits dans le lieu approprié en réalisant la rotation',
                        'Trier et évacuer les contenants',
                        'Maintenir l’organisation et la propreté de la réserve'
                    ]
                ],
                [
                    'id' => 'M1.4',
                    'label' => 'Préparer les commandes destinées aux clients',
                    'subCompetences' => [
                        'Prélever et rassembler les produits commandés',
                        'Reconditionner et stocker les produits selon leur spécificité',
                        'Vérifier l’adéquation entre la commande et la préparation',
                        'Enregistrer et entreposer les colis destinés aux clients ou retournés'
                    ]
                ]
            ]
        ],
        [
            'id' => 'P2',
            'label' => 'Pôle 2 : Mise en valeur et approvisionnement',
            'metaCompetences' => [
                [
                    'id' => 'M2.1',
                    'label' => 'Approvisionner, mettre en rayon et ranger selon la nature des produits',
                    'subCompetences' => [
                        'Déterminer les quantités à mettre en rayon',
                        'Anticiper les ruptures en rayon',
                        'Identifier les produits à mettre en rayon',
                        'Acheminer les produits de la réserve vers la surface de vente',
                        'Déballer les produits à mettre en rayon',
                        'Appliquer les règles de présentation marchande',
                        'Effectuer le remplissage des linéaires, réaliser le facing, procéder au réassortiment',
                        'Procéder à la rotation des produits',
                        'Détecter les produits impropres à la vente et les retirer'
                    ]
                ],
                [
                    'id' => 'M2.2',
                    'label' => 'Mettre en valeur les produits et l’espace commercial',
                    'subCompetences' => [
                        'Participer à la mise en valeur des produits',
                        'Participer à l’aménagement de l’espace d’exposition, de vente, des vitrines',
                        'Veiller à la propreté et nettoyer les surfaces de vente',
                        'Veiller à conserver tous les lieux de vente rangés (cabines, rayons, etc...)'
                    ]
                ],
                [
                    'id' => 'M2.3',
                    'label' => 'Participer aux opérations de conditionnement des produits',
                    'subCompetences' => [
                        'Préparer et nettoyer les équipements et le mobilier',
                        'Rassembler le matériel et fournitures nécessaires à l\'opération',
                        'Sélectionner le(s) produit(s), selon les références, les quantités, les prix',
                        'Conditionner et / ou emballer le produit',
                        'Calculer le prix de vente'
                    ]
                ],
                [
                    'id' => 'M2.4',
                    'label' => 'Installer et mettre à jour la signalétique',
                    'subCompetences' => [
                        'Éditer des étiquettes prix, produits, étiquettes promotionnelles',
                        'Installer et mettre à jour l’ILV et la PLV',
                        'Mettre en place et vérifier le balisage',
                        'Vérifier l’exactitude de l’affichage et alerter en cas d’anomalies'
                    ]
                ],
                [
                    'id' => 'M2.5',
                    'label' => 'Lutter contre la démarque et participer aux opérations d’inventaire',
                    'subCompetences' => [
                        'Poser les antivols sur les produits',
                        'Identifier, repérer et implanter les produits à dates courtes',
                        'Repérer et enregistrer la démarque connue',
                        'Ranger et compter les produits',
                        'Enregistrer le comptage et rendre compte'
                    ]
                ]
            ]
        ],
        [
            'id' => 'P3',
            'label' => 'Pôle 3 : Conseiller et accompagner le client dans son parcours d’achat',
            'metaCompetences' => [
                [
                    'id' => 'M3.1',
                    'label' => 'Préparer son environnement de travail',
                    'subCompetences' => [
                        'Préparer son matériel',
                        'Respecter une tenue professionnelle adaptée au contexte et à l’image de l’unité commerciale',
                        'Vérifier le bon fonctionnement du matériel et des outils d’aide à la vente'
                    ]
                ],
                [
                    'id' => 'M3.2',
                    'label' => 'Prendre contact avec le client',
                    'subCompetences' => [
                        'Accueillir le client',
                        'S’adapter au contexte commercial et au comportement du client',
                        'Adopter une attitude d’accueil',
                        'Favoriser un climat de confiance'
                    ]
                ],
                [
                    'id' => 'M3.3',
                    'label' => 'Accompagner le parcours client dans un contexte omnicanal',
                    'subCompetences' => [
                        'Adopter une écoute active',
                        'Identifier la demande du client, la prendre en compte et / ou la transférer au responsable',
                        'Orienter le client',
                        'Informer le client',
                        'Conseiller le client'
                    ]
                ],
                [
                    'id' => 'M3.4',
                    'label' => 'Accompagner le client dans l\'utilisation des outils digitaux',
                    'subCompetences' => [
                        'Présenter le ou les produits',
                        'Proposer des services associés et complémentaires',
                        'Renseigner le bon de commande, le document de vente et rédiger un message',
                        'Remettre les colis, les sacs et les produits réservés aux clients',
                        'Réaliser des livraisons'
                    ]
                ],
                [
                    'id' => 'M3.5',
                    'label' => 'Finaliser la prise en charge du client',
                    'subCompetences' => [
                        'Enregistrer les achats et / ou retours',
                        'Proposer un moyen de fidélisation',
                        'Encaisser et / ou accompagner l’encaissement digital, automatique et / ou mobile',
                        'Réaliser les opérations complémentaires à l’encaissement',
                        'Prendre congé',
                        'Collecter et actualiser l’information sur le client',
                        'Fermer la caisse et procéder aux opérations de clôture'
                    ]
                ],
                [
                    'id' => 'M3.6',
                    'label' => 'Recevoir les réclamations courantes',
                    'subCompetences' => [
                        'Écouter et identifier clairement le type de réclamation',
                        'Proposer une action corrective dans le cas d’une réclamation simple',
                        'Transférer les réclamations non solutionnées au responsable',
                        'Expliquer au client la solution proposée'
                    ]
                ]
            ]
        ]
    ];
    return $referentiel;
}
