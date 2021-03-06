<?php
//----------------------------------------------------------
// GET

// items/page/2
// items/page/5?filter_foo=val1&filter_bar=val2...
// items/page/1?order_foo=ascending&order_bar=descending
// items/page/2?order_foo=ascending
// items/page/7?filter_favorite_color=green&order_username=ascending

// always paginate. Example response:
// {
//     "pages": 5,
//     "data": [{row1},{row2},...]
// }

//------------------------------------------------------------
// POST

// items

// return the id of the new item if successfull.  Use the same
// error response schema as for PUT.

//-----------------------------------------------------------
// PUT

// items/id

// respond with true if successfull.  Otherwise set
// http status code to 409. Error response example
// {
//     "GLOBAL": "optional global error response message",
//     "fieldName": "error message for a specific field"
// }

//------------------------------------------------------------
// DELETE

// same schema as for PUT.

ini_set('display_errors', 1);
error_reporting(E_STRICT|E_ALL);

require 'sequel.php';
require 'crud.php';

define('ROWS_PER_PAGE', 10);

class CRUDExample extends CRUD {

    private $sql;

    function __construct($fig) {
        parent::__construct(array_merge($fig, array(
            'orderableColumns' => array('id', 'textarea', 'letter')
        )));
        $this->sql = $fig['sql'];
    }

    protected function get() {
        $where = $this->buildWhereSQL();

        if(is_numeric($this->getPageNumber())) {
            $results = $this->sql->query(
                "SELECT * FROM crud " .
                $where['sql'] . ' ' .
                $this->buildOrderBySQL() . ' ' .
                'LIMIT ' . (($this->getPageNumber() - 1) * ROWS_PER_PAGE) .
                    ', ' . ROWS_PER_PAGE,
                $where['values']
            );

            $resultsArray = $results->toArray();
            foreach($resultsArray as $i => $row) {
                $resultsArray[$i]['fruit'] = explode(',', $row['fruit']);
            }

            return array(
                'pages' => ceil($results->count() / ROWS_PER_PAGE),
                // mapIDKeys not necessary in this case since the key is allready
                // named "id" (here for demonstration)
                'data' => $this->mapIDKeys($resultsArray, 'id')
                // 'data' => array()
            );
        }
        else {
            throw new Exception('Page number must be an integer');
        }
    }

    protected function put() {
        $requestData = $this->getRequestBody();
        if($requestData['letter'] === 'a') {
            http_response_code(409);
            return array(
                'letter' => 'server dont like letter a',
                'GLOBAL' => 'global error message'
            );
        }
        else {
            $requestData['fruit'] = implode(',', $requestData['fruit']);
            return $this->sql->update(
                'crud', $requestData, array('id' => $this->getID())
            );
        }
    }

    protected function post() {
        $requestData = $this->getRequestBody();
        if(isset($requestData['fruit'])) {
            $requestData['fruit'] = implode(',', $requestData['fruit']);
        }
        return $this->sql->insert('crud', $requestData);
    }

    protected function delete() {
        return $this->sql->delete('crud', array('id' => $this->getID()));
    }

    // NOTE: dont use this on public servers (sql injection)
    private function buildWhereSQL() {
        $filters = array_filter(
            $this->getFilterParameters(),
            function ($value) {
                return $value !== '';
            }
        );
        $filtersSQL = array();
        $filtersValues = array();
        foreach($filters as $name => $value) {
            switch($name) {
                case 'Maximum_Awesome':
                    $filtersSQL[] = 'awesome <= ?';
                    $filtersValues[] = $value;
                    break;
                case 'Search_Textarea':
                    $filtersSQL[] = 'textarea LIKE ?';
                    $filtersValues[] = '%' . $value . '%';
                    break;
                case 'fruit':
                    if(is_array($value)) {
                        $filtersSQL[] = 'fruit = ?';
                        $filtersValues[] = implode(',', $value);
                    }
                    else {
                        throw new Exception('Checkboxes should return an array.');
                    }
                    break;
                default:
                    throw new Exception('Invalid filter keyname: "' . $name . '".');
            }
        }

        return count($filtersSQL) > 0 ?
            array(
                'sql' => ' WHERE ' . implode(' AND ', $filtersSQL),
                'values' => $filtersValues
            ) : array('sql' => '', 'values' => array());
    }
}

$controller = new CRUDExample(array('sql' => new Sequel(new PDO(
    'mysql:dbname=crud_demo;host=localhost',
    'root',
    'P0l.ar-B3ar'
))));

echo json_encode($controller->respond());
?>
