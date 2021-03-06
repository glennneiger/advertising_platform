<?php
/**
 * Message repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Utils\Paginator;

/**
 * Class MessageRepository.
 *
 * @package Repository
 */
class MessageRepository
{
    /**
     * Number of items per page.
     *
     * const int NUM_ITEMS
     */
    const NUM_ITEMS = 10;

    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * MessageRepository constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Fetch all records.
     *
     * @return array Result
     */
    public function findAll()
    {
        $queryBuilder = $this->queryAll();

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Get records paginated.
     *
     * @param array $user Current user
     * @param int   $page Current page number
     *
     * @return array Result
     */
    public function findAllPaginated($user, $page = 1)
    {
        $countQueryBuilder = $this->queryAll()
            ->select('COUNT(DISTINCT c.id) AS total_results')
            ->where('c.owner_id = :id')
            ->orWhere('c.user_id = :id')
            ->setParameter(':id', $user['id'], \PDO::PARAM_INT)
            ->setMaxResults(1);

        $paginator = new Paginator(
            $this->queryAll()->where('c.owner_id = :id')
                             ->orWhere('c.user_id = :id')
                             ->orderBy('c.id', 'desc')
                             ->setParameter(':id', $user['id'], \PDO::PARAM_INT),
            $countQueryBuilder
        );
        $paginator->setCurrentPage($page);
        $paginator->setMaxPerPage(self::NUM_ITEMS);

        return $paginator->getCurrentPageResults();
    }

    /**
     * Get records paginated.
     *
     * @param array $conversation Current conversation
     * @param int   $page         Current page number
     *
     * @return array Result
     */
    public function findMessagesPaginated($conversation, $page = 1)
    {
        $countQueryBuilder = $this->queryAllMessages()
            ->select('COUNT(DISTINCT m.id) AS total_results')
            ->where('m.conversation_id = :id')
            ->setParameter(':id', $conversation['id'], \PDO::PARAM_INT)
            ->setMaxResults(1);

        $paginator = new Paginator(
            $this->queryAllMessages()->where('m.conversation_id = :id')
                                     ->orderBy('m.id', 'desc')
                                     ->setParameter(':id', $conversation['id'], \PDO::PARAM_INT),
            $countQueryBuilder
        );
        $paginator->setCurrentPage($page);
        $paginator->setMaxPerPage(self::NUM_ITEMS);

        return $paginator->getCurrentPageResults();
    }

    /**
     * Find one record.
     *
     * @param string $id Element id
     *
     * @return array|mixed Result
     */
    public function findOneById($id)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('c.id = :id')
                     ->setParameter(':id', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }

    /**
     * Find one record.
     *
     * @param array $user   User
     * @param array $advert Advert
     *
     * @return array|mixed Result
     */
    public function findOneByUserAndAdvert($user, $advert)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('c.user_id = :user')
                     ->andWhere('c.advert_id = :advert')
                     ->setParameter(':user', $user['id'], \PDO::PARAM_INT)
                     ->setParameter(':advert', $advert['id'], \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }

    /**
     * Save record.
     *
     * @param array   $data   Message
     * @param array   $advert Advert
     * @param integer $userId Advert
     *
     * @return boolean Result
     */
    public function saveFirst($data, $advert, $userId)
    {
        $conversation['topic'] = '#' . $advert['id'] . ' - ' . $advert['topic'];
        $conversation['owner_id'] = $advert['user_id'];
        $conversation['user_id'] = $userId;
        $conversation['advert_id'] = $advert['id'];
        $this->db->insert('si_conversations', $conversation);
        $conversationId = $this->db->lastInsertId();

        unset($data['topic']);
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['user_id'] = $userId;
        $data['conversation_id'] = $conversationId;

        return $this->db->insert('si_messages', $data);
    }

    /**
     * Save record.
     *
     * @param array   $data         Message
     * @param array   $conversation Conversation
     * @param integer $userId       User
     *
     * @return boolean Result
     */
    public function save($data, $conversation, $userId)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['user_id'] = $userId;
        $data['conversation_id'] = $conversation['id'];

        return $this->db->insert('si_messages', $data);
    }

    /**
     * Check if it is first message.
     *
     * @param array   $advert Advert
     * @param integer $userId Advert
     *
     * @return boolean Result
     */
    public function isFirstMessage($advert, $userId)
    {
        $countQueryBuilder = $this->queryAll()
            ->select('COUNT(c.id) AS total')
            ->where('c.user_id = :user_id')
            ->andWhere('c.owner_id = :owner_id')
            ->andWhere('c.advert_id = :advert_id')
            ->setParameter(':user_id', $userId, \PDO::PARAM_INT)
            ->setParameter(':owner_id', $advert['user_id'], \PDO::PARAM_INT)
            ->setParameter(':advert_id', $advert['id'], \PDO::PARAM_INT);
        $result = $countQueryBuilder->execute()->fetch();

        return !current($result);
    }

    /**
     * Check if user can answer.
     *
     * @param array   $advert Advert
     * @param integer $userId Advert
     *
     * @return boolean Result
     */
    public function canAnswer($advert, $userId)
    {
        $countQueryBuilder = $this->queryAll()
            ->select('COUNT(c.id) AS total')
            ->where('c.user_id = :user_id')
            ->andWhere('c.owner_id = :owner_id')
            ->andWhere('c.advert_id = :advert_id')
            ->setParameter(':user_id', $userId, \PDO::PARAM_INT)
            ->setParameter(':owner_id', $advert['user_id'], \PDO::PARAM_INT)
            ->setParameter(':advert_id', $advert['id'], \PDO::PARAM_INT);
        $result = $countQueryBuilder->execute()->fetch();

        return !current($result);
    }

    /**
     * Check if user can view conversation.
     *
     * @param array   $advert Advert
     * @param integer $userId Advert
     *
     * @return boolean Result
     */
    public function canView($conversation, $user)
    {
        $countQueryBuilder = $this->queryAll()
            ->select('COUNT(c.id) AS total')
            ->where('c.id = :conversation_id')
            ->andWhere('c.user_id = :user_id')
            ->orWhere('c.owner_id = :user_id')
            ->andWhere('c.id = :conversation_id')
            ->setParameter(':user_id', $user['id'], \PDO::PARAM_INT)
            ->setParameter(':conversation_id', $conversation['id'], \PDO::PARAM_INT);
        $result = $countQueryBuilder->execute()->fetch();

        return current($result);
    }

    /**
     * Query all records.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('c.id', 'c.topic', 'c.user_id', 'c.owner_id', 'c.advert_id')
                     ->from('si_conversations', 'c');
    }

    /**
     * Query all records.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAllMessages()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('m.id', 'm.content', 'm.user_id', 'm.conversation_id', 'm.created_at', 'u.login')
                     ->from('si_messages', 'm')
                     ->join('m', 'si_users', 'u', 'u.id = m.user_id');
    }
}
