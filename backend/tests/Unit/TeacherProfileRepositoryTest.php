<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Repositories\TeacherProfileRepository;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class TeacherProfileRepositoryTest extends TestCase
{
    public function testFindByUserIdReturnsNullWhenNoProfileExists(): void
    {
        $userId = (new UserRepository())->create('No Profile Teacher', '+998987770001', 'x', 'teacher');

        $repo = new TeacherProfileRepository();

        $this->assertNull($repo->findByUserId($userId));
    }

    public function testUpsertInsertsThenUpdatesOptionalFields(): void
    {
        $userId = (new UserRepository())->create('Profile Teacher', '+998987770002', 'x', 'teacher');

        $repo = new TeacherProfileRepository();
        $repo->upsert($userId, [
            'age' => 40,
            'experience_years' => 12,
            'skills_uz' => 'Ekskavator boshqarish',
            'telegram' => '@teacher',
        ]);

        $profile = $repo->findByUserId($userId);
        $this->assertNotNull($profile);
        $this->assertSame(40, (int) $profile['age']);
        $this->assertSame(12, (int) $profile['experience_years']);
        $this->assertSame('Ekskavator boshqarish', $profile['skills_uz']);
        $this->assertNull($profile['email']);

        $repo->upsert($userId, ['age' => 41, 'email' => 'teacher@example.com']);

        $updated = $repo->findByUserId($userId);
        $this->assertSame(41, (int) $updated['age']);
        $this->assertSame('teacher@example.com', $updated['email']);
        $this->assertNull($updated['telegram']);

        $count = Database::pdo()->prepare('SELECT COUNT(*) FROM teacher_profiles WHERE user_id = ?');
        $count->execute([$userId]);
        $this->assertSame('1', (string) $count->fetchColumn());
    }

    public function testUpsertAllowsAllOptionalFieldsToBeOmitted(): void
    {
        $userId = (new UserRepository())->create('Minimal Teacher', '+998987770003', 'x', 'teacher');

        $repo = new TeacherProfileRepository();
        $repo->upsert($userId, []);

        $profile = $repo->findByUserId($userId);
        $this->assertNotNull($profile);
        $this->assertNull($profile['age']);
        $this->assertNull($profile['skills_uz']);
    }
}
