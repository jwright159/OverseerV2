<?php

namespace Overseer\Models\Base;

use \Exception;
use \PDO;
use Overseer\Models\Character as ChildCharacter;
use Overseer\Models\CharacterQuery as ChildCharacterQuery;
use Overseer\Models\Map\CharacterTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Base class that represents a query for the 'characters' table.
 *
 *
 *
 * @method     ChildCharacterQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildCharacterQuery orderBySpecies($order = Criteria::ASC) Order by the species column
 * @method     ChildCharacterQuery orderByName($order = Criteria::ASC) Order by the name column
 * @method     ChildCharacterQuery orderByChumhandle($order = Criteria::ASC) Order by the chumhandle column
 * @method     ChildCharacterQuery orderByUserId($order = Criteria::ASC) Order by the user_id column
 * @method     ChildCharacterQuery orderBySessionId($order = Criteria::ASC) Order by the session_id column
 *
 * @method     ChildCharacterQuery groupById() Group by the id column
 * @method     ChildCharacterQuery groupBySpecies() Group by the species column
 * @method     ChildCharacterQuery groupByName() Group by the name column
 * @method     ChildCharacterQuery groupByChumhandle() Group by the chumhandle column
 * @method     ChildCharacterQuery groupByUserId() Group by the user_id column
 * @method     ChildCharacterQuery groupBySessionId() Group by the session_id column
 *
 * @method     ChildCharacterQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildCharacterQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildCharacterQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildCharacterQuery leftJoinWith($relation) Adds a LEFT JOIN clause and with to the query
 * @method     ChildCharacterQuery rightJoinWith($relation) Adds a RIGHT JOIN clause and with to the query
 * @method     ChildCharacterQuery innerJoinWith($relation) Adds a INNER JOIN clause and with to the query
 *
 * @method     ChildCharacterQuery leftJoinOwner($relationAlias = null) Adds a LEFT JOIN clause to the query using the Owner relation
 * @method     ChildCharacterQuery rightJoinOwner($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Owner relation
 * @method     ChildCharacterQuery innerJoinOwner($relationAlias = null) Adds a INNER JOIN clause to the query using the Owner relation
 *
 * @method     ChildCharacterQuery joinWithOwner($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the Owner relation
 *
 * @method     ChildCharacterQuery leftJoinWithOwner() Adds a LEFT JOIN clause and with to the query using the Owner relation
 * @method     ChildCharacterQuery rightJoinWithOwner() Adds a RIGHT JOIN clause and with to the query using the Owner relation
 * @method     ChildCharacterQuery innerJoinWithOwner() Adds a INNER JOIN clause and with to the query using the Owner relation
 *
 * @method     ChildCharacterQuery leftJoinSession($relationAlias = null) Adds a LEFT JOIN clause to the query using the Session relation
 * @method     ChildCharacterQuery rightJoinSession($relationAlias = null) Adds a RIGHT JOIN clause to the query using the Session relation
 * @method     ChildCharacterQuery innerJoinSession($relationAlias = null) Adds a INNER JOIN clause to the query using the Session relation
 *
 * @method     ChildCharacterQuery joinWithSession($joinType = Criteria::INNER_JOIN) Adds a join clause and with to the query using the Session relation
 *
 * @method     ChildCharacterQuery leftJoinWithSession() Adds a LEFT JOIN clause and with to the query using the Session relation
 * @method     ChildCharacterQuery rightJoinWithSession() Adds a RIGHT JOIN clause and with to the query using the Session relation
 * @method     ChildCharacterQuery innerJoinWithSession() Adds a INNER JOIN clause and with to the query using the Session relation
 *
 * @method     \Overseer\Models\UserQuery|\Overseer\Models\SessionQuery endUse() Finalizes a secondary criteria and merges it with its primary Criteria
 *
 * @method     ChildCharacter findOne(ConnectionInterface $con = null) Return the first ChildCharacter matching the query
 * @method     ChildCharacter findOneOrCreate(ConnectionInterface $con = null) Return the first ChildCharacter matching the query, or a new ChildCharacter object populated from the query conditions when no match is found
 *
 * @method     ChildCharacter findOneById(int $id) Return the first ChildCharacter filtered by the id column
 * @method     ChildCharacter findOneBySpecies(int $species) Return the first ChildCharacter filtered by the species column
 * @method     ChildCharacter findOneByName(string $name) Return the first ChildCharacter filtered by the name column
 * @method     ChildCharacter findOneByChumhandle(string $chumhandle) Return the first ChildCharacter filtered by the chumhandle column
 * @method     ChildCharacter findOneByUserId(int $user_id) Return the first ChildCharacter filtered by the user_id column
 * @method     ChildCharacter findOneBySessionId(int $session_id) Return the first ChildCharacter filtered by the session_id column *

 * @method     ChildCharacter requirePk($key, ConnectionInterface $con = null) Return the ChildCharacter by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildCharacter requireOne(ConnectionInterface $con = null) Return the first ChildCharacter matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildCharacter requireOneById(int $id) Return the first ChildCharacter filtered by the id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildCharacter requireOneBySpecies(int $species) Return the first ChildCharacter filtered by the species column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildCharacter requireOneByName(string $name) Return the first ChildCharacter filtered by the name column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildCharacter requireOneByChumhandle(string $chumhandle) Return the first ChildCharacter filtered by the chumhandle column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildCharacter requireOneByUserId(int $user_id) Return the first ChildCharacter filtered by the user_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildCharacter requireOneBySessionId(int $session_id) Return the first ChildCharacter filtered by the session_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildCharacter[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildCharacter objects based on current ModelCriteria
 * @method     ChildCharacter[]|ObjectCollection findById(int $id) Return ChildCharacter objects filtered by the id column
 * @method     ChildCharacter[]|ObjectCollection findBySpecies(int $species) Return ChildCharacter objects filtered by the species column
 * @method     ChildCharacter[]|ObjectCollection findByName(string $name) Return ChildCharacter objects filtered by the name column
 * @method     ChildCharacter[]|ObjectCollection findByChumhandle(string $chumhandle) Return ChildCharacter objects filtered by the chumhandle column
 * @method     ChildCharacter[]|ObjectCollection findByUserId(int $user_id) Return ChildCharacter objects filtered by the user_id column
 * @method     ChildCharacter[]|ObjectCollection findBySessionId(int $session_id) Return ChildCharacter objects filtered by the session_id column
 * @method     ChildCharacter[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class CharacterQuery extends ModelCriteria
{
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of \Overseer\Models\Base\CharacterQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'default', $modelName = '\\Overseer\\Models\\Character', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildCharacterQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildCharacterQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildCharacterQuery) {
            return $criteria;
        }
        $query = new ChildCharacterQuery();
        if (null !== $modelAlias) {
            $query->setModelAlias($modelAlias);
        }
        if ($criteria instanceof Criteria) {
            $query->mergeWith($criteria);
        }

        return $query;
    }

    /**
     * Find object by primary key.
     * Propel uses the instance pool to skip the database if the object exists.
     * Go fast if the query is untouched.
     *
     * <code>
     * $obj  = $c->findPk(12, $con);
     * </code>
     *
     * @param mixed $key Primary key to use for the query
     * @param ConnectionInterface $con an optional connection object
     *
     * @return ChildCharacter|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(CharacterTableMap::DATABASE_NAME);
        }

        $this->basePreSelect($con);

        if (
            $this->formatter || $this->modelAlias || $this->with || $this->select
            || $this->selectColumns || $this->asColumns || $this->selectModifiers
            || $this->map || $this->having || $this->joins
        ) {
            return $this->findPkComplex($key, $con);
        }

        if ((null !== ($obj = CharacterTableMap::getInstanceFromPool(null === $key || is_scalar($key) || is_callable([$key, '__toString']) ? (string) $key : $key)))) {
            // the object is already in the instance pool
            return $obj;
        }

        return $this->findPkSimple($key, $con);
    }

    /**
     * Find object by primary key using raw SQL to go fast.
     * Bypass doSelect() and the object formatter by using generated code.
     *
     * @param     mixed $key Primary key to use for the query
     * @param     ConnectionInterface $con A connection object
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildCharacter A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT id, species, name, chumhandle, user_id, session_id FROM characters WHERE id = :p0';
        try {
            $stmt = $con->prepare($sql);
            $stmt->bindValue(':p0', $key, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', $sql), 0, $e);
        }
        $obj = null;
        if ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            /** @var ChildCharacter $obj */
            $obj = new ChildCharacter();
            $obj->hydrate($row);
            CharacterTableMap::addInstanceToPool($obj, null === $key || is_scalar($key) || is_callable([$key, '__toString']) ? (string) $key : $key);
        }
        $stmt->closeCursor();

        return $obj;
    }

    /**
     * Find object by primary key.
     *
     * @param     mixed $key Primary key to use for the query
     * @param     ConnectionInterface $con A connection object
     *
     * @return ChildCharacter|array|mixed the result, formatted by the current formatter
     */
    protected function findPkComplex($key, ConnectionInterface $con)
    {
        // As the query uses a PK condition, no limit(1) is necessary.
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $dataFetcher = $criteria
            ->filterByPrimaryKey($key)
            ->doSelect($con);

        return $criteria->getFormatter()->init($criteria)->formatOne($dataFetcher);
    }

    /**
     * Find objects by primary key
     * <code>
     * $objs = $c->findPks(array(12, 56, 832), $con);
     * </code>
     * @param     array $keys Primary keys to use for the query
     * @param     ConnectionInterface $con an optional connection object
     *
     * @return ObjectCollection|array|mixed the list of results, formatted by the current formatter
     */
    public function findPks($keys, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getReadConnection($this->getDbName());
        }
        $this->basePreSelect($con);
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $dataFetcher = $criteria
            ->filterByPrimaryKeys($keys)
            ->doSelect($con);

        return $criteria->getFormatter()->init($criteria)->format($dataFetcher);
    }

    /**
     * Filter the query by primary key
     *
     * @param     mixed $key Primary key to use for the query
     *
     * @return $this|ChildCharacterQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(CharacterTableMap::COL_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildCharacterQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(CharacterTableMap::COL_ID, $keys, Criteria::IN);
    }

    /**
     * Filter the query on the id column
     *
     * Example usage:
     * <code>
     * $query->filterById(1234); // WHERE id = 1234
     * $query->filterById(array(12, 34)); // WHERE id IN (12, 34)
     * $query->filterById(array('min' => 12)); // WHERE id > 12
     * </code>
     *
     * @param     mixed $id The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildCharacterQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(CharacterTableMap::COL_ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(CharacterTableMap::COL_ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(CharacterTableMap::COL_ID, $id, $comparison);
    }

    /**
     * Filter the query on the species column
     *
     * Example usage:
     * <code>
     * $query->filterBySpecies(1234); // WHERE species = 1234
     * $query->filterBySpecies(array(12, 34)); // WHERE species IN (12, 34)
     * $query->filterBySpecies(array('min' => 12)); // WHERE species > 12
     * </code>
     *
     * @param     mixed $species The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildCharacterQuery The current query, for fluid interface
     */
    public function filterBySpecies($species = null, $comparison = null)
    {
        if (is_array($species)) {
            $useMinMax = false;
            if (isset($species['min'])) {
                $this->addUsingAlias(CharacterTableMap::COL_SPECIES, $species['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($species['max'])) {
                $this->addUsingAlias(CharacterTableMap::COL_SPECIES, $species['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(CharacterTableMap::COL_SPECIES, $species, $comparison);
    }

    /**
     * Filter the query on the name column
     *
     * Example usage:
     * <code>
     * $query->filterByName('fooValue');   // WHERE name = 'fooValue'
     * $query->filterByName('%fooValue%', Criteria::LIKE); // WHERE name LIKE '%fooValue%'
     * </code>
     *
     * @param     string $name The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildCharacterQuery The current query, for fluid interface
     */
    public function filterByName($name = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($name)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(CharacterTableMap::COL_NAME, $name, $comparison);
    }

    /**
     * Filter the query on the chumhandle column
     *
     * Example usage:
     * <code>
     * $query->filterByChumhandle('fooValue');   // WHERE chumhandle = 'fooValue'
     * $query->filterByChumhandle('%fooValue%', Criteria::LIKE); // WHERE chumhandle LIKE '%fooValue%'
     * </code>
     *
     * @param     string $chumhandle The value to use as filter.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildCharacterQuery The current query, for fluid interface
     */
    public function filterByChumhandle($chumhandle = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($chumhandle)) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(CharacterTableMap::COL_CHUMHANDLE, $chumhandle, $comparison);
    }

    /**
     * Filter the query on the user_id column
     *
     * Example usage:
     * <code>
     * $query->filterByUserId(1234); // WHERE user_id = 1234
     * $query->filterByUserId(array(12, 34)); // WHERE user_id IN (12, 34)
     * $query->filterByUserId(array('min' => 12)); // WHERE user_id > 12
     * </code>
     *
     * @see       filterByOwner()
     *
     * @param     mixed $userId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildCharacterQuery The current query, for fluid interface
     */
    public function filterByUserId($userId = null, $comparison = null)
    {
        if (is_array($userId)) {
            $useMinMax = false;
            if (isset($userId['min'])) {
                $this->addUsingAlias(CharacterTableMap::COL_USER_ID, $userId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($userId['max'])) {
                $this->addUsingAlias(CharacterTableMap::COL_USER_ID, $userId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(CharacterTableMap::COL_USER_ID, $userId, $comparison);
    }

    /**
     * Filter the query on the session_id column
     *
     * Example usage:
     * <code>
     * $query->filterBySessionId(1234); // WHERE session_id = 1234
     * $query->filterBySessionId(array(12, 34)); // WHERE session_id IN (12, 34)
     * $query->filterBySessionId(array('min' => 12)); // WHERE session_id > 12
     * </code>
     *
     * @see       filterBySession()
     *
     * @param     mixed $sessionId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildCharacterQuery The current query, for fluid interface
     */
    public function filterBySessionId($sessionId = null, $comparison = null)
    {
        if (is_array($sessionId)) {
            $useMinMax = false;
            if (isset($sessionId['min'])) {
                $this->addUsingAlias(CharacterTableMap::COL_SESSION_ID, $sessionId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($sessionId['max'])) {
                $this->addUsingAlias(CharacterTableMap::COL_SESSION_ID, $sessionId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(CharacterTableMap::COL_SESSION_ID, $sessionId, $comparison);
    }

    /**
     * Filter the query by a related \Overseer\Models\User object
     *
     * @param \Overseer\Models\User|ObjectCollection $user The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildCharacterQuery The current query, for fluid interface
     */
    public function filterByOwner($user, $comparison = null)
    {
        if ($user instanceof \Overseer\Models\User) {
            return $this
                ->addUsingAlias(CharacterTableMap::COL_USER_ID, $user->getId(), $comparison);
        } elseif ($user instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(CharacterTableMap::COL_USER_ID, $user->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterByOwner() only accepts arguments of type \Overseer\Models\User or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the Owner relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildCharacterQuery The current query, for fluid interface
     */
    public function joinOwner($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Owner');

        // create a ModelJoin object for this join
        $join = new ModelJoin();
        $join->setJoinType($joinType);
        $join->setRelationMap($relationMap, $this->useAliasInSQL ? $this->getModelAlias() : null, $relationAlias);
        if ($previousJoin = $this->getPreviousJoin()) {
            $join->setPreviousJoin($previousJoin);
        }

        // add the ModelJoin to the current object
        if ($relationAlias) {
            $this->addAlias($relationAlias, $relationMap->getRightTable()->getName());
            $this->addJoinObject($join, $relationAlias);
        } else {
            $this->addJoinObject($join, 'Owner');
        }

        return $this;
    }

    /**
     * Use the Owner relation User object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Overseer\Models\UserQuery A secondary query class using the current class as primary query
     */
    public function useOwnerQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinOwner($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'Owner', '\Overseer\Models\UserQuery');
    }

    /**
     * Filter the query by a related \Overseer\Models\Session object
     *
     * @param \Overseer\Models\Session|ObjectCollection $session The related object(s) to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildCharacterQuery The current query, for fluid interface
     */
    public function filterBySession($session, $comparison = null)
    {
        if ($session instanceof \Overseer\Models\Session) {
            return $this
                ->addUsingAlias(CharacterTableMap::COL_SESSION_ID, $session->getId(), $comparison);
        } elseif ($session instanceof ObjectCollection) {
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }

            return $this
                ->addUsingAlias(CharacterTableMap::COL_SESSION_ID, $session->toKeyValue('PrimaryKey', 'Id'), $comparison);
        } else {
            throw new PropelException('filterBySession() only accepts arguments of type \Overseer\Models\Session or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the Session relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return $this|ChildCharacterQuery The current query, for fluid interface
     */
    public function joinSession($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('Session');

        // create a ModelJoin object for this join
        $join = new ModelJoin();
        $join->setJoinType($joinType);
        $join->setRelationMap($relationMap, $this->useAliasInSQL ? $this->getModelAlias() : null, $relationAlias);
        if ($previousJoin = $this->getPreviousJoin()) {
            $join->setPreviousJoin($previousJoin);
        }

        // add the ModelJoin to the current object
        if ($relationAlias) {
            $this->addAlias($relationAlias, $relationMap->getRightTable()->getName());
            $this->addJoinObject($join, $relationAlias);
        } else {
            $this->addJoinObject($join, 'Session');
        }

        return $this;
    }

    /**
     * Use the Session relation Session object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return \Overseer\Models\SessionQuery A secondary query class using the current class as primary query
     */
    public function useSessionQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinSession($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'Session', '\Overseer\Models\SessionQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildCharacter $character Object to remove from the list of results
     *
     * @return $this|ChildCharacterQuery The current query, for fluid interface
     */
    public function prune($character = null)
    {
        if ($character) {
            $this->addUsingAlias(CharacterTableMap::COL_ID, $character->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the characters table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(CharacterTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            CharacterTableMap::clearInstancePool();
            CharacterTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

    /**
     * Performs a DELETE on the database based on the current ModelCriteria
     *
     * @param ConnectionInterface $con the connection to use
     * @return int             The number of affected rows (if supported by underlying database driver).  This includes CASCADE-related rows
     *                         if supported by native driver or if emulated using Propel.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public function delete(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(CharacterTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(CharacterTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            CharacterTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            CharacterTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // CharacterQuery
