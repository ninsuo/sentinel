<?php

namespace App\Form;

use App\Entity\FeatureRun;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class FeatureRunPromptType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) : void
    {
        $builder->add('userPrompt', TextareaType::class, [
            'label' => 'Run prompt',
            'help' => 'Adjust the prompt for this run (iteration, fixes, new constraints, etc.).',
            'attr' => [
                'rows' => 14,
                'spellcheck' => 'false',
            ],
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver) : void
    {
        $resolver->setDefaults([
            'data_class' => FeatureRun::class,
        ]);
    }
}
