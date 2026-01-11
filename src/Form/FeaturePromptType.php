<?php

namespace App\Form;

use App\Entity\Feature;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class FeaturePromptType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) : void
    {
        $builder->add('prompt', TextareaType::class, [
            'label' => 'Feature prompt',
            'help' => 'This is the goal/spec for this feature. Runs reuse it unless you override it later.',
            'attr' => [
                'rows' => 16,
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
            'data_class' => Feature::class,
        ]);
    }
}
