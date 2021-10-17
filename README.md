# Flowpack.SearchPlugin

[![Latest Stable Version](https://poser.pugx.org/flowpack/searchplugin/v/stable)](https://packagist.org/packages/flowpack/searchplugin) [![Total Downloads](https://poser.pugx.org/flowpack/searchplugin/downloads)](https://packagist.org/packages/flowpack/searchplugin)

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
              settings:
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

### Adjust the suggestion context

The suggestionContext determines, if a result should be displayed in suggetions. By default, nodes that are hidden are excluded.
In many projects, the search als takes the Neos.Seo metaRobotsNoindex property into account or excludes certain nodeTypes. In order to adjust the suggestion context to your search logic, you can write your custom logic and switch the implementation of the `Flowpack\SearchPlugin\Suggestion\SuggestionContextInterface` via Objects.yaml

## AJAX search

The plugin comes with a controller that can be reached like this per default, using `GET`:

    {f:uri.action(action: 'search', controller: 'AjaxSearch', package: 'Flowpack.SearchPlugin', arguments: {node: node, q: ''}, absolute: 1)}

It expects the search term as a parameter named `q` (as defined in `AjaxSearch.fusion`). This controller
renders the search results and returns them as HTML without any of the page template. It can therefore
be used to request search results via AJAX and display the result by adding it to the DOM as needed.

## Removing special chars from search term

It is recommended to remove characters, which are reserved in Elasticsearch from the search term to prevent errors. There is 
an eel helper to replace them before submitting the search like this:

    prototype(Flowpack.SearchPlugin:Search) {
        searchTerm = ${Flowpack.SearchPlugin.SearchTerm.sanitize(request.arguments.search)}
    }

Keep in mind, that this blocks the explicit use of wildcards (`*`) and phrase search (`"search exactly this"`)
for your users, in case you want to support that.
