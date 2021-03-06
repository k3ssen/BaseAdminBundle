<?php
declare(strict_types=1);

namespace  K3ssen\BaseAdminBundle\Controller;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManager;
use K3ssen\BaseAdminBundle\Model\SoftDeleteableInterface;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class CrudController implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    use ControllerTrait;

    protected const MESSAGE_SAVED = 'Entry has been saved.';
    protected const MESSAGE_DELETED = 'Entry has been deleted.';
    protected const MESSAGE_CANNOT_BE_DELETED = 'Entry cannot be deleted, because other entries depend on this entry.';

    protected function getParameter(string $name)
    {
        return $this->container->getParameter($name);
    }

    /**
     * Returns a RedirectResponse to the given route with the given parameters.
     * Overwrites Controller-trait to allow parameters as object as well.
     */
    protected function redirectToRoute(string $route, $parameters = [], int $status = 302): RedirectResponse
    {
        return $this->redirect($this->generateUrl($route, $parameters), $status);
    }

    /**
     * Generates a URL from the given parameters.
     * Overwrites Controller-trait to allow parameters as object as well.
     */
    protected function generateUrl(string $route, $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $this->container->get('router')->generate($route, $parameters, $referenceType);
    }

    /**
     * @return ObjectManager|EntityManager
     */
    protected function getEntityManager(): ObjectManager
    {
        return $this->getDoctrine()->getManager();
    }

    protected function createDeleteForm($object): FormInterface
    {
        return $this->createFormBuilder($object)
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    protected function handleForm(FormInterface $form, Request $request): bool
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->saveObject($form->getData());
            $this->addFlash('success', static::MESSAGE_SAVED);
            return true;
        }
        return false;
    }

    protected function handleDeleteForm(FormInterface $form, Request $request): bool
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $object = $form->getData();
            if ($this->isDeletable($object)) {
                $this->deleteObject($object);
                $this->addFlash('success', static::MESSAGE_DELETED);
                return true;
            } else {
                $this->addFlash('danger', static::MESSAGE_CANNOT_BE_DELETED);
            }
        }
        return false;
    }

    protected function saveObject($object): void
    {
        $em = $this->getEntityManager();
        $em->persist($object);
        $em->flush();
    }

    protected function deleteObject($object): void
    {
        $em = $this->getEntityManager();
        $em->remove($object);
        $em->flush();
    }

    /**
     * Tries to delete an object without actually deleting it.
     * Returns false if ForeignKeyConstraintViolationException would be thrown; true otherwise.
     */
    protected function isDeletable($object): bool
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();
        try {
            // if an object is softdeleteable, then set 'deletedAt' first, so that a 'hard-delete' will be attempted.
            if ($object instanceof SoftDeleteableInterface) {
                $object->setDeletedAt(new \DateTime());
            }
            $em->remove($object);
            $em->flush();
            $em->rollback();
            return true;
        } catch (ForeignKeyConstraintViolationException $exception) {
            $em->rollback();
            return false;
        }
    }
}
