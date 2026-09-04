const opcoes = document.querySelectorAll('.opcao');
const indicador = document.querySelector('.seletor-indicador');

opcoes.forEach((opcao, index) => {
    opcao.addEventListener('click', () => {

        // Remove a seleção anterior
        opcoes.forEach(item => item.classList.remove('ativa'));

        // Ativa a opção clicada
        opcao.classList.add('ativa');

        // Move o indicador
        indicador.style.transform = `translateX(${index * 100}%)`;
    });
});
