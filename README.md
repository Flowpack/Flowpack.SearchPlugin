# Flowpack.SearchPlugin

This plugin is just a very bare-bones basis for a Search-Plugin, to be used together with
Flowpack.ElasticSearch.ContentRepositoryAdaptor or Flowpack.SimpleSearch.ContentRepositoryAdaptor.

## Installation

Make sure to include the Routes from this package into your main `Configuration/Routes.yaml` by the following snippet:

    -
      name: 'Flowpack.SearchPlugin'
      uriPattern: '<SearchSubroutes>'
      subRoutes:
        'SearchSubroutes':
          package: 'Flowpack.SearchPlugin'

## Custom result rendering

The result list is rendered using a TypoScript object of type `nodeType + 'SearchResult'` for each hit.
Thus you can easily adjust the rendering per type like this for an imaginary `Acme.AcmeCom:Product` nodetype:

    prototype(Acme.AcmeCom:ProductSearchResult) < prototype(TYPO3.Neos:DocumentSearchResult) {
        templatePath = 'resource://Acme.AcmeCom/Private/Templates/SearchResult/ProductSearchResult.html'
    }

Feel free to use the `DocumentSearchResult.html` in the Flowpack.SearchPlugin as an example.

## Search suggestions

The default search form template comes with a `data-autocomplete-source` attribute pointing to the 
`SuggestController` of this package. Fed with a `term` parameter via a `GET` request, it returns a
JSON-encoded array of suggestions from Elasticsearch. These are fetched with a term suggester from
`_all` field, i.e. "the fulltext index".

These can be used to provide autocompletion on the search input using a JS library of your choice.
In case you need to build the URI to the suggest controller yourself, this is what the form uses:

    {f:uri.action(action: 'index', controller: 'Suggest', package: 'Flowpack.SearchPlugin', format: 'json', absolute: 1)}
