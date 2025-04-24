<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Service\ImageUploader;
use App\Service\ImageUploaderService;

#[Route('/api/users')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository
    ) {}

    #[Route('', name: 'api_users_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $users = $this->userRepository->findAll();
        return $this->json($users);
    }

    #[Route('', name: 'api_users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $user = new User();
        $user->setFirstName($data['firstName'] ?? '');
        $user->setLastName($data['lastName'] ?? '');

        // Validación básica a mano (después usamos Validator Component)
        if (empty($user->getFirstName()) || empty($user->getLastName())) {
            return $this->json(['error' => 'Campos requeridos'], 400);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $this->json($user, 201);
    }

    #[Route('/{id}', name: 'api_users_get', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        return $this->json($user);
    }

    #[Route('/{id}', name: 'api_users_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }

        $this->em->flush();

        return $this->json($user);
    }

    #[Route('/{id}', name: 'api_users_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        $this->em->remove($user);
        $this->em->flush();

        return $this->json(['message' => 'Usuario eliminado'], 204);
    }

    #[Route('/{id}/upload-image', name: 'api_users_upload_image', methods: ['POST'])]
    public function uploadImage(int $id, Request $request, ImageUploaderService $uploader): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        /** @var UploadedFile $file */
        $file = $request->files->get('image');

        if (!$file || !$file->isValid()) {
            return $this->json(['error' => 'Imagen inválida o no enviada'], 400);
        }

        $imagePath = $uploader->upload($file);
        $user->setProfileImage($imagePath);

        $this->em->flush();

        return $this->json(['message' => 'Imagen subida', 'path' => $imagePath]);
    }
}
