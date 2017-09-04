<?php

namespace AppBundle\FieldType\Mapper;

use eZ\Publish\API\Repository\FieldTypeService;
use EzSystems\RepositoryForms\Data\Content\FieldData;
use EzSystems\RepositoryForms\Data\FieldDefinitionData;
use EzSystems\RepositoryForms\FieldType\DataTransformer\FieldValueTransformer;
use EzSystems\RepositoryForms\FieldType\FieldDefinitionFormMapperInterface;
use EzSystems\RepositoryForms\FieldType\FieldValueFormMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ISBNFormMapper implements FieldDefinitionFormMapperInterface, FieldValueFormMapperInterface
{
    // Example: 978-1-4028-9462-6
    const ISBN_PATTERN = '^[0-9]*[-| ][0-9]*[-| ][0-9]*[-| ][0-9]*[-| ][0-9]*$';

    /**
     * @var \eZ\Publish\API\Repository\FieldTypeService
     */
    private $fieldTypeService;

    public function __construct(FieldTypeService $fieldTypeService)
    {
        $this->fieldTypeService = $fieldTypeService;
    }

    public function mapFieldDefinitionForm(FormInterface $fieldDefinitionForm, FieldDefinitionData $fieldDefinition)
    {
        $defaultValueForm = $fieldDefinitionForm
            ->getConfig()
            ->getFormFactory()
            ->createBuilder()
            ->create('defaultValue', TextType::class, [
                'required' => false,
                'label' => 'field_definition.ezisbn.default_value',
            ])
            ->addModelTransformer(new FieldValueTransformer($this->fieldTypeService->getFieldType($fieldDefinition->getFieldTypeIdentifier())))
            ->setAutoInitialize(false)
            ->getForm();
        $fieldDefinitionForm
            ->add(
                'isISBN13', CheckboxType::class, [
                    'required' => false,
                    'property_path' => 'fieldSettings[isISBN13]',
                    'label' => 'field_definition.ezisbn.is_isbn13',
                ]
            )
            ->add($defaultValueForm);
    }

    public function mapFieldValueForm(FormInterface $fieldForm, FieldData $data)
    {
        $fieldDefinition = $data->fieldDefinition;
        $formConfig = $fieldForm->getConfig();

        $valueField = $formConfig->getFormFactory()->createBuilder()
            ->create(
                'value',
                TextType::class,
                [
                    'required' => $fieldDefinition->isRequired,
                    'label' => $fieldDefinition->getName($formConfig->getOption('languageCode')),
                    'attr' => [
                        'pattern' => self::ISBN_PATTERN
                    ]
                ]
            )
            ->addModelTransformer(new FieldValueTransformer($this->fieldTypeService->getFieldType($fieldDefinition->fieldTypeIdentifier)))
            // Deactivate auto-initialize as we're not on the root form.
            ->setAutoInitialize(false)
            ->getForm();

        $fieldForm->add($valueField);
    }

    /**
     * Fake method to set the translation domain for the extractor.
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'translation_domain' => 'ezrepoforms_content_type',
            ]);
    }
}
