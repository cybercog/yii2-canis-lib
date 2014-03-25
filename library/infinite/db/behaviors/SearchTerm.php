<?php
/**
 * library/db/behaviors/SearchTerm.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db\behaviors;

use Yii;

use yii\db\Query;
use infinite\helpers\ArrayHelper;

trait SearchTerm
{
	public $searchScore;
	public static $defaultSearchParams = ['limit' => 30, 'foreignLimitPercent' => 0.3];
	public static $searchResultClass = 'infinite\\db\\behaviors\\SearchTermResult';


	public static function searchTerm($queryString, $params = [])
	{
		$params = array_merge(self::$defaultSearchParams, $params);
		$limit = $params['limit'];
		$foreignLimit = floor($limit * $params['foreignLimitPercent']);

		$localResults = $foreignResults = [];
		$searchTerms = self::prepareSearchTerms($queryString);
		if (isset($params['searchFields'])) {
			$searchFields = $params['searchFields'];
		} else {
			$searchFields = array_unique(static::searchFields());
		}
		if (empty($searchTerms) || empty($searchFields)) { return []; }
		list($localFields, $foreignFields) = self::parseSearchFields($searchFields);
		if (empty($localFields) && empty($foreignFields)) { return []; }
		foreach ($foreignFields as $field) {
			if (count($foreignResults) > $foreignLimit) {
				$foreignResults = array_slice($foreignResults, 0, $foreignLimit);
				break;
			}
			$foreignResults = self::mergeSearchResults($foreignResults, self::searchForeign($searchTerms, $field, $params));
		}

		$limit = $limit - count($foreignResults);
		if (!empty($localFields)) {
			$localQuery = static::createQuery();
			$localQuery->limit = $limit;
			self::buildQuery($localQuery, $localFields, $searchTerms);
			self::implementParams($localQuery, $params);
			$command = $localQuery->createCommand();
			$raw = $localQuery->all();
			foreach ($raw as $object) {
				$localResults[$object->primaryKey] = self::createSearchResult($object, $localFields);
			}
		}
		$results = self::mergeSearchResults($localResults, $foreignResults);
		ArrayHelper::multisort($results, ['score', 'object.descriptor', 'object.id'], [SORT_DESC, SORT_ASC, SORT_ASC], [SORT_NUMERIC, SORT_REGULAR, SORT_REGULAR]);
		return $results;
	}

	public static function implementParams($query, $params)
	{

		$modelClass = get_called_class();
		$model = new $modelClass;

		if (!isset($params['ignore'])) {
			$params['ignore'] = [];
		}

		$registryClass = Yii::$app->classes['Registry'];

		$searchModel = false;
		if (isset($params['modules']) && count($params['modules']) === 1) {
			$searchModule = $params['modules'][0];
			if (($typeItem = Yii::$app->collectors['types']->getOne($searchModule)) && ($type = $typeItem->object)) {
				$searchModel = $type->primaryModel;
			}
		}

		if (!empty($params['ignoreChildren'])) {
			foreach ($params['ignoreChildren'] as $parentId) {
				if (!($object = $registryClass::get($parentId))) { continue; }
				$params['ignore'] = array_merge($params['ignore'], $object->queryRelations('children', $searchModel)->select(['child_object_id'])->column());
			}
		}

		if (!empty($params['ignoreParents'])) {
			foreach ($params['ignoreParents'] as $childId) {
				if (!($object = $registryClass::get($childId))) { continue; }
				$params['ignore'] = array_merge($params['ignore'], $object->queryRelations('parents', $searchModel)->select(['parent_object_id'])->column());
			}
		}

		if (!empty($params['ignore'])) {
			$query->andWhere(['not in', $query->primaryAlias .'.'. self::primaryKey()[0], $params['ignore']]);
		}
	}

	public static function buildQuery($query, $fields, $searchTerms)
	{
		$buildOr = ['or'];
		foreach ($fields as $field) {
			foreach ($searchTerms as $term) {
				//$term = '%'.strtr($term, ['%'=>'\%', '_'=>'\_', '\\'=>'\\\\']).'%';
				$buildOr[] = ['like', $field, $term];
			}
		}
		$query->andWhere($buildOr);
		$orders = [];
		$weight = count($fields) * count($searchTerms);
		foreach ($fields as $field) {
			foreach ($searchTerms as $n => $term) {
				$searchTermTag = ":term".$n;
				$query->params[$searchTermTag] = strtolower($term);
				$orders[] = $weight . '*(length(' . $field . ')-length(replace(LOWER(' . $field . '),' . $searchTermTag . ',\'\')))/length(' . $searchTermTag . ')';
				$weight--;
			}
		}
		$relavance = implode('+', $orders);
		$query->select = [$query->primaryAlias . ".*", "({$relavance}) as searchScore"];
		return $query;
	}

	public static function createSearchResult($object, $fields, $score = null, $terms = null)
	{
		if (is_null($score) && !is_null($object->searchScore)) {
			$score = $object->searchScore;
		}
		if (is_null($terms)) {
			$terms = self::prepareObjectTerms($object, $fields);
		}
		return Yii::createObject(['class' => self::$searchResultClass, 'object' => $object, 'score' => $score, 'terms' => $terms]);
	}

	public static function prepareObjectTerms($object, $fields)
	{
		$terms = [];
		foreach ($fields as $field) {
			if (!empty($object->{$field})) {
				$terms[] = $object->{$field};
			}
		}
		return $terms;
	}

	public static function searchFields()
	{
		$modelClass = get_called_class();
		$model = new $modelClass;
		if (is_null($model->descriptorField)) {
			return [];
		} elseif(is_array($model->descriptorField)) {
			return $model->descriptorField;
		} else {
			return [$model->descriptorField];
		}
	}

	public static function parseSearchFields($fields)
	{
		// local, foreign
		return [$fields, []];
	}

	public static function searchForeign($terms, $field)
	{
		// should return array of [objectId => score]
		return [];
	}

	public static function mergeSearchResults($a, $b)
	{
		if (count($b) > count($a)) {
			$aa = $a;
			$a = $b;
			$b = $aa;
		}
		foreach ($b as $key => $result) {
			if (isset($a[$key])) {
				$a[$key]->mergeScore($result->score);
				$a[$key]->mergeTerms($result->terms);
			} else {
				$a[$key] = $result;
			}
		}
		return $a;
	}

	/**
	 *
	 *
	 * @param unknown $query
	 * @return unknown
	 */
	protected static function prepareSearchTerms($query) {
		if (is_array($query)) { return $query; }
		$badSearchWords = ["a", "about", "above", "above", "across", "after", "afterwards", "again", "against", "all", "almost", "alone", "along", "already", "also", "although", "always", "am", "among", "amongst", "amoungst", "amount", "an", "and", "another", "any", "anyhow", "anyone", "anything", "anyway", "anywhere", "are", "around", "as", "at", "back", "be", "became", "because", "become", "becomes", "becoming", "been", "before", "beforehand", "behind", "being", "below", "beside", "besides", "between", "beyond", "bill", "both", "bottom", "but", "by", "call", "can", "cannot", "cant", "co", "con", "could", "couldnt", "cry", "de", "describe", "detail", "do", "done", "down", "due", "during", "each", "eg", "eight", "either", "eleven", "else", "elsewhere", "empty", "enough", "etc", "even", "ever", "every", "everyone", "everything", "everywhere", "except", "few", "fifteen", "fify", "fill", "find", "fire", "first", "five", "for", "former", "formerly", "forty", "found", "four", "from", "front", "full", "further", "get", "give", "go", "had", "has", "hasnt", "have", "he", "hence", "her", "here", "hereafter", "hereby", "herein", "hereupon", "hers", "herself", "him", "himself", "his", "how", "however", "hundred", "ie", "if", "in", "inc", "indeed", "interest", "into", "is", "it", "its", "itself", "keep", "last", "latter", "latterly", "least", "less", "ltd", "made", "many", "may", "me", "meanwhile", "might", "mill", "mine", "more", "moreover", "most", "mostly", "move", "much", "must", "my", "myself", "name", "namely", "neither", "never", "nevertheless", "next", "nine", "no", "nobody", "none", "noone", "nor", "not", "nothing", "now", "nowhere", "of", "off", "often", "on", "once", "one", "only", "onto", "or", "other", "others", "otherwise", "our", "ours", "ourselves", "out", "over", "own", "part", "per", "perhaps", "please", "put", "rather", "re", "same", "see", "seem", "seemed", "seeming", "seems", "serious", "several", "she", "should", "show", "side", "since", "sincere", "six", "sixty", "so", "some", "somehow", "someone", "something", "sometime", "sometimes", "somewhere", "still", "such", "system", "take", "ten", "than", "that", "the", "their", "them", "themselves", "then", "thence", "there", "thereafter", "thereby", "therefore", "therein", "thereupon", "these", "they", "thickv", "thin", "third", "this", "those", "though", "three", "through", "throughout", "thru", "thus", "to", "together", "too", "top", "toward", "towards", "twelve", "twenty", "two", "un", "under", "until", "up", "upon", "us", "very", "via", "was", "we", "well", "were", "what", "whatever", "when", "whence", "whenever", "where", "whereafter", "whereas", "whereby", "wherein", "whereupon", "wherever", "whether", "which", "while", "whither", "who", "whoever", "whole", "whom", "whose", "why", "will", "with", "within", "without", "would", "yet", "you", "your", "yours", "yourself", "yourselves", "the"];
		$oquery = $query;
		$query = preg_replace('/[^0-9a-z\-\'\% ]/i', '', strtolower($query));
		$parts = explode(' ', $query);
		$parts = array_diff($parts, $badSearchWords);
		return $parts;
	}


}
