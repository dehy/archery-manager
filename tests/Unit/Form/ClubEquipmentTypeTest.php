<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\DBAL\Types\ClubEquipmentType as ClubEquipmentTypeEnum;
use App\Entity\ClubEquipment;
use App\Form\ClubEquipmentType;
use Symfony\Component\Form\Test\TypeTestCase;

final class ClubEquipmentTypeTest extends TypeTestCase
{
    public function testSubmitBaseFieldsOnly(): void
    {
        $formData = [
            'type' => ClubEquipmentTypeEnum::QUIVER,
            'name' => 'Test Quiver',
            'serialNumber' => 'SN-001',
            'quantity' => 5,
            'notes' => 'Some notes',
        ];

        $equipment = new ClubEquipment();
        $form = $this->factory->create(ClubEquipmentType::class, $equipment);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertSame(ClubEquipmentTypeEnum::QUIVER, $equipment->getType());
        $this->assertSame('Test Quiver', $equipment->getName());
        $this->assertSame('SN-001', $equipment->getSerialNumber());
        $this->assertSame(5, $equipment->getQuantity());
        $this->assertSame('Some notes', $equipment->getNotes());
    }

    public function testSubmitBowTypeAddsBowFields(): void
    {
        $formData = [
            'type' => ClubEquipmentTypeEnum::BOW,
            'name' => 'Test Bow',
            'quantity' => 1,
            'bowType' => 'classique_competition',
            'brand' => 'Hoyt',
            'model' => 'Formula Xi',
            'limbSize' => '68',
            'limbStrength' => '28',
        ];

        $equipment = new ClubEquipment();
        $form = $this->factory->create(ClubEquipmentType::class, $equipment);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame(ClubEquipmentTypeEnum::BOW, $equipment->getType());
        $this->assertSame('Test Bow', $equipment->getName());
        $this->assertSame('Hoyt', $equipment->getBrand());
        $this->assertSame('Formula Xi', $equipment->getModel());
    }

    public function testSubmitArrowTypeAddsArrowFields(): void
    {
        $formData = [
            'type' => ClubEquipmentTypeEnum::ARROWS,
            'name' => 'Test Arrows',
            'quantity' => 1,
            'arrowType' => 'carbon',
            'arrowLength' => '28',
            'arrowSpine' => '500',
            'fletchingType' => 'spinwings',
        ];

        $equipment = new ClubEquipment();
        $form = $this->factory->create(ClubEquipmentType::class, $equipment);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame(ClubEquipmentTypeEnum::ARROWS, $equipment->getType());
        $this->assertSame('Test Arrows', $equipment->getName());
    }

    public function testBowAndArrowFieldsAlwaysPresentForJsToggle(): void
    {
        $formData = [
            'type' => ClubEquipmentTypeEnum::OTHER,
            'name' => 'Test Other',
            'quantity' => 1,
        ];

        $equipment = new ClubEquipment();
        $form = $this->factory->create(ClubEquipmentType::class, $equipment);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame(ClubEquipmentTypeEnum::OTHER, $equipment->getType());
        // Bow and arrow fields are always added so the JS toggle can show/hide them
        $this->assertTrue($form->has('bowType'));
        $this->assertTrue($form->has('arrowType'));
    }

    public function testFormAlwaysHasBowAndArrowFields(): void
    {
        // Fields are always present regardless of equipment type, so JS can toggle visibility
        $form = $this->factory->create(ClubEquipmentType::class);

        $this->assertTrue($form->has('bowType'));
        $this->assertTrue($form->has('brand'));
        $this->assertTrue($form->has('model'));
        $this->assertTrue($form->has('limbSize'));
        $this->assertTrue($form->has('limbStrength'));
        $this->assertTrue($form->has('arrowType'));
        $this->assertTrue($form->has('arrowLength'));
        $this->assertTrue($form->has('arrowSpine'));
        $this->assertTrue($form->has('fletchingType'));
    }

    public function testFormHasBaseFields(): void
    {
        $form = $this->factory->create(ClubEquipmentType::class);

        $this->assertTrue($form->has('type'));
        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('serialNumber'));
        $this->assertTrue($form->has('quantity'));
        $this->assertTrue($form->has('notes'));
        $this->assertTrue($form->has('purchasePrice'));
        $this->assertTrue($form->has('purchaseDate'));
    }

    public function testAllEquipmentTypesAreSubmittable(): void
    {
        $types = [
            ClubEquipmentTypeEnum::BOW,
            ClubEquipmentTypeEnum::ARROWS,
            ClubEquipmentTypeEnum::QUIVER,
            ClubEquipmentTypeEnum::ARMGUARD,
            ClubEquipmentTypeEnum::FINGER_TAB,
            ClubEquipmentTypeEnum::CHEST_GUARD,
            ClubEquipmentTypeEnum::OTHER,
        ];

        foreach ($types as $type) {
            $equipment = new ClubEquipment();
            $form = $this->factory->create(ClubEquipmentType::class, $equipment);
            $form->submit([
                'type' => $type,
                'name' => 'Equipment '.$type,
                'quantity' => 1,
            ]);

            $this->assertTrue($form->isSynchronized(), 'Form not synchronized for type: '.$type);
            $this->assertSame($type, $equipment->getType());
        }
    }
}
