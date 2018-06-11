BaseAdminBundle
=====================

[Getting started](getting_started.md#BaseAdminBundle)
| **CrudController]**
| [Router](Router.md#BaseAdminBundle)
| [AbstractVoter](AbstractVoter.md#BaseAdminBundle)
| [AbstractDatatable](AbstractDatatable.md#BaseAdminBundle)
| [TwigComponents](TwigComponents.md#BaseAdminBundle)

## CrudController 
The CrudController is an alternative to symfony's abstract Controller
and adds some methods for handling forms and saving data.

| New methods | Alternative to|
|:-----|:-------|
|getEntityManager|$this->getDoctrine()->getManager()|
|getParameter(string $name)|$this->container->getParameter($name)|
|createDeleteForm($object)|$this->createFormBuilder($object)<br>&nbsp;&nbsp;&nbsp;&nbsp;->setMethod('DELETE')<br>&nbsp;&nbsp;&nbsp;&nbsp;->getForm()|
|save($object)|$em = $this->getDoctrine()->getManager();<br>$em->persist($object);<br>$em->flush();
|delete($object)|$em = $this->getDoctrine()->getManager();<br>$em->remove($object);<br>$em->flush();
|handleForm(FormInterface $form, Request $request)|see 'Example editAction'
|handleDeleteForm(FormInterface $form, Request $request)|see 'Example deleteAction'
|isDeletable($object)|explained in 'Example deleteAction'

#### Overwrites `redirectToRoute` and `generateUrl`
To allow objects to be passed to the router (see Router documentation for more info),
the `redirectToRoute` and `generateUrl` are overwritten. They can be used in the
exact same way as you would normally use these, but additionally they also allow
objects.

Example:  

````php
// Original
`return $this->redirectToRoute('product_show', ['id' => $product->getId()]);` 
// New
`return $this->redirectToRoute('product_show', $product);`
````

#### Example editAction:

**Traditional**
````php
/**
 * @Route("/{id}/edit", name="product_edit")
 * @Method({"GET", "POST"})
 */
public function editAction(Request $request, Product $product): Response
{
    $form = $this->createForm(ProductType::class, $product);
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
        $em = $this->getDoctrine()->getEntityManager();
        $em->persist($product);
        $em->flush();
        $this->addFlash('success', static::MESSAGE_SAVED);
        return $this->redirectToRoute('product_show', ['id' => $product->getId()]);
    }
    return $this->render('product/edit.html.twig', [
        'product' => $product,
        'form' => $form->createView(),
    ]);
}
````
Certain code you'll find in nearly every action that uses a form:
- The form should handle the request
- Check for being submitted and valid,
- Get the entity manager and save the data.
- Add a flash message for having saved the data.


Using CrudController form-handling can be slimmed down a little, making it your
action more readable:

**When extending CrudController**
````php
/**
 * @Route("/{id}/edit", name="product_edit")
 * @Method({"GET", "POST"})
 */
public function editAction(Request $request, Product $product): Response
{
    $form = $this->createForm(ProductType::class, $product);
    if ($this->handleForm($form, $request)) {
        return $this->redirectToRoute('product_show', $product);
    }
    return $this->render('product/edit.html.twig', [
        'product' => $product,
        'form' => $form->createView(),
    ]);
}
````

### Example deleteAction

Extending the traditional Controller, our delete action might look like the code below.

**Traditional**
````php
/**
 * @Route("/{id}/delete", name="product_delete")
 * @Method({"GET", "DELETE"})
 */
public function deleteAction(Request $request, Product $product): Response
{
    $form = $this->createFormBuilder($product)->setMethod('DELETE')->getForm();
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
        if ($this->isDeletable($product)) {
            $em = $this->getEntityManager();
            $em->remove($product);
            $em->flush();
            $this->addFlash('success', static::MESSAGE_DELETED);
            $this->redirectToRoute('product_index');
        } else {
            $this->addFlash('danger', static::MESSAGE_CANNOT_BE_DELETED);
        }
    }
    return $this->render('product/delete.html.twig', [
        'product' => $product,
        'form' => $form->createView(),
    ]);
}
````

In this example we used an `isDeletable` check (which is yet to be implemented).
Alternatively you could use a try-catch in this action to make sure the user won't get 
a ForeignKeyConstraintViolationException.

Yet another approach may be using a check inside the voter, but that could be bad 
for performance when dealing with multiple records (e.g. in datatables).

Similar to the edit action, there are several parts of this code that are
standard for almost every delete action.


**Extending the CrudController**
````php
/**
 * @Route("/{id}/delete", name="product_delete")
 * @Method({"GET", "DELETE"})
 */
public function deleteAction(Request $request, Product $product): Response
{
    $form = $this->createDeleteForm($product);
    if ($this->handleDeleteForm($form, $request)) {
        return $this->redirectToRoute('product_index');
    }
    return $this->render('product/delete.html.twig', [
        'product' => $product,
        'form' => $form->createView(),
    ]);
}
````

By using the CrudController, standard code is refactored into the `handleDeleteForm`,
including dealing with an `isDeletable` that takes SoftDeleteable objects
 into consideration.