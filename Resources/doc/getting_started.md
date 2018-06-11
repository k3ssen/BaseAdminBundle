BaseAdminBundle
=====================

**Getting started**
| [CrudController](CrudController.md#BaseAdminBundle)
| [Router](Router.md#BaseAdminBundle)
| [AbstractVoter](AbstractVoter.md#BaseAdminBundle)
| [AbstractDatatable](AbstractDatatable.md#BaseAdminBundle)
| [TwigComponents](TwigComponents.md#BaseAdminBundle)

> ## Note: this bundle is under development and lots of changes may be still going on.

## Getting Started

Add this git repository to your `composer.json` file:

    //...
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/k3ssen/BaseAdminBundle.git"
        }
    ],
    
Run `composer require k3ssen/base-admin:dev-master --dev` in your console.

Symfony Flex should add the bundle automatically to your `config/bundles.php`.
To make sure, open this file and check if the following line 
is added to the array:

    
    K3ssen\BaseAdminBundle\BaseAdminBundle::class => ['all' => true],
    
(add this line yourself if missing)

### Quick start with example template

If you're creating a new project, you could use the following line
in your `templates/base.html.twig`:

    {% extends '@BaseAdmin/layout.html.twig' %}
    
This layout contains the basics for what is needed in the crud application.

Most certainly you should use your own layout, but it can be helpful to
get started with some basis if you don't have a layout ready yet. 

### Required/Recommended bundles

No other bundles are strictly required for using this bundle, but to
take full advantage of this bundle, you may need
the following bundles:

- [DatatablesBundle](https://github.com/stwe/DatatablesBundle)
for datatables.  
The `AbstractDatatable` needs this bundle to work.
- [StofDoctrineExtensionsBundle](http://symfony.com/doc/master/bundles/StofDoctrineExtensionsBundle/index.html).  
The `CrudController` takes SoftDeleteable into account, it won't break
without, so it's fully optional to use this bundle.
- [CrudGeneratorBundle](https://github.com/k3ssen/CrudGeneratorBundle)  
The CrudGenerator is built together with this bundle. It
lets you generate files for quickly creating your application.
Using the CrudGeneratorBundle is fully optional.