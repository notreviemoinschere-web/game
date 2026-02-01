(function () {
    const form = document.querySelector('form[action*="gamehub_validate_code"]');
    if (!form) {
        return;
    }
    form.addEventListener('submit', function (event) {
        if (navigator.onLine) {
            return;
        }
        event.preventDefault();
        const input = form.querySelector('input[name="claim_code"]');
        if (!input || !input.value) {
            return;
        }
        const pending = JSON.parse(localStorage.getItem('gamehub_offline_claims') || '[]');
        pending.push({ code: input.value, at: new Date().toISOString() });
        localStorage.setItem('gamehub_offline_claims', JSON.stringify(pending));
        alert('Connexion faible : code enregistré pour validation ultérieure.');
        input.value = '';
    });
})();
