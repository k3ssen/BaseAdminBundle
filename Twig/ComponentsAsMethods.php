<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\Twig;

use Symfony\Component\Form\FormView;

class ComponentsAsMethods
{
    public function box(string $title = null)
    {
        return '@BaseAdmin/components/box.html.twig';
    }
    public function entity_box(string $title = null, string $entity = null, bool $vote = null)
    {
        return '@BaseAdmin/components/entity_box.html.twig';
    }
    public function entity_form_box(string $title = null, string $entity = null, bool $vote = null, FormView $form = null)
    {
        return '@BaseAdmin/components/entity_form_box.html.twig';
    }
}