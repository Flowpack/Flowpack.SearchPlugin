# Flowpack.SearchPlugin

This plugin is just a very bare-bones basis for a Search-Plugin, to be used together with Flowpack.ElasticSearch.ContentRepositoryAdaptor
or Flowpack.SimpleSearch.ContentRepositoryAdaptor. I doubt it is already useful for production scenarios, but it could be definitely improved into this direction.

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
