<!-- Vue : auth/verify-pending -->
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-mark" aria-hidden="true">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
            </svg>
        </div>
        <h1>Vérifiez vos e-mails</h1>
        <p class="auth-lead">
            Un lien de confirmation vient d’être envoyé.<br>
            Cliquez dessus pour activer votre compte.
        </p>

        <div class="form-errors" style="border-color:#e8d4a8;background:#fbf6ea;color:#7a5b12">
            <p style="margin:0;font-weight:600">Vous ne trouvez pas l’e-mail ?</p>
            <ul>
                <li>Vérifiez Spam / Courrier indésirable</li>
                <li>L’e-mail peut prendre quelques minutes</li>
            </ul>
        </div>

        <p class="auth-footer">
            <a href="/login">← Retour à la connexion</a>
        </p>
    </div>
</div>
