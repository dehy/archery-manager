<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Form\Type\LicenseeUserLinkType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

final class LicenseeUserLinkTypeTest extends KernelTestCase
{
    private FormFactoryInterface $formFactory;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();
        $this->formFactory = self::getContainer()->get(FormFactoryInterface::class);
    }

    public function testFormHasExpectedFields(): void
    {
        $form = $this->formFactory->create(LicenseeUserLinkType::class);

        $this->assertTrue($form->has('user_choice'));
        $this->assertTrue($form->has('existing_user'));
        $this->assertTrue($form->has('email'));
    }

    public function testUserChoiceDefaultsToNew(): void
    {
        $form = $this->formFactory->create(LicenseeUserLinkType::class);

        $this->assertSame('new', $form->get('user_choice')->getData());
    }

    public function testUserChoiceHasCorrectOptions(): void
    {
        $form = $this->formFactory->create(LicenseeUserLinkType::class);

        $userChoiceField = $form->get('user_choice');
        $config = $userChoiceField->getConfig();
        $options = $config->getOptions();

        $this->assertArrayHasKey('choices', $options);
        $this->assertContains('existing', $options['choices']);
        $this->assertContains('new', $options['choices']);
    }

    public function testUserChoiceIsExpanded(): void
    {
        $form = $this->formFactory->create(LicenseeUserLinkType::class);

        $config = $form->get('user_choice')->getConfig();
        $this->assertTrue($config->getOption('expanded'));
    }

    public function testEmailFieldIsNotRequired(): void
    {
        $form = $this->formFactory->create(LicenseeUserLinkType::class);

        $emailConfig = $form->get('email')->getConfig();
        $this->assertFalse($emailConfig->getRequired());
    }

    public function testEmailFieldIsNotMapped(): void
    {
        $form = $this->formFactory->create(LicenseeUserLinkType::class);

        $emailConfig = $form->get('email')->getConfig();
        $this->assertFalse($emailConfig->getMapped());
    }

    public function testExistingUserFieldIsNotRequired(): void
    {
        $form = $this->formFactory->create(LicenseeUserLinkType::class);

        $existingUserConfig = $form->get('existing_user')->getConfig();
        $this->assertFalse($existingUserConfig->getRequired());
    }

    public function testSubmitWithNewUserChoice(): void
    {
        $form = $this->formFactory->create(LicenseeUserLinkType::class);

        $form->submit([
            'user_choice' => 'new',
            'email' => 'newuser@example.com',
            'existing_user' => '',
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame('new', $form->get('user_choice')->getData());
        $this->assertSame('newuser@example.com', $form->get('email')->getData());
    }

    public function testSubmitWithExistingUserChoice(): void
    {
        $form = $this->formFactory->create(LicenseeUserLinkType::class);

        $form->submit([
            'user_choice' => 'existing',
            'existing_user' => '',
            'email' => '',
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame('existing', $form->get('user_choice')->getData());
    }

    public function testPreSubmitEventModifiesFieldsForExistingChoice(): void
    {
        $form = $this->formFactory->create(LicenseeUserLinkType::class);

        $form->submit([
            'user_choice' => 'existing',
            'existing_user' => '',
            'email' => '',
        ]);

        // After submit with 'existing', existing_user should be required
        $existingUserConfig = $form->get('existing_user')->getConfig();
        $this->assertTrue($existingUserConfig->getRequired());
    }

    public function testPreSubmitEventModifiesFieldsForNewChoice(): void
    {
        $form = $this->formFactory->create(LicenseeUserLinkType::class);

        $form->submit([
            'user_choice' => 'new',
            'email' => 'test@example.com',
            'existing_user' => '',
        ]);

        // After submit with 'new', email should be required
        $emailConfig = $form->get('email')->getConfig();
        $this->assertTrue($emailConfig->getRequired());
    }
}
