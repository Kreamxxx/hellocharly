define(['jquery', 'core/notification'], function ($, Notification) {
    return {
        init: function () {
            console.log('✅ Script Hello Charly progression chargé');

            const block = $('.block-hellocharly');
            const userid = block.data('userid');
            const sesskey = block.data('sesskey');

            if (!userid || !sesskey) {
                console.error('❌ Données manquantes : userid ou sesskey');
                return;
            }

            const loadingDiv = block.find('.hellocharly-loading');
            // Affiche le spinner au début du chargement
            loadingDiv.html('<i class="fa fa-spinner fa-spin"></i>').show();

            // 1. Charger la progression
            console.log('👤 Récupération progression pour userid :', userid);
            $.ajax({
                url: M.cfg.wwwroot + '/lib/ajax/service.php?sesskey=' + encodeURIComponent(sesskey),
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify([{
                    methodname: 'block_hellocharly_get_user_data',
                    args: { userid: parseInt(userid) }
                }]),
                success: function (response) {
                    console.log("Réponse SSO Hello Charly :", response);
                    console.log('Données brutes reçues :', JSON.stringify(response));

                    if (response[0]?.exception) {
                        loadingDiv.html('<p>⚠️ Erreur : ' + (response[0].message || 'Données non disponibles') + '</p>');
                        return;
                    }
                    if (response[0]?.data?.html) {
                        loadingDiv.html(response[0].data.html);
                    } else {
                        loadingDiv.html('<p>⚠️ Aucune donnée reçue.</p>');
                    }
                },
                error: function (error) {
                    console.error('❌ Erreur AJAX Hello Charly :', error);
                    loadingDiv.html('<p>⚠️ Erreur de chargement des données Hello Charly.</p>');
                }
            });

            // 2. Gérer le clic bouton SSO
            block.on('click', '.hellocharly-access-btn', function (e) {
                e.preventDefault();
                const button = $(this);
                loadingDiv.show();
                button.prop('disabled', true);

                $.ajax({
                    url: M.cfg.wwwroot + '/lib/ajax/service.php?sesskey=' + encodeURIComponent(sesskey),
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify([{
                        methodname: 'block_hellocharly_generate_sso_token',
                        args: { userid, sesskey }
                    }]),
                    success: function (response) {
                        console.log('📦 Réponse SSO Hello Charly', response);
                        const data = response[0]?.data;
                        if (data?.redirect_url) {
                            window.open(data.redirect_url, '_blank');
                        } else {
                            Notification.alert('Erreur', data?.message || 'Token non généré');
                        }
                    },
                    error: function (error) {
                        console.error('❌ Erreur AJAX SSO Hello Charly :', error);
                        Notification.alert('Erreur', 'Erreur AJAX SSO Hello Charly');
                    },
                    complete: function () {
                        loadingDiv.hide();
                        button.prop('disabled', false);
                    }
                });
            });
        }
    };
});