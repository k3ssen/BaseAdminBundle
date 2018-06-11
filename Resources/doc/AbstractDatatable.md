BaseAdminBundle
=====================
 
[Getting started](getting_started.md#BaseAdminBundle)
| [CrudController](CrudController.md#BaseAdminBundle)
| [Router](Router.md#BaseAdminBundle)
| [AbstractVoter](AbstractVoter.md#BaseAdminBundle)
| **AbstractDatatable**
| [TwigComponents](TwigComponents.md#BaseAdminBundle)

## AbstractDatatable    

When using the [SgDatatablesBundle](https://github.com/stwe/DatatablesBundle), 
defining a custom datatable takes up quite a bit of code.
 Just have a look at the [installation#your-first-datatable documentation](https://github.com/stwe/DatatablesBundle/blob/v1.0.4/Resources/doc/installation.md#your-first-datatable)
.

The `AbstractDatatable` of this bundle will set options and methods that are
 common for entities for which CRUD is being implemented. 

For example, let's say you have a Product entity. The ProductDatatable 
will look something like this:

````php
class ProductDatatable extends AbstractDatatable
{
    protected const USE_VOTER_CHECK = true;

    public function addColumns(array $options = []): void
    {
        $this->columnBuilder
            ->add('id', Column::class, [
                'title' => '#'
            ])
            ->add('name', Column::class, [
                'title' => 'Name'
            ])
            ->add('ean', Column::class, [
                'title' => 'Ean'
            ])
            ->add('price', Column::class, [
                'title' => 'Price'
            ])
            ->add('stock', Column::class, [
                'title' => 'Stock'
            ])
        ;
    }

    public function getEntity()
    {
        return Product::class;
    }
}
````

The AbstractDatatable takes care of settings and adding actions.

Furthermore, using this ProductDatatable in the controller can be done much more simple
than [showed in the SgDatatablesBundle documentation](https://github.com/stwe/DatatablesBundle/blob/v1.0.4/Resources/doc/installation.md#step-3-the-controller-actions).
Using the ProductDatatable example, the ProductController would need to implement an
index and result action:

````php
/**
 * @Route("/", name="product_index")
 */
public function indexAction(ProductDatatable $datatable): Response
{
    return $this->render('product/index.html.twig', [
        'datatable' => $datatable->buildDatatable(),
    ]);
}

/**
 * @Route("/result", name="product_result")
 */
public function resultAction(ProductDatatable $datatable): Response
{
    return $datatable->getResponse();
}
````
    
By using a separate action for the datatable results, you can use
the datatable in other places as well, without any effort for handling responses.

