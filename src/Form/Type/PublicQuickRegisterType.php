<?php
/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 17.09.2019
 * Time: 20:29
 */

namespace App\Form\Type;

use App\Entity\FreeField;
use App\Entity\Rooms;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PublicQuickRegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('firstName', TextType::class, ['attr' => ['placeholder' => 'label.vorname'], 'label' => false, 'required' => true, 'translation_domain' => 'form'])
            ->add('lastName', TextType::class, ['attr' => ['placeholder' => 'label.nachname'], 'label' => false, 'required' => true, 'translation_domain' => 'form'])
            ->add('email', TextType::class, ['attr' => ['placeholder' => 'label.email'], 'label' => false, 'required' => true, 'translation_domain' => 'form'])
            ->add('subscribe', SubmitType::class, ['attr' => array('class' => 'btn btn-outline-secondary btn-block p-3'), 'label' => 'label.subscribe', 'translation_domain' => 'form']);
    }


}
