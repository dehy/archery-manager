<?php

namespace App\Tests\Unit;

use App\Entity\Group;
use App\Entity\Licensee;
use App\Entity\Club;
use App\Type\GenderType;
use PHPUnit\Framework\TestCase;

class GroupTest extends TestCase
{
    public function testGroupCreation(): void
    {
        $group = new Group();
        $group->name = 'Advanced Archers';
        $group->description = 'A group for experienced archers';

        $this->assertEquals('Advanced Archers', $group->name);
        $this->assertEquals('A group for experienced archers', $group->description);
    }

    public function testGroupToString(): void
    {
        $group = new Group();
        $group->name = 'Beginners Group';

        $this->assertEquals('Beginners Group', (string) $group);
    }

    public function testGroupMemberManagement(): void
    {
        $group = new Group();
        $group->name = 'Test Group';

        // Test initial empty collection
        $this->assertCount(0, $group->licensees);

        // Create and add members
        $member1 = new Licensee();
        $member1->familyName = 'Smith';
        $member1->givenName = 'John';
        $member1->gender = GenderType::Male;
        $member1->birthDate = new \DateTimeImmutable('1990-01-01');

        $member2 = new Licensee();
        $member2->familyName = 'Doe';
        $member2->givenName = 'Jane';
        $member2->gender = GenderType::Female;
        $member2->birthDate = new \DateTimeImmutable('1992-05-15');

        $group->licensees->add($member1);
        $group->licensees->add($member2);

        $this->assertCount(2, $group->licensees);
        $this->assertTrue($group->licensees->contains($member1));
        $this->assertTrue($group->licensees->contains($member2));
    }

    public function testGroupClubRelationship(): void
    {
        $club = new Club();
        $club->name = 'Elite Club';

        $group = new Group();
        $group->name = 'Elite Team';
        $group->club = $club;

        $this->assertEquals($club, $group->club);
        $this->assertEquals('Elite Club', $group->club->name);
    }

    public function testGroupMemberRelationship(): void
    {
        $group = new Group();
        $group->name = 'Elite Team';

        $licensee = new Licensee();
        $licensee->familyName = 'Champion';
        $licensee->givenName = 'Ace';
        $licensee->gender = GenderType::Other;
        $licensee->birthDate = new \DateTimeImmutable('1988-12-25');

        // Add licensee to group
        $group->licensees->add($licensee);
        $licensee->groups->add($group);

        // Test bidirectional relationship
        $this->assertTrue($group->licensees->contains($licensee));
        $this->assertTrue($licensee->groups->contains($group));
        $this->assertEquals('Elite Team', $licensee->groups->first()->name);
    }

    public function testGroupDescription(): void
    {
        $group = new Group();
        $group->name = 'Youth Group';
        $group->description = 'A group for young archers under 18 years old';

        $this->assertEquals('A group for young archers under 18 years old', $group->description);
    }

    public function testGroupMemberCount(): void
    {
        $group = new Group();
        $group->name = 'Counting Group';

        // Add 3 members
        for ($i = 1; $i <= 3; $i++) {
            $member = new Licensee();
            $member->familyName = "Member{$i}";
            $member->givenName = "Test";
            $member->gender = GenderType::Other;
            $member->birthDate = new \DateTimeImmutable('1990-01-01');
            
            $group->licensees->add($member);
        }

        $this->assertCount(3, $group->licensees);
        $this->assertEquals(3, $group->licensees->count());
    }

    public function testGroupValidation(): void
    {
        $group = new Group();
        
        // Test required fields
        $group->name = 'Valid Group';
        
        $this->assertNotEmpty($group->name);
        
        // Test optional fields
        $this->assertNull($group->description);
    }
}
