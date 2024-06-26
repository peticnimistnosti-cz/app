<?php

namespace App\Model\Database;

use App\Model\Database\Exceptions\EntityNotFoundException;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Traversable;

/**
 * Class EntityRepository
 * @package App\Model\Database
 */
class EntityRepository
{
	protected EntityTable $table;

	protected Explorer $explorer;

    /**
     * AbstractRepository constructor.
     * @param EntityTable $table In elastic search type == table of MySQL
     * @param Explorer $explorer
     */
	public function __construct(EntityTable $table, Explorer $explorer) {
		$this->table = $table;
		$this->explorer = $explorer;
	}

	/**
	 * @return Selection
	 */
	public function table(): Selection
	{
		return $this->explorer->table($this->table->getTableName());
	}

    /**
     * @return EntityTable
     */
	public function getTable(): EntityTable{
		return $this->table;
	}

	/**
	 * @return Explorer
	 */
	public function getExplorer(): Explorer {
		return $this->explorer;
	}

	/**
	 * @param string $timeColumn
	 * @return Selection
	 */
	public function findTodayRows(string $timeColumn): Selection {
		return $this->findAll()->where($timeColumn." >= CURDATE() AND ".$timeColumn." < CURDATE() + INTERVAL 1 DAY");
	}

    /**
     * @param int $id
     * @param array|null $select
     * @return ActiveRow|null
     * @throws EntityNotFoundException
     */
	public function findById(int $id, ?array $select = null): ?ActiveRow
	{
		$table = $this->explorer->table($this->table->getTableName())->wherePrimary($id);
        $result = $select ? $table->select($select)->fetch() : $table->fetch();
        if(!$result) throw new EntityNotFoundException("Row with that ID doesn't exists.", 404);
		return $result;
	}

	/**
	 * @param string $column
	 * @param string $value
	 * @param array|null $select
	 * @param bool $includesDeleted
	 * @return Selection
	 */
	public function findByColumn(string $column, mixed $value, ?array $select = null, bool $includesDeleted = false): Selection
	{
		//if(!$select) $select = $this->getAllowedValues();
		$table = $this->explorer->table($this->table->getTableName());
		if($select) $table->select($select);
		return $table->where($column . " = ?", $value);
	}

	/**
	 * @param iterable $data
	 * @return int
	 */
	public function update(iterable $data): int {
		return $this->table()->update($data);
	}

	/**
	 * @param string $condition
	 * @param array $params
	 * @param iterable $data
	 * @return int
	 */
	public function updateByColumn(string $condition, array $params, iterable $data): int {
		return $this->table()->where($condition, ...$params)->update($data);
	}

	/**
	 * @param int|string $id
	 * @param iterable $data
	 * @return array
	 */
	public function updateById(int|string $id, iterable $data): array
	{
		return [$this->explorer->table($this->table)->wherePrimary($id)->update($data)];
	}

	/**
	 * @param array $allRows
	 * @param string $keyColumn
	 * @param string|null $valueColumn
	 * @return array
	 */
	public static function generateMap(array $allRows, string $keyColumn, ?string $valueColumn = null): array
	{
		$map = [];
		foreach ($allRows as $row) $map[$row->{$keyColumn}] = !$valueColumn ? $row : $row->{$valueColumn};
		return $map;
	}

	/**
	 * @param string|null $orderQuery
	 * @param array|null $select
	 * @param bool $includesDeleted
	 * @return Selection
	 */
	public function findAll(?string $orderQuery = null, ?array $select = null, bool $includesDeleted = false): Selection {
		//if(!$select) $select = $this->getAllowedValues();
		$table = $this->explorer->table($this->table);
		//if(!$includesPrivate && $this->hasPrivateColumns()) $table->where("private = ?", 0);
		$orderBuild = $orderQuery ? $table->order($orderQuery) : $table;
		return $orderBuild->select($select ?: "*");
	}

	/**
	 * @param iterable $data
	 * @return int|bool|ActiveRow|Selection|iterable
	 */
	public function insert(iterable $data): int|bool|ActiveRow|Selection|iterable
	{ // TODO: return type
		return $this->explorer->table($this->table)->insert($data);
	}

    /**
     * @param int|string $id
     * @return bool
     * @throws EntityNotFoundException
     */
	public function deleteById(int|string $id): bool {
        $deleted = $this->explorer->table($this->table)->wherePrimary($id)->delete();
        if(!$deleted) throw new EntityNotFoundException("Entity of " . $this->table  . " with ID " . $id . " doesn't exist");
		return true;
	}

	/**
	 * @param string $column
	 * @param $value
	 * @return int
	 */
	public function deleteByColumn(string $column, $value): int {
		return $this->explorer->table($this->table)->where($column . " = ?", $value)->delete();
	}

	/**
	 * @return int
	 */
	public function getCount(): int {
		return $this->explorer->table($this->table)->count("*");
	}
}
