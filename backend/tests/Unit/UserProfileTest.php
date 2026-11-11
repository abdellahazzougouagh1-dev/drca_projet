<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\UserController;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    public function test_user_model_allows_profile_fields_to_be_filled(): void
    {
        $user = new User();

        $this->assertContains('telephone', $user->getFillable());
        $this->assertContains('avatar', $user->getFillable());
        $this->assertContains('name', $user->getFillable());
        $this->assertContains('email', $user->getFillable());
    }

    public function test_encode_uploaded_avatar_encodes_valid_png_as_data_uri(): void
    {
        $controller = new UserController();
        $method = new \ReflectionMethod(UserController::class, 'encodeUploadedAvatar');
        $method->setAccessible(true);

        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $file = UploadedFile::fake()->createWithContent('avatar.png', $pngBytes);

        $dataUri = $method->invoke($controller, $file);

        $this->assertIsString($dataUri);
        $this->assertStringStartsWith('data:image/png;base64,', $dataUri);
    }

    public function test_encode_uploaded_avatar_rejects_svg_or_non_raster_payload(): void
    {
        $controller = new UserController();
        $method = new \ReflectionMethod(UserController::class, 'encodeUploadedAvatar');
        $method->setAccessible(true);

        $svgFile = UploadedFile::fake()->createWithContent('evil.svg', '<svg><script>alert(1)</script></svg>');

        $this->expectException(ValidationException::class);
        $method->invoke($controller, $svgFile);
    }

    public function test_director_identification_and_user_management_helpers(): void
    {
        $controller = new UserController();
        $isDirector = new \ReflectionMethod(UserController::class, 'isDirector');
        $canManage = new \ReflectionMethod(UserController::class, 'canManageUsers');
        $isDirector->setAccessible(true);
        $canManage->setAccessible(true);

        $director = new User(['role' => 'directeur', 'is_admin' => false]);
        $directorCase = new User(['role' => ' Directeur ', 'is_admin' => false]);
        $adminGestionnaire = new User(['role' => 'gestionnaire', 'is_admin' => true]);
        $regularUser = new User(['role' => 'gestionnaire', 'is_admin' => false]);

        $this->assertTrue($isDirector->invoke($controller, $director));
        $this->assertTrue($isDirector->invoke($controller, $directorCase));
        $this->assertFalse($isDirector->invoke($controller, $adminGestionnaire));
        $this->assertFalse($isDirector->invoke($controller, $regularUser));

        $this->assertTrue($canManage->invoke($controller, $director));
        $this->assertTrue($canManage->invoke($controller, $adminGestionnaire));
        $this->assertFalse($canManage->invoke($controller, $regularUser));
        $this->assertFalse($canManage->invoke($controller, null));
    }
}
