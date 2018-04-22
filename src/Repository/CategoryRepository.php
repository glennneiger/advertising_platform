<?php

/**
 * Category repository.
 */

namespace Repository;

use Doctrine\DBAL\Connection;
use Utils\Paginator;

/**
 * Class CategoryRepository.
 *
 * @package Repository
 */
class CategoryRepository
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
     * @param int $page Current page number
     *
     * @return array Result
     */
    public function findAllPaginated($page = 1)
    {
        $countQueryBuilder = $this->queryAll()
            ->select('COUNT(DISTINCT c.id) AS total_results')
            ->setMaxResults(1);

        $paginator = new Paginator($this->queryAll(), $countQueryBuilder);
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
     * Save record.
     *
     * @param array $tag Tag
     *
     * @return boolean Result
     */
    public function save($tag)
    {
        if (isset($tag['id']) && ctype_digit((string) $tag['id'])) {
            // update record
            $id = $tag['id'];
            unset($tag['id']);

            return $this->db->update('si_categories', $tag, ['id' => $id]);
        } else {
            // add new record
            return $this->db->insert('si_categories', $tag);
        }
    }

    /**
     * Remove record.
     *
     * @param array $category Category
     *
     * @return boolean Result
     */
    public function delete($category)
    {
        return $this->db->delete('si_categories', ['id' => $category['id']]);
    }

    /**
     * Gets choices array for form select.
     *
     * @return array
     */
    public function getChoices()
    {
        $choices = [];

        foreach ($this->findAll() as $category) {
            $choices[$category['name']] = $category['id'];
        }

        return $choices;
    }

    /**
     * Query all records.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('c.id', 'c.name')
            ->from('si_categories', 'c');
    }
}
