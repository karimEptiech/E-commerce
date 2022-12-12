<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\User;
use App\Entity\Cart;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

/**
 * @method Cart|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cart|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cart[]    findAll()
 * @method Cart[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Cart $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Cart $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
    public function removeProduct(Product $product = null, User $user, $limite = false)
    {
        if ($limite) {
            $sql = "delete from cart where product_id=:product_id and user_id=:user_id limit 1";
        } else {
            $sql = "delete from cart where user_id=:user_id";
        }

        $stmt = $this->_em->getConnection()->prepare($sql);


        $userid = $user->getId();
        $stmt->bindParam('user_id', $userid);
        if ($limite) {
            $productid = $product->getId();
            $stmt->bindParam('product_id', $productid);
        }
        $stmt->executeQuery();
        return (1);
    }



    public function addProductToCart(User $user, Product $product)
    {

        try {
            $sql = 'INSERT INTO cart (user_id, product_id) VALUES (:user_id, :product_id)';
            $stmt = $this->_em->getConnection()->prepare($sql);

            $productid = $product->getId();
            $userid = $user->getId();
            $stmt->bindParam('user_id', $userid);
            $stmt->bindParam('product_id', $productid);
            $stmt->executeQuery()->fetchAssociative();
        } catch (Exception $e) {
            $sql = 'select * from cart';
            $stmt = $this->_em->getConnection()->prepare($sql);
            $stmt->executeQuery()->fetchAssociative();
            return $stmt;
        }
    }
    // /**
    //  * @return Cart[] Returns an array of Cart objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Cart
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
