<?php
namespace App\Controller;
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Allow: GET, POST, OPTIONS, PUT, DELETE");
use App\Entity\User;
use App\Repository\UserRepository;
use \Firebase\JWT\JWT;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class SecurityController extends AbstractController
{
       /**
     * @Route("/api/login", name="login", methods={"POST"})
     */
    public function login(UserRepository $userRepository, UserPasswordEncoderInterface $encoder, Request $request): Response
    {
        $user = $userRepository->findOneBy([
            'email' => $request->get('email'),
            'password' => $request->get('password')
        ]);
        if (!$user) {
            return $this->json([
                'message' => 'email or password is wrong.', 403
            ]);
        }
        $token = random_int(10000000, 9000000000);
        $userRepository->upgradeToken($user, strval($token));
        return $this->json([
            'message' => 'success!',
            'token' => sprintf('%s', $token),
        ]);
    }

    public static function authUser($request, UserRepository $userRepository)
    {
        $authorizationHeader = explode(' ', $request->headers->get('Authorization'));
        if ($authorizationHeader[0] == 'Bearer') {
            $user = $userRepository->findOneBy(['apiToken' => $authorizationHeader[1]]);
            return $user ? $user : null;
        }
    }
}
