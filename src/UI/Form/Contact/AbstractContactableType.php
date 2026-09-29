<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form\Contact;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @template TData of object|null
 *
 * @extends AbstractType<TData>
 */
abstract class AbstractContactableType extends AbstractType
{
    /** @param FormBuilderInterface<TData> $builder */
    protected function addWebsite(FormBuilderInterface $builder): void
    {
        $builder->add('website', UrlType::class, ['required' => false, 'label' => 'Website']);
    }

    /** @param FormBuilderInterface<TData> $builder */
    protected function addDirectContacts(FormBuilderInterface $builder): void
    {
        $builder->add('directContacts', CollectionType::class, [
            'entry_type' => DirectContactType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'required' => false,
            'label' => 'Direct contacts',
        ]);
    }
}
