(() => {
    const selectElement = document.getElementById('results-season-selector') as HTMLSelectElement;

    const openPane = (season: string) => {
        document.querySelectorAll<HTMLDivElement>('.results-season-pane').forEach((pane) => {
            if (pane.dataset.season === season) {
                pane.classList.remove('d-none');
            } else {
                pane.classList.add('d-none');
            }
        });
    }

    selectElement.addEventListener('change', (evt) => {
        openPane((evt.currentTarget as HTMLSelectElement).value);
    });
    openPane(selectElement.value);
})();