import * as pdfjsLib from "pdfjs-dist";
import workerSrc from "pdfjs-dist/build/pdf.worker.min.mjs?url";

pdfjsLib.GlobalWorkerOptions.workerSrc = workerSrc;

// Largura de renderizacao das paginas (px CSS). O canvas escala para 100% do container.
const LARGURA_ALVO = 1100;

const renderizarDocumento = async (container) => {
    const src = container.dataset.pdfSrc;

    if (!src) {
        return;
    }

    container.classList.add("is-loading");

    try {
        const pdf = await pdfjsLib.getDocument({ url: src, withCredentials: true }).promise;
        const dpr = Math.min(window.devicePixelRatio || 1, 2);

        container.classList.remove("is-loading");
        container.replaceChildren();

        for (let numero = 1; numero <= pdf.numPages; numero += 1) {
            const pagina = await pdf.getPage(numero);
            const base = pagina.getViewport({ scale: 1 });
            const escala = (LARGURA_ALVO / base.width) * dpr;
            const viewport = pagina.getViewport({ scale: escala });

            const canvas = document.createElement("canvas");
            canvas.className = "cpe-letter-page";
            canvas.width = Math.floor(viewport.width);
            canvas.height = Math.floor(viewport.height);
            canvas.setAttribute("role", "img");
            canvas.setAttribute("aria-label", `Página ${numero} de ${pdf.numPages}`);
            container.appendChild(canvas);

            await pagina.render({
                canvasContext: canvas.getContext("2d"),
                viewport,
            }).promise;
        }
    } catch (erro) {
        container.classList.remove("is-loading");
        container.replaceChildren();
        const aviso = document.createElement("div");
        aviso.className = "cpe-letter-doc__error";
        aviso.textContent = "Não foi possível carregar a carta.";
        container.appendChild(aviso);
        console.error("Cartas PDF viewer:", erro);
    }
};

const init = () => {
    document.querySelectorAll(".cpe-letter-doc[data-pdf-src]").forEach((container) => {
        if (container.dataset.pdfRendered) {
            return;
        }

        container.dataset.pdfRendered = "1";
        renderizarDocumento(container);
    });
};

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init, { once: true });
} else {
    init();
}
