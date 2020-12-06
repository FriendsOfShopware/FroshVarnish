# Experimental Varnish Cache for Shopware 6

# HttpCache will get a huge update in 6.4 postponed until the Release

## Installation

* Setup Varnish
    * Use default.vcl
    * Configure purger ips and backend
    * Recommanded: SSL Nginx -> Varnish -> Nginx (Shopware)
* Install Plugin
    * May correct Varnish Host in Plugin Config

### Cache Invalidation

It's currently limited to only some entities to improve cache hits

Invalidation happens on Entities:

* Product
* Category
* Manufacturer
* Media
* CMS
