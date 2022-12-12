<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegisterController extends AbstractController
{
    /**
     * @Route("/api/register", name="register", methods={"POST"})
     */

    public function register(Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $encoder, ValidatorInterface $validator): Response
    {
        $User = new User();
        $email = $User->setEmail($request->get('email'));
        $User->setPassword($request->get('password'));
        $User->setRoles($User->getRoles());
        $User->setFirstname($request->get('firstname'));
        $User->setLastname($request->get('lastname'));


        if (count($validator->validate($User)) > 0) {
            return $this->json('error parameter', 417);
        } else {
            $repo = $manager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($repo != null) {
                return $this->json('error mail already exist', 409);
            } else {
                $manager->persist($User);
                $manager->flush();
                return $this->json('user ' . $User->getEmail() . ' is create', 201);
            }
        }
    }
}
