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


        function getNombreEmpruntsParFilm($conn) {
            $query = "SELECT 
                        f.codeFilm, 
                        f.titre, 
                        COUNT(c.tempsVisionnement) as nombreEmprunts
                    FROM FILM f
                    LEFT JOIN COMMANDE c ON f.codeFilm = c.codeFilm
                    GROUP BY f.codeFilm, f.titre
                    ORDER BY nombreEmprunts DESC";

            if ($conn->connect_error) {
                die("Échec de la connexion : " . $conn->connect_error);
            }

            $result = $conn->query($query);
            
            if ($result === false) {
                die("Erreur SQL : " . $conn->error);
            }

            if ($result instanceof mysqli_result) {
                $data = $result->fetch_all(MYSQLI_ASSOC);
                $result->free();
            } else {
                $data = $conn->affected_rows;
            }

            // $conn->close();
            return $data;
        }

        function getStatistiquesVisionnementParFilm($conn) {
            $query = "SELECT 
                        f.codeFilm, 
                        f.titre, 
                        COUNT(c.codeCommande) as nombreEmprunts,
                        SUM(c.tempsVisionnement) as tempsVisionnementTotal,
                        AVG(c.tempsVisionnement) as tempsVisionnementMoyen
                      FROM FILM f
                      LEFT JOIN COMMANDE c ON f.codeFilm = c.codeFilm
                      GROUP BY f.codeFilm, f.titre
                      ORDER BY tempsVisionnementTotal DESC";
            
            if ($conn->connect_error) {
                die("Échec de la connexion : " . $conn->connect_error);
            }

            $result = $conn->query($query);
            
            if ($result === false) {
                die("Erreur SQL : " . $conn->error);
            }

            if ($result instanceof mysqli_result) {
                $data = $result->fetch_all(MYSQLI_ASSOC);
                $result->free();
            } else {
                $data = $conn->affected_rows;
            }

            $conn->close();
            return $data;
        }

        $emprunts = getNombreEmpruntsParFilm($conn);
        $statistiques = getStatistiquesVisionnementParFilm($conn);

        // Calculer le temps total de visionnement
        $tempsVisionnementTotal = 0;
        foreach($statistiques as $stat) {
            $tempsVisionnementTotal += $stat['tempsVisionnementTotal'];
        }

        // Formater le temps en heures et minutes
        function formaterTemps($secondes) {
            $heures = floor($secondes / 3600);
            $minutes = floor(($secondes % 3600) / 60);
            
            return $heures . 'h ' . $minutes . 'm';
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

    <br>
    <br>
    <br>

    <!-- Films les plus empruntés -->
    <div class="container mx-auto">
        <h1 class="text-3xl font-bold mb-6">Statistiques des emprunts de films</h1>

        <div class="bg-gray-200 p-6 rounded shadow-md text-black">
            <h2 class="text-2xl font-bold mb-4">Nombre d'emprunts par film</h2>
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="border p-2">Titre du Film</th>
                        <th class="border p-2">Nombre d'Emprunts</th>
                        <th class="border p-2">Pourcentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Calculer le nombre total d'emprunts
                    $totalEmprunts = 0;
                    foreach($emprunts as $emprunt) {
                        $totalEmprunts += $emprunt['nombreEmprunts'];
                    }
                    
                    // Afficher les emprunts
                    foreach($emprunts as $emprunt): 
                        $pourcentage = $totalEmprunts > 0 ? round(($emprunt['nombreEmprunts'] / $totalEmprunts) * 100, 2) : 0;
                    ?>
                    <tr>
                        <td class="border p-2"><?= htmlspecialchars($emprunt['titre']) ?></td>
                        <td class="border p-2 text-center"><?= $emprunt['nombreEmprunts'] ?></td>
                        <td class="border p-2">
                            <div class="relative pt-1">
                                <div class="flex mb-2 items-center justify-between">
                                    <div>
                                        <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full bg-blue-200 text-blue-800">
                                            <?= $pourcentage ?>%
                                        </span>
                                    </div>
                                </div>
                                <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-blue-200">
                                    <div style="width:<?= $pourcentage ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-gray-100">
                        <td class="border p-2 font-bold">Total</td>
                        <td class="border p-2 text-center font-bold"><?= $totalEmprunts ?></td>
                        <td class="border p-2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <br>

        <!-- Graphique des emprunts les plus fréquents -->
        <div class="bg-gray-200 p-6 rounded shadow-md text-black">
            <h2 class="text-2xl font-bold mb-4">Top 5 des films les plus empruntés</h2>
            
            <?php
            // Récupérer les 5 films les plus empruntés
            $topFilms = array_slice($emprunts, 0, 5);
            ?>
            
            <div class="h-64">
                <?php foreach($topFilms as $film): 
                    $pourcentage = $totalEmprunts > 0 ? round(($film['nombreEmprunts'] / $totalEmprunts) * 100, 2) : 0;
                    $hauteur = $pourcentage * 2; // Pour que le graphique soit visible
                ?>
                <div class="inline-block align-bottom mx-2 text-center" style="height: 100%;">
                    <div class="bg-blue-500 inline-block" style="height: <?= $hauteur ?>%; width: 40px;"></div>
                    <div class="text-xs mt-2 w-20 overflow-hidden text-ellipsis">
                        <?= htmlspecialchars($film['titre']) ?>
                    </div>
                    <div class="text-sm font-bold">
                        <?= $film['nombreEmprunts'] ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <br>

        <!-- Statistiques supplémentaires -->
        <div class="bg-gray-200 p-6 rounded shadow-md text-black">
            <h2 class="text-2xl font-bold mb-4">Statistiques générales</h2>
            
            <?php
            // Calculer le nombre de films jamais empruntés
            $filmsNonEmpruntes = 0;
            foreach($emprunts as $emprunt) {
                if ($emprunt['nombreEmprunts'] == 0) {
                    $filmsNonEmpruntes++;
                }
            }
            
            // Calculer la moyenne d'emprunts par film
            $moyenneEmprunts = count($emprunts) > 0 ? $totalEmprunts / count($emprunts) : 0;
            ?>
            
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-gray-100 p-4 rounded">
                    <p class="text-lg font-bold"><?= $totalEmprunts ?></p>
                    <p class="text-sm">Total des emprunts</p>
                </div>
                <div class="bg-gray-100 p-4 rounded">
                    <p class="text-lg font-bold"><?= $filmsNonEmpruntes ?></p>
                    <p class="text-sm">Films jamais empruntés</p>
                </div>
                <div class="bg-gray-100 p-4 rounded">
                    <p class="text-lg font-bold"><?= number_format($moyenneEmprunts, 2) ?></p>
                    <p class="text-sm">Moyenne d'emprunts par film</p>
                </div>
            </div>
        </div>

        <br>

        <div class="bg-gray-200 p-6 rounded shadow-md text-black">
        <h2 class="text-2xl font-bold mb-4">Statistiques de visionnement</h2>
        
        <?php
        // Calculer le nombre de films jamais visionnés
        $filmsNonVisionnes = 0;
        foreach($statistiques as $stat) {
            if ($stat['tempsVisionnementTotal'] == 0) {
                $filmsNonVisionnes++;
            }
        }
        
        // Calculer le temps moyen de visionnement par film
        $nbFilmsVisionnes = count($statistiques) - $filmsNonVisionnes;
        $tempsMoyenParFilm = $nbFilmsVisionnes > 0 ? 
            $tempsVisionnementTotal / $nbFilmsVisionnes : 0;
        
        // Calculer le film le plus populaire (temps de visionnement le plus élevé)
        $filmPlusVisionne = !empty($statistiques) ? $statistiques[0] : null;
        ?>
        
        <div class="grid grid-cols-3 gap-4">
            <div class="bg-gray-100 p-4 rounded">
                <p class="text-lg font-bold"><?= formaterTemps($tempsVisionnementTotal) ?></p>
                <p class="text-sm">Temps total de visionnement</p>
            </div>
            <div class="bg-gray-100 p-4 rounded">
                <p class="text-lg font-bold"><?= $filmsNonVisionnes ?></p>
                <p class="text-sm">Films jamais visionnés</p>
            </div>
            <div class="bg-gray-100 p-4 rounded">
                <p class="text-lg font-bold"><?= formaterTemps($tempsMoyenParFilm) ?></p>
                <p class="text-sm">Temps moyen par film</p>
            </div>
        </div>
        
        <?php if ($filmPlusVisionne && $filmPlusVisionne['tempsVisionnementTotal'] > 0): ?>
        <div class="mt-6 bg-green-50 p-4 rounded border border-green-200">
            <h3 class="font-bold text-green-800">Film le plus populaire</h3>
            <p class="mt-2">
                <span class="font-bold"><?= htmlspecialchars($filmPlusVisionne['titre']) ?></span> a été 
                visionné pendant <span class="font-bold"><?= formaterTemps($filmPlusVisionne['tempsVisionnementTotal']) ?></span> 
                au total, représentant <span class="font-bold"><?= round(($filmPlusVisionne['tempsVisionnementTotal'] / $tempsVisionnementTotal) * 100, 2) ?>%</span> 
                du temps total de visionnement.
            </p>
        </div>
        <?php endif; ?>
    </div>
    </div>

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
</body>
</html>