<?php
session_start();

// Inclusion du fichier de configuration pour la base de données
require_once 'config.php';

$mapping = [
    'moon'  => 1, // Lune
    'tree'  => 2, // Arbre
    'fire'  => 3, // Feu
    'water' => 4, // Eau
    'key'   => 5, // Clé
    'skull' => 6  // Crâne
];

try {
    // Connexion à la base de données MySQL avec PDO
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// --- GESTION DES POSITIONS DYNAMIQUES ---
if (!isset($_SESSION['artifact_positions'])) {
    // Si aucune position n'est en session, on en génère de nouvelles.
    $_SESSION['artifact_positions'] = generate_artifact_positions();
}

function generate_artifact_positions() {
    $positions = [];
    $artifacts = ['moon', 'tree', 'fire', 'water', 'key', 'skull'];
    // Zones pour éviter les superpositions
    $zones = [
        ['top' => [10, 20], 'left' => [70, 85]], // moon
        ['top' => [60, 80], 'left' => [5, 20]],  // tree
        ['top' => [75, 85], 'left' => [25, 40]], // fire
        ['top' => [70, 85], 'left' => [60, 75]], // water
        ['top' => [35, 55], 'left' => [40, 55]], // key
        ['top' => [80, 90], 'left' => [5, 15]],  // skull
    ];

    foreach ($artifacts as $index => $name) {
        $top = rand($zones[$index]['top'][0], $zones[$index]['top'][1]);
        $left = rand($zones[$index]['left'][0], $zones[$index]['left'][1]);
        $positions[$name] = "top: {$top}%; left: {$left}%;";
    }
    return $positions;
}

$message = "";
$msg_type = ""; // 'success' ou 'error'
$is_logged_in = isset($_SESSION['user_id']);
$current_username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

// --- TRAITEMENT DU FORMULAIRE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Déconnexion
    if ($_POST['action'] === 'logout') {
        session_destroy();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    
    // Récupération de la séquence envoyée (ex: ["moon", "fire"])
    $raw_sequence = json_decode($_POST['sequence_data'], true);
    
    // Conversion en chiffres (ex: "13")
    $numeric_sequence = "";
    if (is_array($raw_sequence)) {
        foreach ($raw_sequence as $item) {
            if (isset($mapping[$item])) {
                $numeric_sequence .= $mapping[$item];
            }
        }
    }

    if (empty($username) || empty($numeric_sequence)) {
        $message = "Veuillez entrer un nom et accomplir le rituel.";
        $msg_type = "error";
    } else {
        
        if ($_POST['action'] === 'register') {
            // --- INSCRIPTION ---
            try {
                // Hachage de la séquence numérique (comme un mot de passe)
                $hash = password_hash($numeric_sequence, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (username, ritual_hash) VALUES (:username, :hash)");
                $stmt->execute([':username' => $username, ':hash' => $hash]);
                
                $message = "Compte créé pour $username ! Vous pouvez maintenant entrer.";
                $msg_type = "success";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Code erreur contrainte d'unicité
                    $message = "Ce nom d'aventurier est déjà pris.";
                } else {
                    $message = "Erreur lors de l'inscription : " . $e->getMessage(); // Affiche l'erreur exacte
                }
                $msg_type = "error";
            }

        } elseif ($_POST['action'] === 'login') {
            // --- CONNEXION ---
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($numeric_sequence, $user['ritual_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $is_logged_in = true;
                // Rafraichir pour afficher l'interface connecté
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $message = "❌ Le rituel est incorrect ou l'aventurier inconnu.";
                $msg_type = "error";
                // On signale l'erreur pour l'animation et on change les positions
                $_SESSION['login_error'] = true;
                $_SESSION['artifact_positions'] = generate_artifact_positions();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Défi Authentification - Ali Baba</title>
    <link rel="stylesheet" href="style.css">
</head>
<body <?php echo (isset($_SESSION['login_error']) && $_SESSION['login_error']) ? 'data-login-error="true"' : ''; ?>>

    <div class="main-wrapper">
        <?php if ($is_logged_in): ?>
            <!-- INTERFACE CONNECTÉ -->
            <div class="success-box">
                <h1 style="font-size: 3rem;">🔓</h1>
                <h1>Bienvenue, <?php echo htmlspecialchars($current_username); ?> !</h1>
                <p>Les secrets de la caverne sont désormais vôtres.</p>
                
                <div style="margin: 40px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                    <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 10px;">💎<br>Saphirs</div>
                    <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 10px;">🏺<br>Reliques</div>
                    <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 10px;">📜<br>Parchemins</div>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="btn">Refermer la caverne (Déconnexion)</button>
                </form>
            </div>

        <?php else: ?>
            <!-- INTERFACE NON CONNECTÉ -->
            <h1>Le Gardien du Seuil</h1>
            
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('login')" id="tab-login">S'identifier</button>
                <button class="tab-btn" onclick="switchTab('register')" id="tab-register">Créer un Rituel (Inscription)</button>
            </div>

            <form id="authForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="login">
                <input type="hidden" name="sequence_data" id="sequenceInput">

                <div class="input-group">
                    <input type="text" name="username" placeholder="Nom de l'aventurier" required autocomplete="off">
                </div>

                <p id="instruction-text">Reproduisez votre séquence secrète :</p>

                <div class="scene-container" id="scene">
                    <!-- Artefacts -->
                    <?php $positions = $_SESSION['artifact_positions']; ?>
                    <div id="moon" class="artifact" data-id="moon" title="Lune (1)" style="<?php echo $positions['moon']; ?>">🌙</div>
                    <div id="tree" class="artifact" data-id="tree" title="Arbre (2)" style="<?php echo $positions['tree']; ?>">🌳</div>
                    <div id="fire" class="artifact" data-id="fire" title="Feu (3)" style="<?php echo $positions['fire']; ?>">🔥</div>
                    <div id="water" class="artifact" data-id="water" title="Eau (4)" style="<?php echo $positions['water']; ?>">💧</div>
                    <div id="key" class="artifact" data-id="key" title="Clé (5)" style="<?php echo $positions['key']; ?>">🗝️</div>
                    <div id="skull" class="artifact" data-id="skull" title="Crâne (6)" style="<?php echo $positions['skull']; ?>">💀</div>
                </div>

                <div id="sequence-display"></div>

                <div class="controls">
                    <button type="button" class="btn" onclick="resetRitual()">Effacer</button>
                    <button type="button" class="btn btn-primary" onclick="submitForm()" id="submitBtn">Entrer</button>
                </div>
            </form>

            <?php if ($message): ?>
                <div class="message-box <?php echo ($msg_type === 'error') ? 'msg-error' : 'msg-success'; ?>">
                    <?php 
                        // On nettoie le flag d'erreur après l'avoir utilisé
                        unset($_SESSION['login_error']); 
                    ?>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <script src="script.js" defer></script>
</body>
</html>