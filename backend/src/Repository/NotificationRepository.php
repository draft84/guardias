<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function save(Notification $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Notification $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Notification[]
     */
    public function findByUser(User $user, bool $unreadOnly = false, int $limit = 20): array
    {
        $userId = $user->getId();
        
        error_log('🔔 FIND BY USER - User ID: ' . $userId);
        error_log('🔔 FIND BY USER - User email: ' . $user->getEmail());
        
        // Usar consulta nativa con UUID convertido correctamente
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = "SELECT n.id, n.type, n.title, n.message, n.data, n.`read`, n.created_at, n.read_at, n.user_id 
                FROM notifications n 
                WHERE HEX(n.user_id) = :userIdHex";
        
        if ($unreadOnly) {
            $sql .= " AND n.`read` = 0";
        }
        
        $sql .= " ORDER BY n.created_at DESC LIMIT " . (int) $limit;
        
        // Convertir UUID a hex sin guiones
        $userIdHex = str_replace('-', '', strtoupper(bin2hex($userId->toBinary())));
        error_log('🔔 FIND BY USER - User ID Hex: ' . $userIdHex);
        error_log('🔔 FIND BY USER - SQL: ' . $sql);
        
        $rows = $conn->fetchAllAssociative($sql, ['userIdHex' => $userIdHex]);
        
        error_log('🔔 FIND BY USER - Rows found: ' . count($rows));
        
        // Convertir rows a entidades Notification
        $notifications = [];
        foreach ($rows as $row) {
            $notif = new Notification();
            // Usar reflection para setear propiedades privadas
            $reflection = new \ReflectionClass($notif);
            
            $idProp = $reflection->getProperty('id');
            $idProp->setAccessible(true);
            $idProp->setValue($notif, \Symfony\Component\Uid\Uuid::fromString($row['id']));
            
            $typeProp = $reflection->getProperty('type');
            $typeProp->setAccessible(true);
            $typeProp->setValue($notif, $row['type']);
            
            $titleProp = $reflection->getProperty('title');
            $titleProp->setAccessible(true);
            $titleProp->setValue($notif, $row['title']);
            
            $messageProp = $reflection->getProperty('message');
            $messageProp->setAccessible(true);
            $messageProp->setValue($notif, $row['message']);
            
            $dataProp = $reflection->getProperty('data');
            $dataProp->setAccessible(true);
            $dataProp->setValue($notif, $row['data'] ? json_decode($row['data'], true) : null);
            
            $readProp = $reflection->getProperty('isRead');
            $readProp->setAccessible(true);
            $readProp->setValue($notif, (bool) $row['read']);
            
            $createdAtProp = $reflection->getProperty('createdAt');
            $createdAtProp->setAccessible(true);
            $createdAtProp->setValue($notif, new \DateTime($row['created_at']));
            
            if ($row['read_at']) {
                $readAtProp = $reflection->getProperty('readAt');
                $readAtProp->setAccessible(true);
                $readAtProp->setValue($notif, new \DateTime($row['read_at']));
            }
            
            $notifications[] = $notif;
        }
        
        return $notifications;
    }

    public function countUnread(User $user): int
    {
        $userId = $user->getId();
        
        error_log('🔔 COUNT UNREAD - User ID: ' . $userId);
        error_log('🔔 COUNT UNREAD - User email: ' . $user->getEmail());
        
        // Usar consulta nativa con UUID convertido correctamente
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT COUNT(*) FROM notifications n WHERE HEX(n.user_id) = :userIdHex AND n.`read` = 0";
        
        // Convertir UUID a hex sin guiones
        $userIdHex = str_replace('-', '', strtoupper(bin2hex($userId->toBinary())));
        error_log('🔔 COUNT UNREAD - User ID Hex: ' . $userIdHex);
        
        $count = (int) $conn->fetchOne($sql, ['userIdHex' => $userIdHex]);
        
        error_log('🔔 COUNT UNREAD - Result: ' . $count);
        
        return $count;
    }
}
