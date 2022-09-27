/**
 * This plugin initialises all search forms which have the attribute `data-autocomplete-source` set
 * and provides completions and suggestions when a user enters search terms.
 */
(() => {
    const searchForms = document.querySelectorAll('form[data-autocomplete-source]');

    /**
     * Fills the autocomplete container with matches grouped by completion and suggestions
     *
     * @param searchForm {HTMLFormElement}
     * @param autocompleteContainer {HTMLDivElement}
     * @param input {HTMLInputElement}
     * @param query {string}
     * @param completions {array<string>}
     * @param suggestions {array<{ title: string, __url: string, __snippet: string }>}
     */
    function populateAutocompleteContainer(
        searchForm,
        autocompleteContainer,
        input,
        query,
        completions,
        suggestions
    ) {
        // TODO: Reuse the previous html structure if possible
        autocompleteContainer.innerHTML = '';
        autocompleteContainer.style.display = completions.length > 0 || suggestions.length > 0 ? 'block' : 'none';
        const queryRegex = new RegExp(`(${query})`, 'ig');

        if (completions.length > 0) {
            const completionsHeader = document.createElement('h3');
            completionsHeader.innerText = 'Completions';
            autocompleteContainer.appendChild(completionsHeader);

            const completionsList = document.createElement('ol');
            completionsList.classList.add('autocomplete-container__completions');
            completions.forEach((completion) => {
                // Highlight the query in the completion
                completion = completion.replaceAll(queryRegex, '<strong>$1</strong>')
                const completionItem = document.createElement('li');
                completionItem.innerHTML = completion;
                completionsList.appendChild(completionItem);
            });

            // Update input when selection a completion and submit the form
            completionsList.addEventListener('click', (event) => {
                if (event.target.parentElement === completionsList) {
                    input.value = event.target.innerText;
                    closeAutocompleteContainer(autocompleteContainer);
                    searchForm.submit();
                }
            });
            autocompleteContainer.appendChild(completionsList);
        }

        if (suggestions.length > 0) {
            const suggestionsHeader = document.createElement('h3');
            suggestionsHeader.innerText = 'Suggestions';
            autocompleteContainer.appendChild(suggestionsHeader);

            const suggestionsList = document.createElement('ol');
            suggestionsList.classList.add('autocomplete-container__suggestions');
            suggestions.forEach((suggestion) => {
                const suggestionItem = document.createElement('li');
                const suggestionLink = document.createElement('a');
                suggestionLink.href = suggestion['__url'];
                suggestionLink.target = '_blank';
                suggestionLink.innerHTML = suggestion['__snippet'] || suggestion['title'];
                suggestionItem.appendChild(suggestionLink);
                suggestionsList.appendChild(suggestionItem);
            });
            autocompleteContainer.appendChild(suggestionsList);
        }
    }

    /**
     * @param autocompleteContainer {HTMLDivElement}
     */
    function closeAutocompleteContainer(autocompleteContainer) {
        autocompleteContainer.style.display = 'none';
    }

    /**
     * @param searchForm {HTMLFormElement}
     */
    searchForms.forEach((searchForm) => {
        const dataSource = searchForm.dataset.autocompleteSource;
        const input = searchForm.querySelector('input[type="search"]');
        const autocompleteContainer = document.createElement('div');
        autocompleteContainer.classList.add('autocomplete-container');

        // Close the autocomplete container when pressing "escape"
        document.addEventListener('keyup', (event) => {
            if (event.key === 'Escape') {
                closeAutocompleteContainer(autocompleteContainer);
            }
        });

        // Close the autocomplete container when clicking outside of it
        document.addEventListener('click', (event) => {
            if (!autocompleteContainer.contains(event.target)) {
                closeAutocompleteContainer(autocompleteContainer);
            }
        });

        // TODO: Implement navigating completions and suggestions with cursor keys

        // Allow positioning the autocomplete container relative to the input
        searchForm.style.position = 'relative';

        input.after(autocompleteContainer);

        input.addEventListener('input', (event) => {
            const query = event.target.value.trim();
            const url = `${dataSource}&term=${encodeURIComponent(query)}`;

            fetch(url, {
                credentials: 'include'
            })
                .then((response) => response.json())
                .then(({completions, suggestions}) => {
                    populateAutocompleteContainer(searchForm, autocompleteContainer, input, query, completions, suggestions);
                })
                .catch((error) => {
                    // TODO: Show error in frontend
                    console.error(error);
                });
        });
    });
})();
