/*
 * Desenha QR codes a partir de `[data-qr]`.
 *
 * Entrypoint proprio, como o do editor: o portal tambem roda sob subpasta
 * (/atpg), e import dinamico dentro do app.js faria o Vite resolver a base em
 * tempo de build, buscando o chunk sem o prefixo.
 *
 * A geracao e no NAVEGADOR de proposito — assim nao entra pacote PHP novo, que
 * exigiria mexer no deploy (a publicacao do ATPG nao leva a pasta vendor).
 */

import QRCode from 'qrcode';

/*
 * Correcao de erro alta: este QR vai ser impresso, plastificado e manuseado
 * em reuniao. Risco e dobra, reflexo e dedo sujo fazem parte da vida dele; com
 * nivel alto, ate um quarto do desenho pode se perder e ele ainda le.
 */
const OPCOES = {
    errorCorrectionLevel: 'H',
    margin: 1,
    color: { dark: '#0f172a', light: '#ffffff' },
};

function desenhar(elemento) {
    const valor = elemento.dataset.qr;

    if (! valor) {
        return;
    }

    const tamanho = Number(elemento.dataset.qrSize || 320);

    QRCode.toCanvas(elemento, valor, { ...OPCOES, width: tamanho }, (erro) => {
        if (erro) {
            console.error('Não foi possível desenhar o QR code:', erro);
        }
    });
}

function iniciar() {
    document.querySelectorAll('canvas[data-qr]').forEach(desenhar);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciar);
} else {
    iniciar();
}
