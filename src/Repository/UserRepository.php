<?php
/**
 * User repository
 */

namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Utils\Paginator;

/**
 * Class UserRepository.
 *
 * @package Repository
 */
class UserRepository
{
    /**
     * Number of items per page.
     *
     * const int NUM_ITEMS
     */
    const NUM_ITEMS = 5;

    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * TagRepository constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Loads user by login.
     *
     * @param string $login User login
     * @throws UsernameNotFoundException
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function loadUserByLogin($login)
    {
        try {
            $user = $this->getUserByLogin($login);

            if (!$user || !count($user)) {
                throw new UsernameNotFoundException(
                    sprintf('Username "%s" does not exist.', $login)
                );
            }

            $roles = $this->getUserRoles($user['id']);

            if (!$roles || !count($roles)) {
                throw new UsernameNotFoundException(
                    sprintf('Username "%s" does not exist.', $login)
                );
            }

            return [
                'login' => $user['login'],
                'password' => $user['password'],
                'roles' => $roles,
            ];
        } catch (DBALException $exception) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $login)
            );
        } catch (UsernameNotFoundException $exception) {
            throw $exception;
        }
    }

    /**
     * Gets user data by login.
     *
     * @param string $login User login
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function getUserByLogin($login)
    {
        try {
            $queryBuilder = $this->db->createQueryBuilder();
            $queryBuilder->select('u.id', 'u.login', 'u.password')
                         ->from('si_users', 'u')
                         ->where('u.login = :login')
                         ->setParameter(':login', $login, \PDO::PARAM_STR);

            return $queryBuilder->execute()->fetch();
        } catch (DBALException $exception) {
            return [];
        }
    }

    /**
     * Gets user data by login.
     *
     * @param string $login User login
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function getUserData($login)
    {
        try {
            $queryBuilder = $this->db->createQueryBuilder();
            $queryBuilder->select('p.id', 'p.name', 'p.surname', 'p.email', 'p.user_id')
                         ->from('si_profiles', 'p')
                         ->innerJoin('p', 'si_users', 'u', 'u.id = p.user_id')
                         ->where('u.login = :login')
                         ->setParameter(':login', $login, \PDO::PARAM_STR);

            return $queryBuilder->execute()->fetch();
        } catch (DBALException $exception) {
            return [];
        }
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
        $queryBuilder = $this->db->createQueryBuilder();

        $result = $queryBuilder->select('p.id', 'u.login', 'u.password', 'p.name', 'p.surname', 'p.email', 'p.user_id')
                      ->from('si_users', 'u')
                      ->join('u', 'si_profiles', 'p', 'u.id = p.user_id')
                      ->where('u.id = :id')
                     ->setParameter(':id', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }

    /**
     * Gets user roles by User ID.
     *
     * @param integer $userId User ID
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function getUserRoles($userId)
    {
        $roles = [];

        try {
            $queryBuilder = $this->db->createQueryBuilder();
            $queryBuilder->select('r.name')
                ->from('si_users', 'u')
                ->innerJoin('u', 'si_roles', 'r', 'u.role_id = r.id')
                ->where('u.id = :id')
                ->setParameter(':id', $userId, \PDO::PARAM_INT);
            $result = $queryBuilder->execute()->fetchAll();

            if ($result) {
                $roles = array_column($result, 'name');
            }

            return $roles;
        } catch (DBALException $exception) {
            return $roles;
        }
    }

    /**
     * Find for uniqueness.
     *
     * @param string          $login Element login
     * @param int|string|null $id    Element id
     *
     * @return array Result
     */
    public function uniqueLogin($login, $id = null)
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder->select('login', 'id')
            ->from('si_users', 'u')
            ->where('u.login = :login')
            ->setParameter(':login', $login, \PDO::PARAM_STR);
        if ($id) {
            $queryBuilder->andWhere('u.id <> :id')
                ->setParameter(':id', $id, \PDO::PARAM_INT);
        }

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Finds if email is unique
     *
     * @param string   $email  Email
     * @param null|int $userId User id
     * @return array
     */
    public function uniqueEmial($email, $userId = null)
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder->select('email')
            ->from('si_profiles', 'p')
            ->where('p.email = :email')
            ->setParameter(':email', $email, \PDO::PARAM_STR);
        if ($userId) {
            $queryBuilder->andWhere('p.user_id <> :user_id')
                ->setParameter(':user_id', $userId, \PDO::PARAM_INT);
        }

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * SCreate User
     *
     * @param array $data
     * @throws DBALException
     */
    public function create($data)
    {
        $this->db->beginTransaction();
        try {
            $this->db->insert(
                'si_users',
                [
                    'login' => $data['login'],
                    'password' => $data['password'],
                    'role_id' => 2,
                ]
            );
            $userId = $this->db->lastInsertId();
            $this->db->insert(
                'si_profiles',
                [
                    'name' => $data['name'],
                    'surname' => $data['surname'],
                    'email' => $data['email'],
                    'user_id' => $userId,
                ]
            );
            $this->db->commit();
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Update user data
     *
     * @param array $data User data
     * @return int
     */
    public function update($data)
    {
        $user = $data;
        unset($user['id']);
        unset($user['name']);
        unset($user['surname']);
        unset($user['email']);
        unset($user['user_id']);
        $this->db->update('si_users', $user, ['id' => $data['user_id']]);
        $id = $data['id'];
        unset($data['id']);
        unset($data['user_id']);
        unset($data['login']);
        unset($data['password']);
        unset($data['role_id']);

        return $this->db->update('si_profiles', $data, ['id' => $id]);
    }

    /**
     * Update user data
     *
     * @param array $data User
     * @return int
     */
    public function saveData($data)
    {
        $id = $data['id'];
        unset($data['id']);
        unset($data['user_id']);

        return $this->db->update('si_profiles', $data, ['id' => $id]);
    }

    /**
     * Update user password
     *
     * @param string $password Password
     * @param object $user User
     * @return int
     */
    public function savePassword($password, $user)
    {
        return $this->db->update('si_users', ['password' => $password], ['login' => $user->getUsername()]);
    }

    /**
     * Get records paginated.
     *
     * @param int $page Current page number
     *
     * @return array Result
     */
    public function findAllPaginated($page = 1)
    {
        $countQueryBuilder = $this->queryAll()
            ->select('COUNT(DISTINCT u.id) AS total_results')
            ->setMaxResults(1);

        $paginator = new Paginator($this->queryAll(), $countQueryBuilder);
        $paginator->setCurrentPage($page);
        $paginator->setMaxPerPage(self::NUM_ITEMS);

        return $paginator->getCurrentPageResults();
    }

    /**
     * Remove record.
     *
     * @param array $data User data
     *
     * @return boolean Result
     */
    public function delete($data)
    {
        $userId = $data['user_id'];
        $this->db->delete('si_profiles', ['id' => $data['id']]);
        return $this->db->delete('si_users', ['id' => $userId]);
    }

    /**
     * Query all records.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('u.id', 'u.login', 'r.name')
                     ->join('u', 'si_roles', 'r', 'r.id = u.role_id')
                     ->from('si_users', 'u');
    }
}
