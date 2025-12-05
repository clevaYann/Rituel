// --- LOGIQUE CLIENT ---
let userSequence = [];
const scene = document.getElementById('scene');
const display = document.getElementById('sequence-display');
const formActionInput = document.getElementById('formAction');
const submitBtn = document.getElementById('submitBtn');
const instructionText = document.getElementById('instruction-text');

// Détection de l'erreur de login au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    // On vérifie si le body a l'attribut data-login-error="true"
    if (document.body.dataset.loginError === 'true') {
        const artifacts = document.querySelectorAll('.artifact');
        artifacts.forEach(artifact => {
            artifact.classList.add('shake-error');
            // Retire la classe après l'animation pour pouvoir la rejouer
            artifact.addEventListener('animationend', () => {
                artifact.classList.remove('shake-error');
            }, { once: true });
        });
    }
});

// Gestion des onglets
function switchTab(mode) {
    // UI Update
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + mode).classList.add('active');
    
    // Logic Update
    formActionInput.value = mode;
    if(mode === 'register') {
        submitBtn.innerText = "Enregistrer le Rituel";
        instructionText.innerText = "Inventez une séquence (cliquez sur les symboles) :";
    } else {
        submitBtn.innerText = "Entrer";
        instructionText.innerText = "Reproduisez votre séquence secrète :";
    }
    
    // Reset
    resetRitual();
}

// Interaction Scène
if(scene) {
    document.querySelectorAll('.artifact').forEach(item => {
        item.addEventListener('click', (e) => {
            const id = item.getAttribute('data-id');
            userSequence.push(id);
            
            // Ripple Effect
            createRipple(e.clientX, e.clientY);
            
            // Feedback visuel (points)
            updateDisplay();
        });
    });
}

function createRipple(x, y) {
    const ripple = document.createElement('div');
    ripple.classList.add('click-marker');
    const rect = scene.getBoundingClientRect();
    // Position relative au container de la scène pour éviter les décalages au scroll
    ripple.style.left = (x - rect.left - 10) + 'px';
    ripple.style.top = (y - rect.top - 10) + 'px';
    scene.appendChild(ripple);
    setTimeout(() => ripple.remove(), 600);
}

function updateDisplay() {
    // Affiche un symbole mystique pour chaque étape
    display.innerHTML = userSequence.map(() => '✨').join(' ');
}

function resetRitual() {
    userSequence = [];
    updateDisplay();
}

function submitForm() {
    if (userSequence.length === 0) {
        alert("Vous devez toucher au moins un symbole !");
        return;
    }
    document.getElementById('sequenceInput').value = JSON.stringify(userSequence);
    document.getElementById('authForm').submit();
}