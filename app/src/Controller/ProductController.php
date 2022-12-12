<?php
namespace App\Controller;
header('Access-Control-Allow-Origin: *');
use App\Entity\Order;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Repository\ProductRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class ProductController extends AbstractController
{
    /**
     * 
     * @Route("/api/product", name="add_product")
     */
    public function addProduct(Request $request, EntityManagerInterface $manager, ValidatorInterface $validator): Response
    {
        $user = SecurityController::authUser($request, $manager->getRepository(User::class));
        if ($user == null) return $this->json(['message' => '401'], 401);

        $product = new Product();
        $product->setName($request->query->get('name'));
        $product->setDescription($request->query->get('description'));
        $product->setPhoto($request->query->get('photo'));
        $product->setPrice($request->query->get('price'));

        if (count($validator->validate($product)) > 0) {
            return $this->json('error parameter', 417);
        } else {
            $repo = $manager->getRepository(Product::class)->add($product);
            return $this->json('ressource created', 201);
        }
    }

    /**
     * 
     * @Route("/api/products", name="list_products")
     */
    public function listProducts(ProductRepository $productRepository): Response
    {
        $productList = $productRepository->findAll();
        $encoders = array(new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());

        $serializer = new Serializer($normalizers, $encoders);
        $productSerialized = $serializer->serialize($productList, 'json');
        return new Response($productSerialized);
    }

    /**
     * 
     * @Route("/api/products/{id<\d+>}", name="get_product")
     */
    public function getProductById($id, ProductRepository $productRepository, Request $request, EntityManagerInterface $manager): Response
    {
        if ($request->getMethod() == 'GET') {
            $product = $productRepository->findOneBy(['id' => $id]);
            if ($product == null) {
                return $this->json([], 404);
            }
            $encoders = array(new JsonEncoder());
            $normalizers = array(new ObjectNormalizer());

            $serializer = new Serializer($normalizers, $encoders);
            $productSerialized = $serializer->serialize($product, 'json', ['groups' => ['normal']]);
            return new Response($productSerialized);
        }

        $user = SecurityController::authUser($request, $manager->getRepository(User::class));
        if ($user == null) return $this->json(['message' => '401'], 401);

        $product = $productRepository->findOneBy(['id' => $id]);
        if ($product == null) {
            return $this->json([], 404);
        }

        if ($request->getMethod() == 'DELETE') {
            $productRepository->remove($product);
            return $this->json("Ressource supprimée", 200);
        }

        if ($request->getMethod() == 'PUT') {
            $productRepository->updateProduct(
                $product,
                $request->get('name', $product->getName()),
                $request->get('price', $product->getPrice()),
                $request->get('description', $product->getDescription()),
                $request->get('photo', $product->getPhoto())
            );
            return $this->json("Ressource mit à jour avec succès", 200);
        }
    }


    /**
     * @Route("/api/cart/{id<\d+>}", name="add_cart_product")
     */
    public function addProductToCart($id, CartRepository $cartRepository, Request $request, EntityManagerInterface $manager, ProductRepository $productRepository)
    {
        $user = SecurityController::authUser($request, $manager->getRepository(User::class));
        if ($user == null) return $this->json(['message' => '401'], 401);
        $product = $productRepository->findOneBy(['id' => $id]);
        if ($request->getMethod() == 'GET' || $request->getMethod() == 'POST') {
            return $this->json('method not allowed', 405);
        }
        if ($product == null) return $this->json('error', 404);
        if ($request->getMethod() == 'PUT') {
            $cartRepository->addProductToCart($user, $product);
            return $this->json('product add to cart');
        }

        if ($request->getMethod() == 'DELETE') {
            $cartRepository->removeProduct($product, $user, true);
            return $this->json('remove ok', 200);
        }
    }

    /**
     * @Route("/api/cart", name="list_cart")
     */
    public function listCart(ProductRepository $productRepository, CartRepository $cartRepository, Request $request, EntityManagerInterface $manager)
    {
        $user = SecurityController::authUser($request, $manager->getRepository(User::class));
        if ($user == null) return $this->json(['message' => '401'], 401);
        $cartList = $cartRepository->findBy(['user_id' => $user->getId()]);
        $encoders = array(new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $cartSerialized = '[';
        if ($cartList == null) {
            return new Response($cartSerialized . ']');
        }
        foreach ($cartList as $cart) {
            $cartSerialized .= $serializer->serialize($productRepository->findOneBy(['id' => $cart->getProductId()]), 'json');
            $cartSerialized .= ',';
        }
        $cartSerialized = substr($cartSerialized, 0, -1);
        return new Response($cartSerialized . ']');
    }


    /**
     * @Route("/api/cart/validate", name="validate_cart")
     */
    public function validateCart(ProductRepository $productRepository, OrderRepository $orderRepository, CartRepository $cartRepository, Request $request, EntityManagerInterface $manager)
    {
        $user = SecurityController::authUser($request, $manager->getRepository(User::class));
        if ($user == null) return $this->json(['message' => '401'], 401);

        $cartList = $cartRepository->findBy(['user_id' => $user->getId()]);
        if ($cartList == null) {
            return $this->json('empty cart', 404);
        }
        $encoders = array(new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $cartSerialized = '[';
        $price = 0;
        foreach ($cartList as $cart) {
            $product = $productRepository->findOneBy(['id' => $cart->getProductId()]);
            $cartSerialized .= $serializer->serialize($product, 'json');
            $cartSerialized .= ',';
            $price += $product->getPrice();
        }
        $cartSerialized = substr($cartSerialized, 0, -1);

        $order = new Order();
        $order->setProducts($cartSerialized);
        $date = new DateTime('now');
        $order->setCreationDate($date->format('d-m-Y H:i:s'));
        $order->setTotalPrice($price);
        $order->setCreatedBy($user->getId());
        try {
            $orderRepository->add($order);
            $cartRepository->removeProduct(null, $user);
            return $this->json('cart validated', 200);
        } catch (Exception $e) {
            return $this->json('error internal server', 500);
        }
    }


    /**
     * 
     * @Route("/api/orders", name="retrieve_orders")
     */
    public function getOrder(Request $request, EntityManagerInterface $manager, OrderRepository $orderRepository, ValidatorInterface $validator): Response
    {
        $user = SecurityController::authUser($request, $manager->getRepository(User::class));
        if ($user == null) return $this->json(['message' => '401'], 401);
        $orderList = $orderRepository->findBy(['createdBy' => $user->getId()]);
        $encoders = array(new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());

        $serializer = new Serializer($normalizers, $encoders);
        $orderSerialized = $serializer->serialize($orderList, 'json');
        return new Response($orderSerialized);
    }

    /**
     * 
     * @Route("/api/order/{id<\d+>}", name="retrieve_one_orders")
     */
    public function getOrderById($id, Request $request, EntityManagerInterface $manager, OrderRepository $orderRepository, ValidatorInterface $validator): Response
    {
        $user = SecurityController::authUser($request, $manager->getRepository(User::class));
        if ($user == null) return $this->json(['message' => '401'], 401);
        $orderList = $orderRepository->findOneBy(['id' => $id]);
        $encoders = array(new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());

        $serializer = new Serializer($normalizers, $encoders);
        $orderSerialized = $serializer->serialize($orderList, 'json');
        return new Response($orderSerialized);
    }
}
