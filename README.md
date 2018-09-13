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

### Custom index name

It is usually a good idea to specify a custom index name for a project, instead of the default `typo3cr`. That
way no conflicts can arise when multiple projects use the same Elasticsearch server.

To specify a custom index name, the following is needed:

    Neos:
      ContentRepository:
        Search:
          elasticSearch:
            indexName: acmecom

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
`SuggestController` of this package.

To use this term suggester, you need to configure the indexing like this, to define a custom
analyzer to be used:

    Flowpack:
      ElasticSearch:
        indexes:
          default:      # client name used to connect (see Flowpack.ElasticSearch.clients)
            acmecom:    # your (custom) index name
              analysis:
                filter:
                  autocompleteFilter:
                    max_shingle_size: 5
                    min_shingle_size: 2
                    type: 'shingle'
                analyzer:
                  autocomplete:
                    filter: [ 'lowercase', 'autocompleteFilter' ]
                    char_filter: [ 'html_strip' ]
                    type: 'custom'
                    tokenizer: 'standard'

Then you need to configure the node types to be be included in the suggestion building, this can be
done like this:

    'Neos.Neos:Document':
      superTypes:
        'Flowpack.SearchPlugin:SuggestableMixin': true
        'Flowpack.SearchPlugin:AutocompletableMixin': true

    'Neos.Neos:Shortcut':
      superTypes:
        'Flowpack.SearchPlugin:SuggestableMixin': false
        'Flowpack.SearchPlugin:AutocompletableMixin': false

    'Neos.NodeTypes:TitleMixin':
      superTypes:
        'Flowpack.SearchPlugin:SuggestableMixin': true
        'Flowpack.SearchPlugin:AutocompletableMixin': true

When fed with a `term` parameter via a `GET` request, the `SuggestController` will return a
JSON-encoded array of suggestions from Elasticsearch. They are fetched with a term suggester
from the `_all` field, i.e. "the fulltext index".

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
          "_index": "acmecom-1536833562",
          "_type": "Neos-Neos:Page",
          "_id": "03da089f6495852dc9e7b796adde85f21093b3c7",
          "score": 40,
          "payload": {
            "__path": "/sites/acmecom/node-2"
          },
          "contexts": {
            "workspace": {
              0: "live"
            },
            "parentPath": {
              0: "/sites/acmecom"
            },
            "dimensionCombinationHash": {
              0: "d751713988987e9331980363e24189ce"
            },
          }
        },
        {
          "text": "a66ec7db-3459-b67b-7bcb-16e2508a89f0",
          "_index": "acmecom-1536833562",
          "_type": "Neos-Neos:Page",
          "_id": "151a1d0531f1ac5c1a267a3d6a3af84967e0c35f",
          "score": 20,
          "payload": {
            "__path": "/sites/acmecom/node-1"
          },
          "contexts": {
            "workspace": {
              0: "live"
            },
            "parentPath": {
              0: "/sites/acmecom"
            },
            "dimensionCombinationHash": {
              0: "d751713988987e9331980363e24189ce"
            },
          }
        },
        {
          "text": "a3474e1d-dd60-4a84-82b1-18d2f21891a3",
          "_index": "acmecom-1536833562",
          "_id": "c443d53c76de1af2438b8af0bf33dc7befe291f5",
          "_type": "Neos-Neos:Page",
          "score": 20,
          "_source": {
            "__path": "/sites/acmecom/node-3"
          },
          "contexts": {
            "workspace": {
              0: "live"
            },
            "parentPath": {
              0: "/sites/acmecom"
            },
            "dimensionCombinationHash": {
              0: "d751713988987e9331980363e24189ce"
            },
          }
        }
      ]
    }

The `completions` can be used to suggest search terms to the user. The `suggestions` contains
"top search results" for the given term, the document they refer to is given in the `payload`.

## AJAX search

The plugin comes with a controller that can be reached like this per default, using `GET`:

    {f:uri.action(action: 'search', controller: 'AjaxSearch', package: 'Flowpack.SearchPlugin', arguments: {node: node, q: ''}, absolute: 1)}

It expects the search term as a parameter named `q` (as defined in `AjaxSearch.fusion`). This controller
renders the search results and returns them as HTML without any of the page template. It can therefore
be used to request search results via AJAX and display the result by adding it to the DOM as needed.

## Removing special chars from search term

You might need to remove special chars to prevent search errors. Some chars are reserved in Elasticsearch
and you can replace them before submitting the search like this:

    prototype(Flowpack.SearchPlugin:Search) {
        searchTerm = ${request.arguments.search}
        searchTerm.@process.removeSpecialChars = ${String.pregReplace(value, "/[^a-zA-Z0-9äöüÄÖÜß]/", "")}
    }

Keep in mind, that this blocks the explicit use of wildcards (`*`) and phrase search (`"search exactly this"`)
for your users, in case you want to support that.
