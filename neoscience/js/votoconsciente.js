document.addEventListener('DOMContentLoaded', function() {
    const profileButton = document.getElementById('profile-button');
    const profileCard = document.getElementById('profile-card');

    if (profileButton && profileCard) {
        profileButton.addEventListener('click', function(event) {
            event.preventDefault();
            profileCard.style.display = 'block';
        });

        document.addEventListener('click', function(event) {
            if (!profileButton.contains(event.target) && !profileCard.contains(event.target)) {
                profileCard.style.display = 'none';
            }
        });
    }

    const checkboxIds = ['negro(a)', 'indigena', 'lgbtqia+', 'mulher'];
    checkboxIds.forEach(function(id) {
        const checkbox = document.getElementById(id);
        if (checkbox) {
            checkbox.addEventListener('change', monta_lista);
        }
    });

    monta_lista();
});

function monta_lista() {
    const lista = document.getElementById('main');
    if (!lista) {
        return;
    }

    const dados = [
        {
            nome: 'Maria da Silva',
            cargo: 'Vereador',
            perfil: 'Negro(a)',
            proposta: 'Ambiente'
        },
        {
            nome: 'Leonardo da Silva',
            cargo: 'Senador',
            perfil: 'Indigena',
            proposta: 'Educação'
        }
    ];

    let html = '';
    const filtro_perfil = [];

    if (document.getElementById('negro(a)')?.checked) {
        filtro_perfil.push('Negro(a)');
    }
    if (document.getElementById('indigena')?.checked) {
        filtro_perfil.push('Indigena');
    }
    if (document.getElementById('mulher')?.checked) {
        filtro_perfil.push('Mulher');
    }
    if (document.getElementById('lgbtqia+')?.checked) {
        filtro_perfil.push('LGBTQIA+');
    }

    for (let i = 0; i < dados.length; i++) {
        if (filtro_perfil.length > 0 && !filtro_perfil.includes(dados[i].perfil)) {
            continue;
        }

        html += "<div class='card'>";
        html += "<h2>" + dados[i].nome + "</h2>";
        html += "<p><strong>Cargo:</strong> " + dados[i].cargo + "</p>";
        html += "<p><strong>Perfil:</strong> " + dados[i].perfil + "</p>";
        html += "<p><strong>Proposta:</strong> " + dados[i].proposta + "</p>";
        html += "</div>";
    }

    lista.innerHTML = html;
}
