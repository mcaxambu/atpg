/*
 * Movimento por rolagem do site publico.
 *
 * O portal ja tinha animacoes, mas todas disparavam no CARREGAMENTO — inclusive
 * as secoes abaixo da dobra. Quando o visitante chegava nelas rolando, a
 * animacao ja tinha acabado havia muito tempo, e a pagina parecia estatica.
 * Aqui cada bloco anima quando entra na tela.
 *
 * Duas regras que o codigo respeita de proposito:
 *
 * 1. Sem JS, nada fica escondido. O estado inicial (invisivel) so e aplicado
 *    depois que este script confirma suporte, marcando a raiz do documento.
 *    Assim leitor de tela, buscador e navegador antigo veem o conteudo normal.
 *
 * 2. Quem pediu menos movimento no sistema nao recebe nenhum. Nao e preferencia
 *    estetica: animacao pode causar enjoo e desconforto real em quem tem
 *    sensibilidade vestibular.
 */
(() => {
    const querMenosMovimento = window.matchMedia('(prefers-reduced-motion: reduce)');

    if (querMenosMovimento.matches || !('IntersectionObserver' in window)) {
        return;
    }

    /** Blocos que ganham entrada propria ao aparecer. */
    const ALVOS = [
        '.section',
        '.page-section > .page-title',
        // Os cartoes, e nao o bloco inteiro: assim eles entram em sequencia.
        '.stats-section > .stat-card',
        '.feature-grid > article',
        '.values-grid > .value-card',
        '.purpose-grid > .purpose-card',
        '.job-list > .job-card',
        '.post-grid > *',
        '.company-card',
        '.member-card',
        '.event-timeline > article',
        '.content-card',
        '.company-callout',
        '.job-block',
    ].join(', ');

    // Marca a raiz: e o gatilho para o CSS esconder o estado inicial. Sem esta
    // classe o conteudo fica visivel, que e o comportamento sem JS.
    document.documentElement.classList.add('com-movimento');

    // Prova de que o observador esta vivo. A rede de seguranca so entra em
    // acao se isto continuar falso — ver `armarRede`.
    let observadorRespondeu = false;

    const observador = new IntersectionObserver((entradas) => {
        observadorRespondeu = true;

        entradas.forEach((entrada) => {
            if (!entrada.isIntersecting) return;

            entrada.target.classList.add('revelado');
            observador.unobserve(entrada.target);
        });
    }, {
        // Comeca um pouco antes de entrar de fato: o bloco chega a dobra ja
        // em movimento, em vez de "pular" depois de aparecer.
        rootMargin: '0px 0px -12% 0px',
        threshold: 0.08,
    });

    const preparar = () => {
        document.querySelectorAll(ALVOS).forEach((elemento) => {
            if (elemento.dataset.revelar) return;

            elemento.dataset.revelar = '';
            observador.observe(elemento);
        });

        // Escalona os itens de cada grade: os cartoes entram em sequencia,
        // e nao todos de uma vez. O limite evita atraso longo demais numa
        // listagem grande.
        document.querySelectorAll('.feature-grid, .values-grid, .purpose-grid, .job-list, .post-grid, .directory-grid, .stats-section').forEach((grade) => {
            [...grade.children].forEach((filho, indice) => {
                filho.style.setProperty('--atraso', `${Math.min(indice, 7) * 90}ms`);
            });
        });
    };

    /*
     * Rede de seguranca — condicional, de proposito.
     *
     * O estado inicial e invisivel, e quem devolve a visibilidade e o
     * observador. Se ele nao rodar, o conteudo some de vez: aba em segundo
     * plano ou pre-renderizada nao dispara IntersectionObserver, e um erro
     * adiante no arquivo mataria o resto do script.
     *
     * A versao anterior revelava tudo depois de 4s sem perguntar nada — e isso
     * ANULAVA a animacao: quem levasse mais de 4 segundos para comecar a rolar
     * (ou seja, quase todo mundo) encontrava os cards ja revelados e nao via
     * entrada nenhuma.
     *
     * Agora a rede so age se o observador nunca tiver respondido. Basta uma
     * resposta dele — mesmo que seja "nada visivel ainda" — para sabermos que o
     * mecanismo funciona, e entao a revelacao fica por conta da rolagem, sem
     * prazo.
     */
    const revelarTudo = () => {
        document.querySelectorAll('[data-revelar]:not(.revelado)').forEach((elemento) => {
            elemento.classList.add('revelado');
        });
    };

    const conferirRede = () => {
        if (! observadorRespondeu) {
            revelarTudo();
        }
    };

    const armarRede = () => {
        setTimeout(conferirRede, 4000);

        // Aba que estava oculta e volta a aparecer: o observador pode nao ter
        // rodado enquanto a pagina nao era renderizada. Damos um tempo para ele
        // responder e so entao conferimos.
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                setTimeout(conferirRede, 1500);
            }
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            preparar();
            armarRede();
        });
    } else {
        preparar();
        armarRede();
    }

    /*
     * Contador dos numeros das estatisticas.
     *
     * So mexe em elemento cujo texto e um numero puro: "12" vira contagem,
     * mas "PG" ou "+50" ficam como estao.
     */
    const contar = (elemento) => {
        const alvo = Number(elemento.textContent.trim());

        if (!Number.isFinite(alvo) || alvo <= 0) return;

        const duracao = 900;
        const inicio = performance.now();

        const passo = (agora) => {
            const progresso = Math.min((agora - inicio) / duracao, 1);
            // easeOutCubic: rapido no comeco, desacelerando no fim.
            const suave = 1 - Math.pow(1 - progresso, 3);

            elemento.textContent = Math.round(alvo * suave).toString();

            if (progresso < 1) requestAnimationFrame(passo);
        };

        // O numero real so e substituido dentro do primeiro quadro.
        // Zera-lo aqui fora deixaria "0" na tela para sempre caso o
        // requestAnimationFrame nunca rode — o que acontece em aba oculta ou
        // pre-renderizada.
        requestAnimationFrame(passo);
    };

    const observadorDeNumeros = new IntersectionObserver((entradas) => {
        entradas.forEach((entrada) => {
            if (!entrada.isIntersecting) return;

            contar(entrada.target);
            observadorDeNumeros.unobserve(entrada.target);
        });
    }, { threshold: 0.5 });

    const prepararNumeros = () => {
        document.querySelectorAll('.stat-card strong').forEach((numero) => {
            observadorDeNumeros.observe(numero);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', prepararNumeros);
    } else {
        prepararNumeros();
    }
})();
