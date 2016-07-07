# Flowpack.SearchPlugin

This plugin is just a very bare-bones basis for a Search-Plugin, to be used together with
Flowpack.ElasticSearch.ContentRepositoryAdaptor or Flowpack.SimpleSearch.ContentRepositoryAdaptor.

## Installation

Make sure to include the Routes from this package into your main `Configuration/Routes.yaml` by the following snippet:

```
-
  name: 'Flowpack.SearchPlugin'
  uriPattern: '<SearchSubroutes>'
  subRoutes:
    'SearchSubroutes':
      package: 'Flowpack.SearchPlugin'
```

## Custom result rendering

The result list is rendered using a TypoScript object of type `nodeType + 'SearchResult'` for each hit.
Thus you can easily adjust the rendering per type like this for an imaginary `Acme.AcmeCom:Product` nodetype:

```
prototype(Acme.AcmeCom:ProductSearchResult) < prototype(TYPO3.Neos:DocumentSearchResult) {
    templatePath = 'resource://Acme.AcmeCom/Private/Templates/SearchResult/ProductSearchResult.html'
}
```

Feel free to use the `DocumentSearchResult.html` in the Flowpack.SearchPlugin as an example.
