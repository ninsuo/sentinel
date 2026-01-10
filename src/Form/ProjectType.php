<?php

namespace App\Form;

use App\Entity\Project;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) : void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 120),
                ],
            ])
            ->add('path', TextType::class, [
                'help' => 'Local filesystem path to the project directory.',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 65535),
                ],
            ])
            ->add('prompt', TextareaType::class, [
                'required' => false,
                'help' => 'Project system prompt injected into all AI interactions.',
                'attr' => ['rows' => 10],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver) : void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}
