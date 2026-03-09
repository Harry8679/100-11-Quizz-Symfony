<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class QuizController extends AbstractController
{
    // Les questions du quiz définies en dur dans le controller
    // (pas de BDD pour ce projet — l'objectif c'est les sessions)
    private array $questions = [
        [
            'id'       => 1,
            'question' => 'Quelle est la capitale de la France ?',
            'choix'    => ['Paris', 'Lyon', 'Marseille', 'Bordeaux'],
            'reponse'  => 'Paris',
        ],
        [
            'id'       => 2,
            'question' => 'Combien font 7 × 8 ?',
            'choix'    => ['54', '56', '58', '64'],
            'reponse'  => '56',
        ],
        [
            'id'       => 3,
            'question' => 'Quel langage utilise Symfony ?',
            'choix'    => ['Python', 'Ruby', 'PHP', 'Java'],
            'reponse'  => 'PHP',
        ],
        [
            'id'       => 4,
            'question' => 'En quelle année a été créé Symfony ?',
            'choix'    => ['2002', '2005', '2008', '2010'],
            'reponse'  => '2005',
        ],
        [
            'id'       => 5,
            'question' => 'Que signifie HTML ?',
            'choix'    => [
                'HyperText Markup Language',
                'High Transfer Markup Language',
                'HyperText Modern Language',
                'Home Tool Markup Language',
            ],
            'reponse'  => 'HyperText Markup Language',
        ],
    ];

    // ─── PAGE D'ACCUEIL du quiz ───────────────────────────────────────────────
    #[Route('/quiz', name: 'app_quiz_index')]
    public function index(): Response
    {
        return $this->render('quiz/index.html.twig', [
            'total' => count($this->questions),
        ]);
    }

    // ─── DÉMARRER un nouveau quiz ────────────────────────────────────────────
    #[Route('/quiz/start', name: 'app_quiz_start')]
    public function start(Request $request): Response
    {
        $session = $request->getSession();

        // Initialiser l'état du quiz en session
        // La session est un stockage clé/valeur persistant entre les requêtes
        $session->set('quiz_score',    0);        // score actuel
        $session->set('quiz_question', 0);        // index de la question actuelle
        $session->set('quiz_reponses', []);       // tableau des réponses données

        return $this->redirectToRoute('app_quiz_question');
    }

    // ─── AFFICHER la question courante ───────────────────────────────────────
    #[Route('/quiz/question', name: 'app_quiz_question')]
    public function question(Request $request): Response
    {
        $session = $request->getSession();

        // Lire l'index de la question courante depuis la session
        $index = $session->get('quiz_question', 0);

        // Si on a dépassé toutes les questions → résultats
        if ($index >= count($this->questions)) {
            return $this->redirectToRoute('app_quiz_resultat');
        }

        $questionCourante = $this->questions[$index];

        return $this->render('quiz/question.html.twig', [
            'question' => $questionCourante,
            'numero'   => $index + 1,          // numéro affiché (commence à 1)
            'total'    => count($this->questions),
            'progress' => round(($index / count($this->questions)) * 100), // % pour la barre
        ]);
    }

    // ─── TRAITER la réponse soumise ──────────────────────────────────────────
    #[Route('/quiz/repondre', name: 'app_quiz_repondre', methods: ['POST'])]
    public function repondre(Request $request): Response
    {
        $session = $request->getSession();

        $index   = $session->get('quiz_question', 0);
        $score   = $session->get('quiz_score', 0);
        $reponses = $session->get('quiz_reponses', []);

        // Récupérer la réponse choisie par l'utilisateur
        $reponseUtilisateur = $request->request->get('reponse');
        $questionCourante   = $this->questions[$index];

        // Vérifier si la réponse est correcte
        $estCorrecte = ($reponseUtilisateur === $questionCourante['reponse']);

        if ($estCorrecte) {
            $score++;
            $session->set('quiz_score', $score);
        }

        // Sauvegarder la réponse pour l'afficher dans les résultats
        $reponses[] = [
            'question'           => $questionCourante['question'],
            'reponseUtilisateur' => $reponseUtilisateur,
            'bonneReponse'       => $questionCourante['reponse'],
            'estCorrecte'        => $estCorrecte,
        ];
        $session->set('quiz_reponses', $reponses);

        // Passer à la question suivante
        $session->set('quiz_question', $index + 1);

        return $this->redirectToRoute('app_quiz_question');
    }

    // ─── RÉSULTATS finaux ────────────────────────────────────────────────────
    #[Route('/quiz/resultat', name: 'app_quiz_resultat')]
    public function resultat(Request $request): Response
    {
        $session = $request->getSession();

        $score    = $session->get('quiz_score', 0);
        $reponses = $session->get('quiz_reponses', []);
        $total    = count($this->questions);

        // Calculer le pourcentage
        $pourcentage = $total > 0 ? round(($score / $total) * 100) : 0;

        // Déterminer le message selon le score
        $message = match(true) {
            $pourcentage === 100 => '🏆 Parfait ! Score parfait !',
            $pourcentage >= 80   => '🎉 Excellent ! Très bon résultat !',
            $pourcentage >= 60   => '👍 Bien joué ! Continue comme ça.',
            $pourcentage >= 40   => '😐 Pas mal, mais tu peux mieux faire.',
            default              => '😅 Essaie encore, tu vas progresser !',
        };

        return $this->render('quiz/resultat.html.twig', [
            'score'       => $score,
            'total'       => $total,
            'pourcentage' => $pourcentage,
            'reponses'    => $reponses,
            'message'     => $message,
        ]);
    }
}
