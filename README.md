# Flowpack.SearchPlugin

This plugin is a Search Plugin, to be used together with

* [Flowpack.ElasticSearch.ContentRepositoryAdaptor](https://github.com/Flowpack/Flowpack.ElasticSearch.ContentRepositoryAdaptor) or 
* [Flowpack.SimpleSearch.ContentRepositoryAdaptor](https://github.com/Flowpack/Flowpack.SimpleSearch.ContentRepositoryAdaptor).

## Installation

Install via composer with your favorite adaptor:

**ElasticSearch**

    composer require flowpack/searchplugin flowpack/elasticsearch-contentrepositoryadaptor

**SimpleSearch**

    composer require flowpack/searchplugin flowpack/simplesearch-contentrepositoryadaptor

Inclusion of the routes from this package into your main `Configuration/Routes.yaml` is no longer needed as of Flow 4.0.

## Configuration


### Pagination 

The pagination search results can be configured via Fusion. The following shows the defaults:

    prototype(Flowpack.SearchPlugin:Search).configuration {
        itemsPerPage = 25
        insertAbove = false
        insertBelow = true
        maximumNumberOfLinks = 10
    }

### Custom result rendering

The result list is rendered using a Fusion object of type `nodeType + 'SearchResult'` for each hit.
Thus you can easily adjust the rendering per type like this for an imaginary `Acme.AcmeCom:Product` nodetype:

    prototype(Acme.AcmeCom:ProductSearchResult) < prototype(Neos.Neos:DocumentSearchResult) {
        templatePath = 'resource://Acme.AcmeCom/Private/Templates/SearchResult/ProductSearchResult.html'
    }

Feel free to use the `DocumentSearchResult.html` in the Flowpack.SearchPlugin as an example.

## Search completions and suggestions

The default search form template comes with a `data-autocomplete-source` attribute pointing to the 
`SuggestController` of this package. Fed with a `term` parameter via a `GET` request, it returns a
JSON-encoded array of suggestions from Elasticsearch. These are fetched with a term suggester from
the `_all` field, i.e. "the fulltext index".

These can be used to provide autocompletion on the search input using a JS library of your choice.
In case you need to build the URI to the suggest controller yourself, this is what the form uses:

    {f:uri.action(action: 'index', controller: 'Suggest', package: 'Flowpack.SearchPlugin', format: 'json', absolute: 1, arguments: {contextNodeIdentifier: node.identifier, dimensionCombination: dimensionCombination})}

The returned JSON looks like this (with a `term` of "content" after indexing the Neos demo site):

    {
      "completions": [
        "content",
        "content element",
        "content in",
        "content in neos",
        "content in neos because",
        "content in neos because you",
        "content repository"
      ],
      "suggestions": [
        {
          "text": "995c9174-ddd6-4d5c-cfc0-1ffc82184677",
          "score": 20,
          "payload": {
            "nodeIdentifier": "d17caff2-f50c-d30b-b735-9b9216de02e9"
          }
        },
        {
          "text": "a66ec7db-3459-b67b-7bcb-16e2508a89f0",
          "score": 20,
          "payload": {
            "nodeIdentifier": "fd283257-9b12-8412-f922-6643ac818294"
          }
        },
        {
          "text": "a3474e1d-dd60-4a84-82b1-18d2f21891a3",
          "score": 20,
          "payload": {
            "nodeIdentifier": "7eee2ee6-2a4e-5240-3674-2fb84a51900b"
          }
        }
      ]
    }

The `completions` can be used to suggest search terms to the user. The `suggestions` contains
"top search results" for the given term, the document they refer to is given in the `payload`.

## AJAX search

The plugin comes with a controller that can be reached like this per default, using `GET`:

    {f:uri.action(action: 'index', controller: 'AjaxSearch', package: 'Flowpack.SearchPlugin', absolute: 1)}

It expects the search term as a parameter named `q` (as defined in `AjaxSearch.fusion`). This controller
renders the search results and returns them as HTML without any of the page template. It can therefore
be used to request search results via AJAX and display the result by adding it to the DOM as needed.
