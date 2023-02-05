import * as pdfjsLib from "pdfjs-dist/legacy/build/pdf";

// Setting worker path to worker bundle.
pdfjsLib.GlobalWorkerOptions.workerSrc = "/build/pdfjs/pdf.worker.js";
const outputScale = window.devicePixelRatio || 1;

const pdfPages: { canvas: HTMLCanvasElement, pdfPage: pdfjsLib.PDFPageProxy }[] = [];

const loadPdf = (canvas: HTMLCanvasElement) => {
    const pdfUrl = canvas.dataset.pdfUrl;

    const ctx = canvas.getContext("2d");
    if (null === ctx) {
        throw Error('Cannot get a Context');
    }
    ctx.font = "14px Georgia";
    ctx.fillText('Chargement du document...', 4, 20);

    if (undefined === pdfUrl) {
        throw Error('No url found in canvas element');
    }

    // Loading a document.
    const loadingTask = pdfjsLib.getDocument(`${pdfUrl}`);
    loadingTask.promise
        .then(function (pdfDocument) {
            // Request a first page
            return pdfDocument.getPage(1).then(function (pdfPage) {
                pdfPages.push({ canvas, pdfPage });
                return renderPage(pdfPage, canvas).promise;
            });
        })
        .catch(function (reason) {
            console.error("Error: " + reason);
        });
}

enum ScalingMode {
    Width = 'width',
    Height = 'height'
}
const renderPage = (pdfPage: pdfjsLib.PDFPageProxy, canvas: HTMLCanvasElement, scalingMode: ScalingMode = ScalingMode.Width): pdfjsLib.RenderTask => {
    const originalViewport = pdfPage.getViewport({scale: 1.0});
    const parentElement = canvas.parentElement as HTMLElement;
    const parentComputedStyles = getComputedStyle(parentElement);
    const desiredWith = parentElement.clientWidth
        - parseFloat(parentComputedStyles.paddingLeft)
        - parseFloat(parentComputedStyles.paddingRight);
    const desiredHeight = parentElement.clientHeight
        - parseFloat(parentComputedStyles.paddingTop)
        - parseFloat(parentComputedStyles.paddingBottom);

    let wantedScale: number = 1.0;
    if (scalingMode === ScalingMode.Width) { // set document with, update canvas height
        wantedScale = desiredWith / originalViewport.width;
    }
    if (scalingMode === ScalingMode.Height) { // set document height, update canvas width
        wantedScale = desiredHeight / originalViewport.height;
    }
    console.log(wantedScale);
    const scaledViewport = pdfPage.getViewport({ scale: wantedScale });

    canvas.width = originalViewport.width * wantedScale * outputScale;
    canvas.height = originalViewport.height * wantedScale * outputScale;
    canvas.style.width = Math.floor(scaledViewport.width) + "px";
    canvas.style.height =  Math.floor(scaledViewport.height) + "px";

    const transform = outputScale !== 1
        ? [outputScale, 0, 0, outputScale, 0, 0]
        : undefined;

    const ctx = canvas.getContext("2d") as CanvasRenderingContext2D;
    return pdfPage.render({
        canvasContext: ctx,
        viewport: scaledViewport,
        transform,
    });
}

const resizePages = () => {
    pdfPages.forEach(({canvas, pdfPage}) => {
        renderPage(pdfPage, canvas);
    });
}

const init = () => {
    document.querySelectorAll<HTMLCanvasElement>('canvas[data-pdf-url]').forEach((canvas) => {
        loadPdf(canvas);
    });
}

const listenForDisplayChanges = () => {
    document.addEventListener('shown.bs.collapse', (event) => {
        pdfPages.forEach(({canvas, pdfPage}) => {
            if ((event.currentTarget as HTMLElement).contains(canvas)) {
                renderPage(pdfPage, canvas);
            }
        });
    });
    let previousWindowSize = {w: window.innerWidth, h: window.innerHeight};
    window.addEventListener("resize", () => {
        if (previousWindowSize.w !== window.innerWidth && previousWindowSize.h !== window.innerHeight) {
            resizePages();
            previousWindowSize = {w: window.innerWidth, h: window.innerHeight};
        }
    });
}

export { init, listenForDisplayChanges };