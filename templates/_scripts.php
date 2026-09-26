<dialog class="dlg" id="confirmDlg">
    <form method="dialog">
        <div class="dlg-body">
            <h2 id="confirmDlgTitle">Are you sure?</h2>
            <p id="confirmDlgText"></p>
        </div>
        <div class="dlg-actions">
            <button class="btn" value="" autofocus>Cancel</button>
            <button class="btn btn-primary" id="confirmDlgOk" value="ok">Confirm</button>
        </div>
    </form>
</dialog>
<script>
/* Instant filter for any list page.

   <input data-filter=".js-row"> hides every .js-row whose text doesn't contain
   what was typed. Purely client-side: it filters the rows already on the page.
   Fires a "filtered" event on the input so pages can react (bulk QR does). */
(function () {
    document.querySelectorAll('input[data-filter]').forEach(function (box) {
        var sel = box.getAttribute('data-filter');
        var counter = box.parentNode.querySelector('.count');
        var noun = box.getAttribute('data-noun') || 'match';

        function apply() {
            var q = box.value.trim().toLowerCase();
            var shown = 0;
            document.querySelectorAll(sel).forEach(function (row) {
                var hit = q === '' || row.textContent.toLowerCase().indexOf(q) !== -1;
                row.classList.toggle('f-hide', !hit);
                if (hit) { shown++; }
            });
            if (counter) {
                counter.textContent = q === '' ? '' : shown + ' ' + noun + (shown === 1 ? '' : 'es');
                counter.classList.toggle('none', q !== '' && shown === 0);
            }
            box.dispatchEvent(new CustomEvent('filtered'));
        }

        box.addEventListener('input', apply);
        box.addEventListener('search', apply);
    });
})();

/* Any <form data-confirm="…"> asks first, in a real modal dialog.
   Optional: data-confirm-title, data-confirm-ok, data-confirm-danger. */
(function () {
    var dlg = document.getElementById('confirmDlg');
    var okBtn = document.getElementById('confirmDlgOk');
    var titleEl = document.getElementById('confirmDlgTitle');
    var textEl = document.getElementById('confirmDlgText');

    document.addEventListener('submit', function (ev) {
        var form = ev.target;
        if (!(form instanceof HTMLFormElement)) return;
        var msg = form.getAttribute('data-confirm');
        if (!msg || form.dataset.confirmed === '1') return;
        ev.preventDefault();

        if (!dlg || typeof dlg.showModal !== 'function') {
            if (window.confirm(msg)) { form.dataset.confirmed = '1'; form.submit(); }
            return;
        }
        titleEl.textContent = form.getAttribute('data-confirm-title') || 'Are you sure?';
        textEl.textContent = msg;
        okBtn.textContent = form.getAttribute('data-confirm-ok') || 'Confirm';
        okBtn.classList.toggle('btn-danger', form.hasAttribute('data-confirm-danger'));
        okBtn.classList.toggle('btn-primary', !form.hasAttribute('data-confirm-danger'));

        dlg.addEventListener('close', function onClose() {
            dlg.removeEventListener('close', onClose);
            if (dlg.returnValue !== 'ok') return;
            form.dataset.confirmed = '1';
            if (typeof form.requestSubmit === 'function') form.requestSubmit(); else form.submit();
        });
        dlg.returnValue = '';
        dlg.showModal();
    });
})();

/* Any <select data-autosubmit> submits its form on change. */
document.querySelectorAll('select[data-autosubmit]').forEach(function (s) {
    s.addEventListener('change', function () { s.form.submit(); });
});
</script>
