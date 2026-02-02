(function () {
    function initGameHub(container) {
        const dataEl = container.querySelector('.gamehub-data');
        if (!dataEl) {
            return;
        }
        const data = JSON.parse(dataEl.textContent);
        const stage = container.querySelector('[data-stage]');
        const cards = container.querySelectorAll('[data-select-game]');
        const playButton = container.querySelector('.gamehub__play');
        const resultBox = container.querySelector('.gamehub__result');
        const resultLabel = container.querySelector('.gamehub__result-label');
        const claimEl = container.querySelector('.gamehub__claim');
        const leadButton = container.querySelector('.gamehub__lead');

        function ensureDeviceId() {
            let device = localStorage.getItem('gamehub_device');
            if (!device) {
                device = Math.random().toString(36).slice(2) + Date.now().toString(36);
                localStorage.setItem('gamehub_device', device);
            }
            return device;
        }

        function renderSimpleGame(type) {
            stage.innerHTML = '';
            const card = document.createElement('div');
            card.className = 'gamehub__card';
            card.textContent = type.toUpperCase();
            stage.appendChild(card);
        }

        let currentGame = data.type;
        renderSimpleGame(currentGame);

        cards.forEach((card) => {
            card.addEventListener('click', () => {
                currentGame = card.dataset.selectGame;
                renderSimpleGame(currentGame);
            });
        });

        playButton.addEventListener('click', function () {
            playButton.disabled = true;
            const deviceHash = ensureDeviceId();
            fetch(data.rest_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': data.nonce,
                },
                body: JSON.stringify({
                    type: currentGame,
                    qr_id: data.qr_id,
                    user_key: localStorage.getItem('gamehub_user_key') || '',
                    device_hash: deviceHash,
                }),
            })
                .then((response) => response.json())
                .then((payload) => {
                    resultBox.hidden = false;
                    resultLabel.textContent = payload.label || 'Merci !';
                    claimEl.textContent = payload.claim_code ? `Code: ${payload.claim_code}` : 'Pas de gain cette fois.';
                })
                .catch(() => {
                    resultBox.hidden = false;
                    resultLabel.textContent = 'Erreur, réessayez.';
                })
                .finally(() => {
                    playButton.disabled = false;
                });
        });

        leadButton.addEventListener('click', function () {
            const email = window.prompt('Email pour recevoir le code');
            if (!email) {
                return;
            }
            localStorage.setItem('gamehub_user_key', email);
            fetch(data.lead_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': data.nonce,
                },
                body: JSON.stringify({
                    first_name: 'Client',
                    last_name: 'GameHub',
                    email: email,
                    phone: '',
                    game: data.type,
                }),
            }).then(() => {
                alert('Merci !');
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.gamehub').forEach(initGameHub);
    });
})();
