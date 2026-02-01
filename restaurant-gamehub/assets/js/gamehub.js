(function () {
    const QUESTIONS = [
        {
            q: 'Quel est votre plat préféré ?',
            a: ['Pizza', 'Burger', 'Salade'],
        },
        {
            q: 'Quel moment vous préférez ?',
            a: ['Midi', 'Soir', 'Week-end'],
        },
        {
            q: 'Vous aimez les surprises ?',
            a: ['Oui', 'Un peu', 'Pas trop'],
        },
    ];

    function $(root, selector) {
        return root.querySelector(selector);
    }

    function $all(root, selector) {
        return Array.from(root.querySelectorAll(selector));
    }

    function loadGoogleFont(fontName) {
        if (!fontName || fontName.toLowerCase() === 'system') {
            return;
        }
        const existing = document.querySelector('link[data-gamehub-font]');
        if (existing && existing.dataset.font === fontName) {
            return;
        }
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(fontName)}:wght@400;600;700&display=swap`;
        link.dataset.gamehubFont = 'true';
        link.dataset.font = fontName;
        document.head.appendChild(link);
    }

    function setTheme(root, theme) {
        root.style.setProperty('--gh-primary', theme.primary);
        root.style.setProperty('--gh-secondary', theme.secondary);
        root.style.setProperty('--gh-accent', theme.accent);
        if (theme.font && theme.font.toLowerCase() !== 'system') {
            root.style.setProperty('--gh-font', `'${theme.font}', system-ui, sans-serif`);
        }
    }

    function setStatus(root, message, type) {
        const status = $('[data-status]', root);
        status.textContent = message;
        status.dataset.status = type || 'info';
    }

    function setLoading(root, isLoading) {
        root.dataset.loading = isLoading ? 'true' : 'false';
    }

    async function hashValue(value) {
        if (!window.crypto || !window.crypto.subtle) {
            return value;
        }
        const encoder = new TextEncoder();
        const data = encoder.encode(value);
        const digest = await window.crypto.subtle.digest('SHA-256', data);
        return Array.from(new Uint8Array(digest))
            .map((b) => b.toString(16).padStart(2, '0'))
            .join('');
    }

    async function getDeviceHash() {
        const stored = localStorage.getItem('gamehub_device');
        if (stored) {
            return stored;
        }
        const fingerprint = [
            navigator.userAgent,
            navigator.language,
            screen.width,
            screen.height,
            Intl.DateTimeFormat().resolvedOptions().timeZone,
        ].join('|');
        const hashed = await hashValue(fingerprint + Date.now());
        localStorage.setItem('gamehub_device', hashed);
        return hashed;
    }

    function drawWheel(canvas, rotation) {
        const ctx = canvas.getContext('2d');
        const size = canvas.width;
        const center = size / 2;
        const segments = 6;
        const colors = ['#ff6a3d', '#ffd166', '#06d6a0', '#118ab2', '#ef476f', '#8338ec'];
        ctx.clearRect(0, 0, size, size);
        for (let i = 0; i < segments; i += 1) {
            const start = (i / segments) * Math.PI * 2 + rotation;
            const end = ((i + 1) / segments) * Math.PI * 2 + rotation;
            ctx.beginPath();
            ctx.moveTo(center, center);
            ctx.arc(center, center, center - 6, start, end);
            ctx.closePath();
            ctx.fillStyle = colors[i % colors.length];
            ctx.fill();
            ctx.save();
            ctx.translate(center, center);
            ctx.rotate(start + (end - start) / 2);
            ctx.fillStyle = '#fff';
            ctx.font = 'bold 14px sans-serif';
            ctx.textAlign = 'right';
            ctx.fillText('•', center - 20, 5);
            ctx.restore();
        }
        ctx.beginPath();
        ctx.arc(center, center, 18, 0, Math.PI * 2);
        ctx.fillStyle = '#111';
        ctx.fill();
    }

    function animateWheel(canvas, targetRotation, duration) {
        return new Promise((resolve) => {
            const start = performance.now();
            const initialRotation = 0;
            function step(now) {
                const progress = Math.min((now - start) / duration, 1);
                const ease = 1 - Math.pow(1 - progress, 3);
                const rotation = initialRotation + ease * targetRotation;
                drawWheel(canvas, rotation);
                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    resolve();
                }
            }
            requestAnimationFrame(step);
        });
    }

    function getGameResult(data, payload) {
        return {
            result: payload.result || 'lose',
            label: payload.label || data.i18n.result_default,
            claimCode: payload.claim_code || null,
            claimToken: payload.claim_token || null,
            expiresAt: payload.expires_at || null,
        };
    }

    function buildBonusList(root, actions) {
        const list = $('[data-bonus-list]', root);
        list.innerHTML = '';
        const items = [];
        if (actions.instagram) items.push({ label: 'Suivre sur Instagram', url: actions.instagram });
        if (actions.tiktok) items.push({ label: 'Suivre sur TikTok', url: actions.tiktok });
        if (actions.youtube) items.push({ label: 'S’abonner YouTube', url: actions.youtube });
        if (actions.newsletter) items.push({ label: 'S’inscrire à la newsletter', action: 'newsletter' });
        if (actions.whatsapp) items.push({ label: 'Opt-in WhatsApp', action: 'whatsapp' });
        if (actions.feedback) items.push({ label: 'Laisser un feedback', action: 'feedback' });
        if (actions.referral) items.push({ label: 'Parrainer un ami', url: actions.referral });
        if (actions.google_review) items.push({ label: 'Laisser un avis Google', url: actions.google_review });

        items.forEach((item) => {
            const li = document.createElement('li');
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'gamehub__bonus-item';
            btn.textContent = item.label;
            if (item.url) {
                btn.addEventListener('click', () => window.open(item.url, '_blank', 'noopener'));
            }
            li.appendChild(btn);
            list.appendChild(li);
        });
    }

    function renderClaim(root, result, qrServiceUrl) {
        const claimText = $('.gamehub__claim', root);
        const claimBox = $('.gamehub__claim-box', root);
        const claimCode = $('[data-claim-code]', root);
        const qr = $('.gamehub__claim-qr', root);
        if (!result.claimCode) {
            claimText.textContent = 'Pas de gain cette fois.';
            claimBox.hidden = true;
            return;
        }
        claimText.textContent = `Valable jusqu’au ${result.expiresAt}`;
        claimCode.textContent = result.claimCode;
        claimBox.hidden = false;
        qr.alt = 'QR de retrait';
        if (qrServiceUrl) {
            qr.src = qrServiceUrl.replace('{data}', encodeURIComponent(result.claimCode));
        }
    }

    function showResult(root, result, qrServiceUrl) {
        const resultBox = $('.gamehub__result', root);
        const label = $('.gamehub__result-label', root);
        resultBox.hidden = false;
        label.textContent = result.label;
        renderClaim(root, result, qrServiceUrl);
        root.classList.add('gamehub--show-result');
        if (result.result === 'win') {
            root.classList.add('gamehub--win');
            launchConfetti(root);
        }
    }

    function launchConfetti(root) {
        const canvas = document.createElement('canvas');
        canvas.className = 'gamehub__confetti';
        root.appendChild(canvas);
        const ctx = canvas.getContext('2d');
        const pieces = Array.from({ length: 80 }).map(() => ({
            x: Math.random() * root.offsetWidth,
            y: -20,
            r: Math.random() * 6 + 4,
            s: Math.random() * 2 + 1,
            c: `hsl(${Math.random() * 360},90%,60%)`,
        }));
        function resize() {
            canvas.width = root.offsetWidth;
            canvas.height = root.offsetHeight;
        }
        resize();
        const start = performance.now();
        function step(now) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            pieces.forEach((p) => {
                p.y += p.s * 2;
                ctx.fillStyle = p.c;
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fill();
            });
            if (now - start < 1800) {
                requestAnimationFrame(step);
            } else {
                canvas.remove();
            }
        }
        requestAnimationFrame(step);
    }

    function buildLeadForm(root, data) {
        const modal = document.createElement('div');
        modal.className = 'gamehub__lead-modal';
        modal.innerHTML = `
            <div class="gamehub__lead-card" role="dialog" aria-modal="true">
                <button class="gamehub__lead-close" type="button" aria-label="Fermer">✕</button>
                <h3>Recevez votre code</h3>
                <form class="gamehub__lead-form">
                    <label>Prénom<input name="first_name" required></label>
                    <label>Nom<input name="last_name" required></label>
                    <label>Email<input name="email" type="email"></label>
                    <label>Téléphone / WhatsApp<input name="phone"></label>
                    <p class="gamehub__consent">${data.consent_text}</p>
                    <label class="gamehub__checkbox"><input type="checkbox" name="newsletter"> ${data.marketing_email_text}</label>
                    <label class="gamehub__checkbox"><input type="checkbox" name="whatsapp"> ${data.marketing_sms_text}</label>
                    <button type="submit">Envoyer</button>
                </form>
            </div>`;
        root.appendChild(modal);

        const close = () => modal.remove();
        $('.gamehub__lead-close', modal).addEventListener('click', close);
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                close();
            }
        });

        $('.gamehub__lead-form', modal).addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.target;
            const email = form.email.value.trim();
            const phone = form.phone.value.trim();
            const payload = {
                first_name: form.first_name.value.trim(),
                last_name: form.last_name.value.trim(),
                email: email,
                phone: phone,
                game: data.type,
                consents: {
                    newsletter: form.newsletter.checked,
                    whatsapp: form.whatsapp.checked,
                },
            };
            if (!payload.first_name || !payload.last_name || (!email && !phone)) {
                alert('Merci de renseigner les champs requis.');
                return;
            }
            const keySource = email || phone;
            const userKey = keySource ? await hashValue(keySource.toLowerCase()) : '';
            if (userKey) {
                localStorage.setItem('gamehub_user_key', userKey);
            }
            fetch(data.lead_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': data.nonce,
                },
                body: JSON.stringify(payload),
            }).then(() => {
                close();
                alert('Merci ! Votre code est disponible.');
            });
        });
    }

    async function initGameHub(container) {
        const dataEl = $('.gamehub-data', container);
        if (!dataEl) {
            return;
        }
        const data = JSON.parse(dataEl.textContent);
        const hub = $('[data-hub]', container);
        const screen = $('[data-screen]', container);
        const stage = $('[data-stage]', container);
        const playButton = $('.gamehub__play', container);
        const resetButton = $('.gamehub__reset', container);
        const leadButton = $('.gamehub__lead', container);
        const bonusButton = $('.gamehub__bonus', container);
        const modal = $('.gamehub__modal', container);
        const toggle = $('.gamehub__toggle', container);
        const deviceHash = await getDeviceHash();

        loadGoogleFont(data.theme.font);
        setTheme(container, data.theme);

        const title = container.querySelector('.gamehub__title');
        if (title) title.textContent = data.i18n.choose_game;
        const cta = container.querySelector('[data-start-hub]');
        if (cta) cta.textContent = data.i18n.play;
        playButton.textContent = data.i18n.launch;
        leadButton.textContent = data.i18n.get_code;
        bonusButton.textContent = data.i18n.bonus;

        const storedMode = localStorage.getItem('gamehub-dark');
        const prefersDark = storedMode ? storedMode === 'true' : data.theme.dark_mode;
        container.dataset.dark = prefersDark ? 'true' : 'false';

        toggle.addEventListener('click', () => {
            const next = container.dataset.dark !== 'true';
            container.dataset.dark = next ? 'true' : 'false';
            localStorage.setItem('gamehub-dark', next ? 'true' : 'false');
        });

        if (data.type !== 'hub') {
            hub.hidden = true;
            screen.hidden = false;
        }

        let currentGame = data.type === 'hub' ? 'roulette' : data.type;
        let pendingResult = null;

        function reset() {
            stage.innerHTML = '';
            $('.gamehub__result', container).hidden = true;
            container.classList.remove('gamehub--show-result', 'gamehub--win');
            setStatus(container, data.i18n.ready, 'info');
            resetButton.hidden = true;
            pendingResult = null;
            renderStage(currentGame);
        }

        function handlePlay() {
            const userKey = localStorage.getItem('gamehub_user_key') || '';
            setLoading(container, true);
            setStatus(container, 'Calcul du résultat...', 'loading');
            return fetch(data.rest_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': data.nonce,
                },
                body: JSON.stringify({
                    type: currentGame,
                    qr_id: data.qr_id,
                    user_key: userKey,
                    device_hash: deviceHash,
                }),
            })
                .then((response) => response.json())
                .then((payload) => {
                    pendingResult = getGameResult(data, payload);
                    return pendingResult;
                })
                .catch(() => {
                    setStatus(container, data.i18n.error, 'error');
                })
                .finally(() => setLoading(container, false));
        }

        function renderStage(game) {
            stage.innerHTML = '';
            if (game === 'roulette') {
                const wrapper = document.createElement('div');
                wrapper.className = 'gamehub__roulette';
                const canvas = document.createElement('canvas');
                canvas.width = 280;
                canvas.height = 280;
                wrapper.appendChild(canvas);
                const pointer = document.createElement('div');
                pointer.className = 'gamehub__roulette-pointer';
                wrapper.appendChild(pointer);
                stage.appendChild(wrapper);
                drawWheel(canvas, 0);
                playButton.onclick = () => {
                    handlePlay().then((result) => {
                        if (!result) return;
                        const segment = result.result === 'win' ? 0 : result.result === 'consolation' ? 1 : 3;
                        const targetRotation = Math.PI * 6 + (Math.PI * 2 * (segment / 6));
                        animateWheel(canvas, targetRotation, 1400).then(() => {
                            showResult(container, result, data.qr_service_url);
                            resetButton.hidden = false;
                        });
                    });
                };
            }
            if (game === 'scratch') {
                const wrapper = document.createElement('div');
                wrapper.className = 'gamehub__scratch';
                const reveal = document.createElement('div');
                reveal.className = 'gamehub__scratch-reveal';
                reveal.textContent = 'Votre gain apparaît ici';
                const canvas = document.createElement('canvas');
                canvas.width = 300;
                canvas.height = 180;
                wrapper.append(reveal, canvas);
                stage.appendChild(wrapper);
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#c9c9c9';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                let isDrawing = false;
                function scratch(x, y) {
                    ctx.globalCompositeOperation = 'destination-out';
                    ctx.beginPath();
                    ctx.arc(x, y, 18, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.globalCompositeOperation = 'source-over';
                }
                function handleMove(event) {
                    if (!isDrawing) return;
                    const rect = canvas.getBoundingClientRect();
                    scratch(event.clientX - rect.left, event.clientY - rect.top);
                }
                canvas.addEventListener('pointerdown', (event) => {
                    isDrawing = true;
                    handleMove(event);
                });
                canvas.addEventListener('pointermove', handleMove);
                window.addEventListener('pointerup', () => {
                    isDrawing = false;
                });
                playButton.onclick = () => {
                    handlePlay().then((result) => {
                        if (!result) return;
                        reveal.textContent = result.label;
                        showResult(container, result, data.qr_service_url);
                        resetButton.hidden = false;
                    });
                };
            }
            if (game === 'quiz') {
                const wrapper = document.createElement('div');
                wrapper.className = 'gamehub__quiz';
                stage.appendChild(wrapper);
                let index = 0;
                function renderQuestion() {
                    wrapper.innerHTML = '';
                    const question = document.createElement('div');
                    question.className = 'gamehub__quiz-question';
                    question.textContent = QUESTIONS[index].q;
                    wrapper.appendChild(question);
                    const answers = document.createElement('div');
                    answers.className = 'gamehub__quiz-answers';
                    QUESTIONS[index].a.forEach((answer) => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.textContent = answer;
                        btn.className = 'gamehub__quiz-answer';
                        btn.addEventListener('click', () => {
                            index += 1;
                            if (index >= QUESTIONS.length) {
                                showResult(container, pendingResult, data.qr_service_url);
                                resetButton.hidden = false;
                            } else {
                                renderQuestion();
                            }
                        });
                        answers.appendChild(btn);
                    });
                    wrapper.appendChild(answers);
                }
                playButton.onclick = () => {
                    handlePlay().then((result) => {
                        if (!result) return;
                        pendingResult = result;
                        renderQuestion();
                    });
                };
            }
            if (game === 'pickbox') {
                const grid = document.createElement('div');
                grid.className = 'gamehub__pickbox';
                stage.appendChild(grid);
                playButton.onclick = () => {
                    handlePlay().then((result) => {
                        if (!result) return;
                        pendingResult = result;
                        grid.innerHTML = '';
                        for (let i = 0; i < 9; i += 1) {
                            const card = document.createElement('button');
                            card.type = 'button';
                            card.className = 'gamehub__pickbox-card';
                            card.textContent = '🎁';
                            card.addEventListener('click', () => {
                                card.classList.add('is-open');
                                card.textContent = result.result === 'win' ? '🏆' : '✨';
                                showResult(container, result, data.qr_service_url);
                                resetButton.hidden = false;
                            }, { once: true });
                            grid.appendChild(card);
                        }
                    });
                };
            }
            if (game === 'memory') {
                const grid = document.createElement('div');
                grid.className = 'gamehub__memory';
                stage.appendChild(grid);
                playButton.onclick = () => {
                    handlePlay().then((result) => {
                        if (!result) return;
                        pendingResult = result;
                        const symbols = ['🍕', '🍔', '🍟', '🍣', '🍩', '🥗'];
                        const cards = [...symbols, ...symbols].sort(() => Math.random() - 0.5);
                        grid.innerHTML = '';
                        let first = null;
                        let lock = false;
                        let matched = 0;
                        cards.forEach((symbol) => {
                            const card = document.createElement('button');
                            card.type = 'button';
                            card.className = 'gamehub__memory-card';
                            card.innerHTML = `<span class="front">❓</span><span class="back">${symbol}</span>`;
                            card.addEventListener('click', () => {
                                if (lock || card.classList.contains('is-flipped')) return;
                                card.classList.add('is-flipped');
                                if (!first) {
                                    first = card;
                                    return;
                                }
                                lock = true;
                                const match = first.querySelector('.back').textContent === card.querySelector('.back').textContent;
                                if (match) {
                                    matched += 1;
                                    first = null;
                                    lock = false;
                                    if (matched === symbols.length) {
                                        showResult(container, result, data.qr_service_url);
                                        resetButton.hidden = false;
                                    }
                                } else {
                                    setTimeout(() => {
                                        first.classList.remove('is-flipped');
                                        card.classList.remove('is-flipped');
                                        first = null;
                                        lock = false;
                                    }, 600);
                                }
                            });
                            grid.appendChild(card);
                        });
                    });
                };
            }
            if (game === 'stoptimer') {
                const wrapper = document.createElement('div');
                wrapper.className = 'gamehub__stoptimer';
                const display = document.createElement('div');
                display.className = 'gamehub__timer';
                display.textContent = '00.00';
                const stop = document.createElement('button');
                stop.type = 'button';
                stop.textContent = 'Stop';
                stop.className = 'gamehub__stop';
                wrapper.append(display, stop);
                stage.appendChild(wrapper);
                playButton.onclick = () => {
                    let start = performance.now();
                    let raf;
                    function tick(now) {
                        const elapsed = (now - start) / 1000;
                        display.textContent = elapsed.toFixed(2);
                        raf = requestAnimationFrame(tick);
                    }
                    raf = requestAnimationFrame(tick);
                    stop.onclick = () => {
                        cancelAnimationFrame(raf);
                        handlePlay().then((result) => {
                            if (!result) return;
                            showResult(container, result, data.qr_service_url);
                            resetButton.hidden = false;
                        });
                    };
                };
            }
        }

        $all(container, '[data-select-game]').forEach((button) => {
            button.addEventListener('click', () => {
                currentGame = button.dataset.selectGame;
                hub.dataset.selected = currentGame;
                setStatus(container, `Mode ${currentGame}`, 'info');
            });
        });

        $('[data-start-hub]', container).addEventListener('click', () => {
            hub.hidden = true;
            screen.hidden = false;
            reset();
        });

        bonusButton.addEventListener('click', () => {
            modal.setAttribute('aria-hidden', 'false');
            modal.classList.add('is-open');
        });
        $('.gamehub__modal-close', modal).addEventListener('click', () => {
            modal.setAttribute('aria-hidden', 'true');
            modal.classList.remove('is-open');
        });
        $('.gamehub__skip', modal).addEventListener('click', () => {
            modal.setAttribute('aria-hidden', 'true');
            modal.classList.remove('is-open');
        });

        buildBonusList(container, data.actions || {});

        leadButton.addEventListener('click', () => buildLeadForm(container, data));
        resetButton.addEventListener('click', reset);

        if (data.type !== 'hub') {
            reset();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.gamehub').forEach((el) => {
            initGameHub(el);
        });
    });
})();
