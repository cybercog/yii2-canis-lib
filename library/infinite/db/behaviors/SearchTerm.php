<?php
/**
 * library/db/behaviors/SearchTerm.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db\behaviors;

use yii\db\Query;

trait SearchTerm
{
	public $searchTermFields;
	public $searchScore;
	public $rowTransactionSize = 1;
	public $resultClass = 'infinite\\db\\behaviors\\SearchTermResult';

	public static function searchTerm($term, $params = [])
	{
		$results = [];
		
		return $results;
	}

	/**
	 *
	 *
	 * @param unknown $term
	 * @return unknown
	 */
	public function searchTerm2($term, $params = []) {
		throw new \Exception("Not implemented yet");
		
		$package = ['results' => [], 'total' => 0];
		$fields = [];
		$oterm = $term;
		foreach ($this->searchTermFields as $k => $v) {
			if (is_numeric($k)) {
				$fields[$v] = 't.'.$v;
			} else {
				$fields[$k] = $v;
			}
		}
		$_Owner = get_class($this->owner);
		$searchTerms = $this->_prepareSearchTerms($term);
		//modified from Yii eSearch extension (https://raw.github.com/jorgebg/yii-esearch/master/SearchAction.php)
		$criteria = $this->owner->dbCriteria;

		if (!empty($searchTerms)) {
			$query = new Query();

			$searchConditions = [];
			foreach ($fields as $field) {
				foreach ($searchTerms as $term) {
					$searchConditions[$field] = $term;

					$query->addSearchCondition($field, $term, true, 'OR');
				}
			}
			
			$query->where(['or like', $searchConditions]);

			if (isset($params['field'])) {
				foreach ($params['field'] as $field => $value) {
					$value = $this->owner->quote($value);
					$fullField = $field;
					if (!strstr($fullField, '.')) {
						$fullField = $this->owner->tableAlias.'.'.$fullField;
					}
					if (is_array($value)) {
						$null_key = $this->_findNull($value);
						if ($null_key !== false) {
							unset($value[$null_key]);
							$query->addCondition([$fullField.' IS NULL', $fullField.' IN ('.implode(',', $value).')'], 'OR');
						} else {
							$query->addCondition($fullField.' IN ('.implode(',', $value).')', RActiveRecord::LOGINFINITE_APP_AND);
						}
					} elseif (is_null($value)) {
						$query->addCondition([$fullField.' IS NULL'], RActiveRecord::LOGINFINITE_APP_AND);
					} else {
						$query->addCondition($fullField.' = '. $value.'', RActiveRecord::LOGINFINITE_APP_AND);
					}
				}
			}

			if (isset($params['notField'])) {
				foreach ($params['notField'] as $field => $value) {
					$value = $this->owner->quote($value);
					$fullField = $field;
					if (!strstr($fullField, '.')) {
						$fullField = $this->owner->tableAlias.'.'.$fullField;
					}
					if (is_array($value)) {
						$null_key = $this->_findNull($value);
						if ($null_key !== false) {
							unset($value[$null_key]);
							$query->addCondition([$fullField.' IS NOT NULL', $fullField.' NOT IN ('.implode(',', $value).')'], 'AND');
						} else {
							$query->addCondition($fullField.' NOT IN ('.implode(',', $value).')', RActiveRecord::LOGINFINITE_APP_AND);
						}
					} elseif (is_null($value)) {
						$query->addCondition([$fullField.' IS NOT NULL'], RActiveRecord::LOGINFINITE_APP_AND);
					} else {
						$query->addCondition($fullField.' != '. $value.'', RActiveRecord::LOGINFINITE_APP_AND);
					}
				}
			}

			if (!empty($params['ignore'])) {
				if (empty($params['ignore']['objects'])) {
					$params['ignore']['objects'] = [];
				}
				if (!is_array($params['ignore']['objects'])) {
					$params['ignore']['objects'] = [$params['ignore']['objects']];
				}

				if (isset($params['ignore']['parents'])) { // ignore the parents of the following objects
					if (!is_array($params['ignore']['parents'])) {
						$params['ignore']['parents'] = [$params['ignore']['parents']];
					}
					foreach ($params['ignore']['parents'] as $childId) {
						$child = Registry::getObject($childId);
						if ($child) {
							$params['ignore']['objects'] = array_unique(array_merge($params['ignore']['objects'], $child->getParentIds(get_class($this->owner))));
						}
					}
				}

				if (isset($params['ignore']['children'])) { // ignore the children of the following objects
					if (!is_array($params['ignore']['children'])) {
						$params['ignore']['children'] = [$params['ignore']['children']];
					}
					foreach ($params['ignore']['children'] as $parentId) {
						$parent = Registry::getObject($parentId);
						if ($parent) {
							$params['ignore']['objects'] = array_unique(array_merge($params['ignore']['objects'], $parent->getChildIds(get_class($this->owner))));
						}
					}
				}
				if (!empty($params['ignore']['objects'])) {
					$params['ignore']['objects'] = $this->owner->quote($params['ignore']['objects']);
					$query->addCondition($this->owner->tableAlias.'.id'.' NOT IN ('.implode(',', $params['ignore']['objects']).')', 'AND');
				}
			}

			$criteria->mergeWith($query);

			
			$orders = [];
			$weight = count($fields) * count($searchTerms);
			foreach ($fields as $field) {
				foreach ($searchTerms as $n => $term) {
					$searchTermTag = ":term".$n;
					$criteria->params[$searchTermTag] = $term;
					$orders[] = $weight . '*(length(' . $field . ')-length(replace(LOWER(' . $field . '),' . $searchTermTag . ',\'\')))/length(' . $searchTermTag . ')';
					$weight--;
				}
			}
			$relavance = implode('+', $orders);
			$criteria->select = ["t.*", "({$relavance}) as searchScore"];
			$criteria->order = $relavance . ' desc';

			$schema = $this->owner->dbConnection->schema;
			$builder = $schema->commandBuilder;
			if ($this->owner->asa('RAclBehavior')) {
				if (empty($params['mine'])) {
				} else {
					$this->owner->addCheckAccess('update', $criteria);
				}

				if (isset($params['mine'])) {
					if ($params['mine'] === true) {
						$this->owner->addCheckAccess('read', $criteria);
					} elseif ($params['mine'] === false) {
						$this->owner->addCheckNoAccess('read', $criteria);
					} else {
						$this->owner->addCheckAccess('read', $criteria);
					}
				}
			}
			$countCriteria = clone $criteria;
			$countCommand = $builder->createCountCommand($schema->getTable($this->owner->tableName()), $countCriteria);
			$package['total'] = (int)$countCommand->queryScalar();

			$package['limit'] = false;
			$package['offset'] = 0;

			if (isset($params['limit'])) {
				$criteria->limit = $package['limit'] = (int)$params['limit'];
				$criteria->offset = $package['offset'] = (int)(isset($params['offset']) ? $params['offset'] : 0);
			}

			$command = $builder->createFindCommand($schema->getTable($this->owner->tableName()), $criteria);
			
			$dataReader = $command->query();

			foreach ($dataReader as $row) {
				$newRecord = $this->owner->populateRecord($row);
				$newRecord->searchScore = $row['searchScore'];
				$package['results'][] = $newRecord;
			}

			return $package;
		}
		return [];
	}


	/**
	 *
	 *
	 * @param unknown $query
	 * @return unknown
	 */
	public function _prepareSearchTerms($query) {
		$badSearchWords = ["a", "about", "above", "above", "across", "after", "afterwards", "again", "against", "all", "almost", "alone", "along", "already", "also", "although", "always", "am", "among", "amongst", "amoungst", "amount", "an", "and", "another", "any", "anyhow", "anyone", "anything", "anyway", "anywhere", "are", "around", "as", "at", "back", "be", "became", "because", "become", "becomes", "becoming", "been", "before", "beforehand", "behind", "being", "below", "beside", "besides", "between", "beyond", "bill", "both", "bottom", "but", "by", "call", "can", "cannot", "cant", "co", "con", "could", "couldnt", "cry", "de", "describe", "detail", "do", "done", "down", "due", "during", "each", "eg", "eight", "either", "eleven", "else", "elsewhere", "empty", "enough", "etc", "even", "ever", "every", "everyone", "everything", "everywhere", "except", "few", "fifteen", "fify", "fill", "find", "fire", "first", "five", "for", "former", "formerly", "forty", "found", "four", "from", "front", "full", "further", "get", "give", "go", "had", "has", "hasnt", "have", "he", "hence", "her", "here", "hereafter", "hereby", "herein", "hereupon", "hers", "herself", "him", "himself", "his", "how", "however", "hundred", "ie", "if", "in", "inc", "indeed", "interest", "into", "is", "it", "its", "itself", "keep", "last", "latter", "latterly", "least", "less", "ltd", "made", "many", "may", "me", "meanwhile", "might", "mill", "mine", "more", "moreover", "most", "mostly", "move", "much", "must", "my", "myself", "name", "namely", "neither", "never", "nevertheless", "next", "nine", "no", "nobody", "none", "noone", "nor", "not", "nothing", "now", "nowhere", "of", "off", "often", "on", "once", "one", "only", "onto", "or", "other", "others", "otherwise", "our", "ours", "ourselves", "out", "over", "own", "part", "per", "perhaps", "please", "put", "rather", "re", "same", "see", "seem", "seemed", "seeming", "seems", "serious", "several", "she", "should", "show", "side", "since", "sincere", "six", "sixty", "so", "some", "somehow", "someone", "something", "sometime", "sometimes", "somewhere", "still", "such", "system", "take", "ten", "than", "that", "the", "their", "them", "themselves", "then", "thence", "there", "thereafter", "thereby", "therefore", "therein", "thereupon", "these", "they", "thickv", "thin", "third", "this", "those", "though", "three", "through", "throughout", "thru", "thus", "to", "together", "too", "top", "toward", "towards", "twelve", "twenty", "two", "un", "under", "until", "up", "upon", "us", "very", "via", "was", "we", "well", "were", "what", "whatever", "when", "whence", "whenever", "where", "whereafter", "whereas", "whereby", "wherein", "whereupon", "wherever", "whether", "which", "while", "whither", "who", "whoever", "whole", "whom", "whose", "why", "will", "with", "within", "without", "would", "yet", "you", "your", "yours", "yourself", "yourselves", "the"];
		$oquery = $query;
		$query = preg_replace('/[^0-9a-z\'\% ]/i', '', strtolower($query));
		$parts = explode(' ', $query);
		$parts = array_diff($parts, $badSearchWords);
		return $parts;
	}
}
