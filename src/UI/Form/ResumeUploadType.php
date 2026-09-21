<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form;

use CurlySanders\JobApplicationTracker\UI\Form\Model\ResumeUploadData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;

/** @extends AbstractType<ResumeUploadData> */
final class ResumeUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('resume', FileType::class, [
            'label' => 'Resume (PDF or DOCX, max 5 MB)',
            'constraints' => [
                new NotNull(message: 'Choose a resume to upload.'),
                new File(
                    maxSize: '5M',
                    mimeTypes: ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                    mimeTypesMessage: 'Upload a PDF or DOCX resume.',
                ),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ResumeUploadData::class]);
    }
}
