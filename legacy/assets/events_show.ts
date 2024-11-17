import * as PdfViewer from './pdf';

(() => {
    PdfViewer.init();
    PdfViewer.listenForDisplayChanges();
})();
