<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TP BD-Fletnix</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-900 text-white">
<!-- PHP -->
<?php
    // Configuration de connexion à la base de données
    $servername = "localhost";
    $username = "ericfourmaux"; // Utilisateur par défaut de PHPMyAdmin
    $password = "L1m01!ou"; // Mot de passe vide par défaut
    $dbname = "ERICFOURMAUX"; // Nom de votre base de données

    // Création de connexion
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Test de connexion
    if ($conn->connect_error) {
        die("Connexion échouée: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");

    // Fonction pour récupérer les films (tous ou un spécifique)
    function getFilmDetails($conn, $filmId = null) {
        $sqlBase = "SELECT f.codeFilm, f.titre, f.duree, 
                    f.noteGlobale, f.description, 
                    cp.nomCompagnie AS nomCompagnie,
                    l.nomLangue AS langueOriginale
                    FROM FILM f
                    LEFT JOIN COMPAGNIE cp ON f.codeCompagnie = cp.codeCompagnie
                    LEFT JOIN LANGUE l ON f.langueOriginale = l.codeLangue";

        if ($filmId === null) {
            $sql = $sqlBase;
            $result = $conn->query($sql);
        } else {
            $sql = $sqlBase . " WHERE f.codeFilm = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $filmId);
            $stmt->execute();
            $result = $stmt->get_result();
        }

        if ($result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC); // Retourne tous les films sous forme de tableau associatif
        } else {
            return "<p class='text-red-500'>Film introuvable !</p>";
        }
    }

    function getFilmsByCategory($conn, $categorie) {
        $sql = "SELECT f.codeFilm, f.titre, f.duree, f.noteGlobale, f.description, 
                       cp.nomCompagnie AS nomCompagnie, l.nomLangue AS langueOriginale
                FROM FILM f
                LEFT JOIN COMPAGNIE cp ON f.codeCompagnie = cp.codeCompagnie
                LEFT JOIN LANGUE l ON f.langueOriginale = l.codeLangue
                INNER JOIN FILM_CATEGORIE fc ON f.codeFilm = fc.codeFilm
                INNER JOIN CATEGORIE c ON fc.codeCategorie = c.codeCategorie
                WHERE c.nomCategorie = ?";
    
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $categorie);
        $stmt->execute();
        $result = $stmt->get_result();
    
        $films = [];
        while ($row = $result->fetch_assoc()) {
            $films[] = $row;
        }
    
        return $films;
    }

    function getActorsAndCrewByFilm($conn, $filmId) {
        $actorsQuery = "SELECT 
                            p.codePersonne AS personneCodePersonne,
                            p.prenom AS personnePrenom,
                            p.nom AS personneNom,
                            af.nomPersonnage AS acteurNomPersonnage
                        FROM ACTEUR_FILM af
                        INNER JOIN PERSONNE p ON af.codePersonne = p.codePersonne
                        WHERE af.codeFilm = ?";
    
        $staffQuery = "SELECT 
                            p.codePersonne AS personneCodePersonne,
                            p.prenom AS personnePrenom,
                            p.nom AS personneNom,
                            tt.nomTypeTravail AS nomTypeTravail
                        FROM STAFF_FILM sf
                        INNER JOIN PERSONNE p ON sf.codePersonne = p.codePersonne
                        INNER JOIN TYPE_TRAVAIL tt ON sf.codeTypeTravail = tt.codeTypeTravail
                        WHERE sf.codeFilm = ?";
    
        // Récupération des acteurs
        $stmt = $conn->prepare($actorsQuery);
        $stmt->bind_param("s", $filmId);
        $stmt->execute();
        $result = $stmt->get_result();
        $actors = [];
        while ($row = $result->fetch_assoc()) {
            $actors[] = $row;
        }
    
        // Récupération de l'équipe technique
        $stmt = $conn->prepare($staffQuery);
        $stmt->bind_param("s", $filmId);
        $stmt->execute();
        $result = $stmt->get_result();
        $staff = [];
        while ($row = $result->fetch_assoc()) {
            $staff[] = $row;
        }
    
        return [
            "acteurs" => $actors,
            "equipe" => $staff
        ];
    }
    
    // Fonction pour obtenir le chemin de l'image d'un film
    function getImagePath($filmId) {
        $imagePath = "images/films/{$filmId}.jpg";
        $placeholderPath = "images/placeholder.jpg";
        
        return file_exists($imagePath) ? $imagePath : $placeholderPath;
    }
?>

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
                        <button class="flex items-center">
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

    <!-- Hero Section -->
    <div class="relative pt-16">
        <div class="absolute inset-0">
            <img src="images/hero-background.jpg" 
                 class="w-full h-[600px] object-cover" 
                 alt="Background">
            <div class="absolute inset-0 bg-gradient-to-r from-gray-900"></div>
        </div>
        
        <div class="relative max-w-7xl mx-auto px-4 py-32">
            <h1 class="text-4xl font-bold tracking-tight sm:text-5xl md:text-6xl">
                Les meilleurs films
                <span class="block flx-pink">à portée de clic</span>
            </h1>
            <p class="mt-6 max-w-lg text-xl text-gray-300">
                Découvrez des milliers de films, nouveautés, classiques, séries et plus encore.
            </p>
        </div>
    </div>

    <!-- Films Populaires -->
    <section class="max-w-7xl mx-auto px-4 py-12">
        <h2 class="text-2xl font-semibold mb-6">Films Populaires</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
        <?php $films = getFilmDetails($conn); ?>
        <?php if (!empty($films)): ?>
            <?php foreach ($films as $film): ?>
            <div class="relative group">
                <img src="images/films/<?php echo htmlspecialchars($film['codeFilm'] ?? '1', ENT_QUOTES, 'UTF-8'); ?>.jpg" 
                    class="rounded-lg w-full h-72 object-cover transform transition duration-300 group-hover:scale-105"
                    alt="<?php echo htmlspecialchars($film['titre']); ?>"
                    onerror="this.onerror=null; this.src='images/placeholder.jpg';">
                <div class="absolute inset-0 bg-gradient-to-t from-black opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-lg">
                    <div class="absolute bottom-0 p-4">
                        <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($film['titre']); ?></h3>
                        <div class="flex items-center mt-2">
                            <div class="flex items-center">
                                <i class="fas fa-star text-yellow-400 mr-1"></i>
                                <span><?php echo number_format($film['noteGlobale']/20, 1); ?>/5</span>
                            </div>
                            <span class="mx-2">•</span>
                            <span><?php echo $film['duree']; ?> min</span>
                        </div>
                        <button class="mt-3 w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded-md transition duration-300"
                                onclick="openPlayerModal('player-modal-<?php echo $film['codeFilm']; ?>', <?php echo $film['codeFilm']; ?>)">
                            Regarder
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Modale de lecture pour ce film -->
            <div id="player-modal-<?php echo $film['codeFilm']; ?>" class="fixed inset-0 bg-black bg-opacity-80 hidden z-50 flex items-center justify-center">
                <div class="bg-gray-900 p-2 rounded-lg shadow-lg max-w-4xl w-full max-h-full relative">
                    <button class="absolute top-2 right-2 text-white hover:text-red-500 text-2xl font-bold z-10"
                            onclick="closePlayerModal('player-modal-<?php echo $film['codeFilm']; ?>')">
                        &times;
                    </button>
                    
                    <div class="aspect-video w-full relative" id="player-container-<?php echo $film['codeFilm']; ?>">
                        <!-- Le lecteur vidéo sera injecté ici via JavaScript -->
                        <div class="flex items-center justify-center h-full text-white text-xl">
                            <i class="fas fa-spinner fa-spin mr-2"></i> Chargement...
                        </div>
                    </div>
                    
                    <div class="p-4 text-white">
                        <h2 class="text-xl font-bold"><?php echo htmlspecialchars($film['titre']); ?></h2>
                        <p class="text-gray-300 mt-2"><?php echo htmlspecialchars($film['description'] ?? 'Aucune description disponible.'); ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php else: ?>
            <p>Aucun film trouvé.</p>
        <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 mt-12">
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
                        <a href="#" class="text-gray-400 hover:text-white">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="mt-8 pt-8 border-t border-gray-700 text-center text-gray-400">
                <p>Fletnix - Eric Fourmaux - RAC Cégep Limoilou</p>
            </div>
        </div>
    </footer>

    <script>
        // Animation du menu au scroll
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('nav');
            if (window.scrollY > 0) {
                nav.classList.add('bg-opacity-95');
            } else {
                nav.classList.remove('bg-opacity-95');
            }
        });

        // Gestion de la modale
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
            document.getElementById(id).classList.add('flex');
            document.body.classList.add('overflow-hidden'); // Supprime la barre de scroll
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
            document.getElementById(id).classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        // Remplacement de l'image par défaut si l'image n'existe pas
        let img = document.querySelectorAll('img');
        img.forEach((img) => {
            img.addEventListener('error', function() {
                this.src = 'images/films/placeholder.jpg';

                // Trouver le bouton "Regarder" associé à cette image
                // Et le désactiver
                let filmCard = this.closest('.relative.group');
                if (filmCard) {
                    let watchButton = filmCard.querySelector('button');
                    if (watchButton) {
                        watchButton.disabled = true;
                        watchButton.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                }
            });
        });

        // Gestion des modales de lecture vidéo
        function openPlayerModal(modalId, filmId) {
        // Afficher la modale
        document.getElementById(modalId).classList.remove('hidden');
        document.getElementById(modalId).classList.add('flex');
        document.body.classList.add('overflow-hidden'); // Désactiver le scroll
        
        // Injecter le lecteur vidéo et audio
        const playerContainer = document.getElementById(`player-container-${filmId}`);
        
        playerContainer.innerHTML = `
            <video
                id="film-player-${filmId}"
                class="w-full h-full"
                controls
                autoplay>
                <source src="videos/${filmId}.mp4" type="video/mp4">
                Votre navigateur ne supporte pas la lecture vidéo.
            </video>
            <audio id="film-audio-${filmId}" src="videos/${filmId}.mp3" autoplay>
                Votre navigateur ne supporte pas la lecture audio.
            </audio>
        `;

        // Synchroniser l'audio et la vidéo
        const videoElement = document.getElementById(`film-player-${filmId}`);
        const audioElement = document.getElementById(`film-audio-${filmId}`);
        
        // Synchroniser la lecture
        videoElement.addEventListener('play', function() {
            audioElement.play();
            audioElement.currentTime = videoElement.currentTime;
        });
        
        videoElement.addEventListener('pause', function() {
            audioElement.pause();
        });
        
        videoElement.addEventListener('seeked', function() {
            audioElement.currentTime = videoElement.currentTime;
        });
        
        // Ajuster le volume de la vidéo à 0 pour éviter le double son
        videoElement.volume = 0;
    }

        function closePlayerModal(modalId) {
            // Récupérer l'ID du film depuis l'ID de la modale
            const filmId = modalId.split('-').pop();
            const playerContainer = document.getElementById(`player-container-${filmId}`);
            
            playerContainer.innerHTML = `
                <div class="flex items-center justify-center h-full text-white text-xl">
                    <i class="fas fa-spinner fa-spin mr-2"></i> Chargement...
                </div>
            `;
            
            // Cacher la modale
            document.getElementById(modalId).classList.add('hidden');
            document.getElementById(modalId).classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }
    </script>
    
    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</body>
</html>