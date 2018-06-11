BaseAdminBundle
=====================

[Getting started](getting_started.md#BaseAdminBundle)
| [CrudController](CrudController.md#BaseAdminBundle)
| **Router**
| [AbstractVoter](AbstractVoter.md#BaseAdminBundle)
| [AbstractDatatable](AbstractDatatable.md#BaseAdminBundle)
| [TwigComponents](TwigComponents.md#BaseAdminBundle)

## Router

The router is overridden to allow objects being parsed as parameter.

A typicall controller action will look like the this show action:
````php
/**
 * @Route("/{id}/show", name="product_show")
 */
public function showAction(Product $product): Response
````
The ParamConverter will recognize the id and converts it to
the matching product object, so that you don't have to be
bothered with adding code for fetching the product yourself. 

When referring to this route, a rederectToRoute will look like the following code:
````php
return $this->generateUrl('product_show', ['id' => $product]);
````
Since it is obvious enough for a ParamConverter to know that an id 
should be converted to a related object, it in turn should be obvious
that the Router knows that an object should be converted to it's id.

By overriding the router, the previous example can be written as follows:
````php
return $this->generateUrl('product_show', $product);
````

For this to work, the product is required to have an identifier called 'id'. Also
the router must contain the '{id}' in its path. 

If a product were to have an identifier called 'code' the Router won't know what to
convert the Product into. To resolve this, you can add the `IdentifiableInterface` to
Product and use '{code}' inside the path. 

Of course, you can still generate an url using the traditional way, which will
still be needed when passing multiple parameters.

To use an object as parameter, a controller needs to extend the CrudController.


#### Twig

The Router also affects twig, where you could use something like this:  
````twig
// new
{{ path('product_show', product) }}

// alternative to
{{ path('product_show', {id: product.id}) }}
````