BaseAdminBundle
=====================
 
[Getting started](getting_started.md#BaseAdminBundle)
| [CrudController](CrudController.md#BaseAdminBundle)
| [Router](Router.md#BaseAdminBundle)
| [AbstractVoter](AbstractVoter.md#BaseAdminBundle)
| [AbstractDatatable](AbstractDatatable.md#BaseAdminBundle)
| **TwigComponents**

## (Twig) Components

Splitting parts into components can be very useful, but using certain parts can become bulky and hard to overview,
especially when using embed.


#### Omitting blocks
The BaseAdminBundle adds the ability to have content in embed without need of any block, which can be useful when using
components for which you most often only need to set a single block. When the block is omitted, the parser wrap the content
with a {% block main %}. 

#### Twig components
For even compacter code, twig components can be defined. This bundle has a few defined itself in 
`Twig/ComponentsAsMethods.php` (you can override this service to add more components).
For example, the `entity_box` component can be used like the following example:

````twig
{% embed entity_box('Product details', product, true) %}
    <div>
        {{ sg_datatables_render(datatable) }}
    </div>
{% endembed %}
````

this would have the same result as what originally would be:

````twig
{% embed '@BaseAdmin/components/entity_box.html.twig' with {title: 'Product details', entity: product, voter: true} %}
    {% block main %}
        <div>
            {{ sg_datatables_render(datatable) }}
        </div>
    {% endblock %}
{% endembed %}
````

> TODO: More explanation about which components
> are available and how to add your own.

> TODO2: add some suggestions for autocompletion-'hacks' when
> using phpstorm. 