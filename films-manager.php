<?php
// Connexion à la base de données
$servername = "localhost";
$username = "ericfourmaux";
$password = "L1m01!ou";
$dbname = "ERICFOURMAUX";

// Création de la connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérification de la connexion
if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Fonction pour créer un nouveau film
function creerFilm($conn, $titre, $annee, $duree, $description, $codeCompagnie, $langueOriginale) {
    $query = "INSERT INTO FILM 
              (titre, duree, description, codeCompagnie, langueOriginale, dateAjout) 
              VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $duree = (int)$duree;
    
    // Générer la date d'aujourd'hui au format MySQL
    $dateAjout = date('Y-m-d');
    
    // Liaison des paramètres avec la méthode bind_param (s = string, i = integer)
    $stmt->bind_param("sissss", $titre, $duree, $description, $codeCompagnie, $langueOriginale, $dateAjout);
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Traitement du formulaire de création
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'creer') {
    try {
        $result = creerFilm(
            $conn,
            $_POST['titre'], 
            $_POST['annee'], 
            $_POST['duree'], 
            $_POST['description'], 
            $_POST['codeCompagnie'], 
            $_POST['langueOriginale']
        );
        
        if ($result) {
            $message = "Le film a été créé avec succès.";
            $messageType = "success";
        } else {
            $message = "Erreur lors de la création du film: " . $conn->error;
            $messageType = "error";
        }
    } catch (Exception $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
    }
    
    // Redirection pour éviter la soumission multiple du formulaire
    header("Location: stats.php?message=" . urlencode($message) . "&type=" . $messageType);
    exit();
}

// Modifier un film
function modifierFilm($conn, $codeFilm, $titre, $duree, $description, $codeCompagnie, $langueOriginale) {
    $query = "UPDATE FILM SET 
              titre = ?, 
              duree = ?, 
              description = ?, 
              codeCompagnie = ?, 
              langueOriginale = ? 
              WHERE codeFilm = ?";
    
    $stmt = $conn->prepare($query);
    $duree = (int)$duree;
    $codeFilm = (int)$codeFilm;
    
    $stmt->bind_param("sisssi", $titre, $duree, $description, $codeCompagnie, $langueOriginale, $codeFilm);
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier') {
    try {
        $result = modifierFilm(
            $conn,
            $_POST['codeFilm'],
            $_POST['titre'], 
            $_POST['duree'], 
            $_POST['description'], 
            $_POST['codeCompagnie'], 
            $_POST['langueOriginale']
        );
        
        if ($result) {
            $message = "Le film a été modifié avec succès.";
            $messageType = "success";
        } else {
            $message = "Erreur lors de la modification du film: " . $conn->error;
            $messageType = "error";
        }
    } catch (Exception $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
    }
    
    // Redirection pour éviter la soumission multiple du formulaire
    header("Location: stats.php?message=" . urlencode($message) . "&type=" . $messageType);
    exit();
}

// Supprimer un film
function supprimerFilm($conn, $codeFilm) {
    // Vérifier d'abord si le film a des commandes associées
    $checkCommandes = "SELECT COUNT(*) as count FROM COMMANDE WHERE codeFilm = ?";
    $stmt = $conn->prepare($checkCommandes);
    $stmt->bind_param("i", $codeFilm);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        $stmt->close();
        return false; // Ne pas supprimer si des commandes sont associées
    }
    
    // Supprimer d'abord les enregistrements dans les tables associées
    $tables = ['FILM_CATEGORIE', 'FILM_LANGUE', 'FILMS_SIMILAIRES', 'ACTEUR_FILM', 'STAFF_FILM', 'NOTE'];
    
    foreach ($tables as $table) {
        $query = "DELETE FROM $table WHERE codeFilm = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $codeFilm);
        $stmt->execute();
        $stmt->close();
    }
    
    // Supprimer les références dans FILMS_SIMILAIRES où ce film est référencé
    $query = "DELETE FROM FILMS_SIMILAIRES WHERE codeFilmSimilaire = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $codeFilm);
    $stmt->execute();
    $stmt->close();
    
    // Supprimer le film
    $query = "DELETE FROM FILM WHERE codeFilm = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $codeFilm);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'supprimer') {
    try {
        $result = supprimerFilm($conn, $_POST['codeFilm']);
        
        if ($result) {
            $message = "Le film a été supprimé avec succès.";
            $messageType = "success";
        } else {
            $message = "Impossible de supprimer ce film. Il peut être référencé par des commandes.";
            $messageType = "error";
        }
    } catch (Exception $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
    }
    
    header("Location: stats.php?message=" . urlencode($message) . "&type=" . $messageType);
    exit();
}

// Récupération d'un film par son ID
function getFilmById($conn, $codeFilm) {
    $query = "SELECT * FROM FILM WHERE codeFilm = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $codeFilm);
    $stmt->execute();
    $result = $stmt->get_result();
    $film = $result->fetch_assoc();
    $stmt->close();
    
    return $film;
}

// Récupération de tous les films
function getFilms($conn) {
    $sql = "SELECT f.codeFilm, 
                   f.titre, 
                   f.duree, 
                   f.dateAjout, 
                   f.description, 
                   f.noteGlobale, 
                   l.nomLangue,
                   c.nomCompagnie
            FROM FILM f
            LEFT JOIN LANGUE l ON f.langueOriginale = l.codeLangue
            LEFT JOIN COMPAGNIE c ON f.codeCompagnie = c.codeCompagnie
            ORDER BY f.dateAjout DESC";

    $result = $conn->query($sql);
    
    $films = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $films[] = $row;
        }
        $result->free_result();
    }

    return $films;
}

// Récupération des compagnies de production pour le formulaire
$queryCompagnies = "SELECT * FROM COMPAGNIE ORDER BY nomCompagnie";
$resultCompagnies = $conn->query($queryCompagnies);
$compagnies = [];

if ($resultCompagnies) {
    while ($row = $resultCompagnies->fetch_assoc()) {
        $compagnies[] = $row;
    }
    $resultCompagnies->free_result();
}

// Récupération des langues pour le formulaire
$queryLangues = "SELECT * FROM LANGUE ORDER BY nomLangue";
$resultLangues = $conn->query($queryLangues);
$langues = [];

if ($resultLangues) {
    while ($row = $resultLangues->fetch_assoc()) {
        $langues[] = $row;
    }
    $resultLangues->free_result();
}

// Récupération des catégories pour le formulaire
$queryCategories = "SELECT * FROM CATEGORIE ORDER BY nomCategorie";
$resultCategories = $conn->query($queryCategories);
$categories = [];

if ($resultCategories) {
    while ($row = $resultCategories->fetch_assoc()) {
        $categories[] = $row;
    }
    $resultCategories->free_result();
}

$films = getFilms($conn);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TP BD-Fletnix</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-900 text-black">

    <!-- Navigation -->
    <nav class="bg-gray-800 fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="#" class="text-2xl font-bold flx-pink" style="color: #EF5DA8;">Fletnix</a>
                    <div class="ml-10 flex space-x-8">
                        <a href="index.php" class="text-gray-300 hover:text-white">Accueil</a>
                        <a href="films-manager.php" class="text-gray-300 hover:text-white">Gestion des films</a>
                        <a href="stats.php" class="text-gray-300 hover:text-white">Statistiques</a>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="ml-4 relative">
                        <button class="flex items-center text-white">
                            <img class="h-8 w-8 rounded-full" 
                                 src="images/avatar_eric.svg" 
                                 alt="Profile">
                            <span class="ml-2"><?php echo htmlspecialchars($username, ENT_QUOTES | ENT_HTML401, 'UTF-8'); ?></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <section class="max-w-7xl mx-auto px-4 py-12">
        &nbsp;
    </section>
    
    
    <div class="container mx-auto text-black">  
        <h1 class="text-3xl font-bold mb-6 text-white">Gestion des Films</h1>

        <!-- Affichage des messages de confirmation/erreur -->
        <?php if (isset($_GET['message'])): ?>
            <div class="mb-4 p-4 rounded <?= $_GET['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <?= htmlspecialchars($_GET['message']) ?>
            </div>
        <?php endif; ?>

        <!-- Liste des films -->
        <div class="bg-gray-200 p-6 rounded shadow-md text-black">
            <h2 class="text-2xl font-bold mb-4">Retirer un film</h2>
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-300">
                        <th class="border p-2">Titre</th>
                        <th class="border p-2">Année</th>
                        <th class="border p-2">Durée</th>
                        <th class="border p-2">Compagnie</th>
                        <th class="border p-2">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($films as $film): ?>
                    <tr>
                        <td class="border p-2"><?= $film['titre'] ?></td>
                        <td class="border p-2"><?= htmlspecialchars($film['dateAjout']) ?></td>
                        <td class="border p-2"><?= htmlspecialchars($film['duree']) ?> min</td>
                        <td class="border p-2"><?= htmlspecialchars($film['nomCompagnie']) ?></td>
                        <td class="border p-2 text-center">
                            <form method="POST" style="display:inline" onsubmit="return confirm('Voulez-vous vraiment supprimer ce film ?');">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="codeFilm" value="<?= $film['codeFilm'] ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700">
                                    Supprimer
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <br>

        <!-- Formulaire de modification -->
        <form method="POST" class="bg-gray-200 p-6 rounded shadow-md mb-6">
            <input type="hidden" name="action" value="modifier" id="editForm">
            <input type="hidden" name="codeFilm" id="codeFilm">
            
            <h2 class="text-2xl font-bold mb-4">Éditer un film</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700">Sélection</label>
                    <select id="editFilm" class="w-full px-3 py-2 border rounded">
                        <option value="">-- Sélectionnez un film --</option>
                        <?php foreach($films as $film): ?>
                            <option value="<?= $film['codeFilm'] ?>">
                                <?= htmlspecialchars($film['titre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700">Titre</label>
                    <input type="text" name="titre" id="editTitre" class="w-full px-3 py-2 border bg-gray-50 p-4 rounded-lg">
                </div>

                <div>
                    <label class="block text-gray-700">Date d'ajout</label>
                    <input type="text" name="dateAjout" id="editAjout" disabled class="w-full px-3 py-2 border bg-gray-50 p-4 rounded-lg">
                </div>
                
                <div>
                    <label class="block text-gray-700">Durée (minutes)</label>
                    <input type="text" name="duree" id="editDuree" class="w-full px-3 py-2 border bg-gray-50 p-4 rounded-lg">
                </div>
                
                <div>
                    <label class="block text-gray-700">Compagnie de Production</label>
                    <select name="codeCompagnie" id="editCompagnie" class="w-full px-3 py-2 border rounded">
                        <?php foreach($compagnies as $compagnie): ?>
                            <option value="<?= $compagnie['codeCompagnie'] ?>">
                                <?= htmlspecialchars($compagnie['nomCompagnie']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700">Langue originale</label>
                    <select name="langueOriginale" id="editLangue" class="w-full px-3 py-2 border rounded">
                        <?php foreach($langues as $langue): ?>
                            <option value="<?= $langue['codeLangue'] ?>">
                                <?= htmlspecialchars($langue['nomLangue']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700">Note globale</label>
                    <input type="text" id="editNote" disabled class="w-full px-3 py-2 border bg-gray-50 p-4 rounded-lg">
                </div>
                
                <div class="col-span-2">
                    <label class="block text-gray-700">Description</label>
                    <textarea name="description" id="editDescription" class="w-full px-3 py-2 border bg-gray-50 p-4 rounded-lg"></textarea>
                </div>
            </div>
            
            <div class="mt-4 flex justify-between">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Enregistrer
                </button>
                <button type="reset" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                    Réinitialiser
                </button>
            </div>
        </form>

        <br>

        <!-- Formulaire de création -->
        <?php if (isset($_GET['message'])): ?>
            <div class="mb-4 p-4 rounded <?= $_GET['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <?= htmlspecialchars($_GET['message']) ?>
            </div>
        <?php endif; ?>

        <form id="filmForm" method="POST" class="bg-gray-200 p-6 rounded shadow-md mb-6">
            <input type="hidden" name="action" value="creer">
            
            <h2 class="text-2xl font-bold mb-4">Ajouter un film</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="titre" class="block text-gray-700 font-medium mb-1">Titre du film *</label>
                    <input type="text" name="titre" id="titre" required 
                        class="w-full px-3 py-2 border bg-gray-50 p-4 rounded-lg">
                </div>

                <div>
                    <label for="annee" class="block text-gray-700 font-medium mb-1">Année de sortie *</label>
                    <input type="text" name="annee" id="annee" required class="w-full px-3 py-2 border bg-gray-50 p-4 rounded-lg">
                </div>
                
                <div>
                    <label for="duree" class="block text-gray-700 font-medium mb-1">Durée (minutes) *</label>
                    <input type="text" name="duree" id="duree" required class="w-full px-3 py-2 border bg-gray-50 p-4 rounded-lg">
                </div>
                
                <div>
                    <label for="codeCompagnie" class="block text-gray-700 font-medium mb-1">Compagnie de production *</label>
                    <select name="codeCompagnie" id="codeCompagnie" required
                            class="w-full px-3 py-2 border bg-gray-50 p-4 rounded-lg">
                        <option value="">-- Sélectionnez une compagnie --</option>
                        <?php foreach($compagnies as $compagnie): ?>
                            <option value="<?= $compagnie['codeCompagnie'] ?>">
                                <?= htmlspecialchars($compagnie['nomCompagnie']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="langueOriginale" class="block text-gray-700 font-medium mb-1">Langue originale *</label>
                    <select name="langueOriginale" id="langueOriginale" required
                            class="w-full px-3 py-2 border bg-gray-50 p-4 rounded-lg">
                        <option value="">-- Sélectionnez une langue --</option>
                        <?php foreach($langues as $langue): ?>
                            <option value="<?= $langue['codeLangue'] ?>">
                                <?= htmlspecialchars($langue['nomLangue']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-span-2">
                    <label for="description" class="block text-gray-700 font-medium mb-1">Description</label>
                    <textarea name="description" id="description" rows="4"
                            class="w-full px-3 py-2 border bg-gray-50 p-4 rounded-lg"></textarea>
                </div>
            </div>
            
            <div class="mt-4 flex justify-between">
                <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600 transition">
                    Créer le film
                </button>
                <button type="reset" class="bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400 transition">
                    Réinitialiser
                </button>
            </div>
        </form>
    </div>


    <!-- Footer -->
    <footer class="bg-gray-800 mt-12 text-gray-400">
        <div class="max-w-7xl mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4">À propos</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white">Qui sommes-nous</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Carrières</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Aide</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white">FAQ</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Support technique</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Conditions d'utilisation</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Légal</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white">Confidentialité</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Cookies</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Suivez-nous</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="mt-8 pt-8 border-t border-gray-700 text-center text-gray-400">
                <p>Fletnix - Eric Fourmaux - RAC Cégep Limoilou</p>
            </div>
        </div>
    </footer>

    <script>

    // Chargement des sons
    createSound = new Audio();
    createSound.src="./sounds/create.wav";

    deleteSound = new Audio();
    deleteSound.src="./sounds/delete.wav";

    updateSound = new Audio()
    updateSound.src="./sounds/update.wav";

    errorSound = new Audio()
    errorSound.src="./sounds/error.wav";

    resetSound = new Audio();
    resetSound.src="./sounds/reset.mp3";
    
    // Modifier un film                      
    document.getElementById('editFilm').addEventListener('change', function() {
        const filmId = this.value;

        // Si aucun film n'est sélectionné, réinitialiser le formulaire
        if (!filmId) {
            document.getElementById('codeFilm').value = '';
            document.getElementById('editTitre').value = '';
            document.getElementById('editAjout').value = '';
            document.getElementById('editDuree').value = '';
            document.getElementById('editLangue').value = '';
            document.getElementById('editNote').value = '';
            document.getElementById('editDescription').value = '';
            resetSound.play();
            return;
        }

        // Chercher le film dans la liste des films déjà récupérés
        <?php echo "const filmsData = " . json_encode($films) . ";"; ?>

        const film = filmsData.find(f => f.codeFilm == filmId);

        if (film) {
            // Remplissage du formulaire avec les données du film
            document.getElementById('codeFilm').value = film.codeFilm;
            document.getElementById('editTitre').value = film.titre;
            document.getElementById('editAjout').value = film.dateAjout;
            document.getElementById('editDuree').value = film.duree;
            document.getElementById('editDescription').value = film.description;
            document.getElementById('editNote').value = film.noteGlobale;

            const selectCompagnie = document.getElementById('editCompagnie');
            for (let i = 0; i < selectCompagnie.options.length; i++) {
                if (selectCompagnie.options[i].value == film.codeCompagnie) {
                    selectCompagnie.selectedIndex = i;
                    break;
                }
            }

            const selectLangue = document.getElementById('editLangue');
            for (let i = 0; i < selectLangue.options.length; i++) {
                if (selectLangue.options[i].value == film.langueOriginale) {
                    selectLangue.selectedIndex = i;
                    break;
                }
            }
        }
    });


    // Validation du formulaire de création
    document.addEventListener('DOMContentLoaded', function() {
        // Référence au formulaire
        const filmForm = document.getElementById('filmForm');
        const formAction = document.getElementById('formAction');
        const submitButton = filmForm.querySelector('button[type="submit"]');
        
        // Validation côté client
        filmForm.addEventListener('submit', function(e) {
            const titre = document.getElementById('titre').value.trim();
            const annee = document.getElementById('annee').value;
            const duree = document.getElementById('duree').value;
            const compagnie = document.getElementById('codeCompagnie').value;
            const langue = document.getElementById('codeLangueOriginale').value;
            
            let isValid = true;
            let errorMessage = '';
            
            // Validation du titre
            if (titre === '') {
                isValid = false;
                errorMessage += "Le titre du film est requis.\n";
            }
            
            // Validation de l'année
            const currentYear = new Date().getFullYear();
            if (annee === '' || isNaN(annee) || annee < 1900 || annee > currentYear + 5) {
                isValid = false;
                errorMessage += `L'année doit être entre 1900 et ${currentYear + 5}.\n`;
            }
            
            // Validation de la durée
            if (duree === '' || isNaN(duree) || duree < 1) {
                isValid = false;
                errorMessage += "La durée doit être un nombre positif.\n";
            }
            
            // Validation de la compagnie
            if (compagnie === '') {
                isValid = false;
                errorMessage += "Veuillez sélectionner une compagnie de production.\n";
            }
            
            // Validation de la langue
            if (langue === '') {
                isValid = false;
                errorMessage += "Veuillez sélectionner une langue originale.\n";
            }
            
            // Afficher les erreurs ou soumettre le formulaire
            if (!isValid) {
                e.preventDefault();
                alert("Erreurs de validation :\n" + errorMessage);
                errorSound.play();
            } else {
                // Mise à jour du texte du bouton lors de la soumission
                submitButton.textContent = "Création en cours...";
                submitButton.disabled = true;
                createSound.play();
            }
        });
        
        // Réinitialisation du formulaire
        filmForm.addEventListener('reset', function() {
            // Réinitialiser l'action du formulaire à "creer"
            formAction.value = 'creer';
            document.getElementById('codeFilm').value = '';
            
            // Mettre à jour le texte du bouton de soumission
            submitButton.textContent = "Créer le film";
            
            // Attendre un moment pour permettre la réinitialisation des champs
            setTimeout(() => {
                // Réinitialiser les messages d'erreur personnalisés
                const errorElements = filmForm.querySelectorAll('.error-message');
                errorElements.forEach(el => el.remove());
                
                // Réinitialiser les styles de validation
                const inputElements = filmForm.querySelectorAll('input, select, textarea');
                inputElements.forEach(input => {
                    input.classList.remove('border-red-500');
                    input.classList.remove('border-green-500');
                });
            }, 10);

            resetSound.play();
        });
        
        // Ajouter des classes de style lors de la saisie
        const inputElements = filmForm.querySelectorAll('input, select, textarea');
        inputElements.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() !== '') {
                    this.classList.add('border-green-500');
                } else if (this.hasAttribute('required')) {
                    this.classList.add('border-red-500');
                }
            });
            
            input.addEventListener('focus', function() {
                this.classList.remove('border-red-500');
                this.classList.remove('border-green-500');
            });
        });
    });

    
    // Vérifier s'il y a un message d'erreur dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const messageType = urlParams.get('type');
    const message = urlParams.get('message');
    
    // Jouer le son approprié si un message est présent dans l'URL
    if (message) {
        if (messageType === 'error') {
            errorSound.play();
        } else if (message.includes('créé')) {
            createSound.play();
        } else if (message.includes('modifié')) {
            updateSound.play();
        } else if (message.includes('supprimé')) {
            deleteSound.play();
        }
    }
    
    </script>
</body>
</html>
<?php
$conn->close();
?>
