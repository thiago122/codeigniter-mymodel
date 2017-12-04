<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Model extends CI_Model
{

    public $table = '';
    public $primaryKey = '';

    public $has_one = [];
    public $has_many = [];
    public $belongs_to = [];
    public $belongs_to_many = [];

    public $order_col_fields = [];
    public $order_by_parm = 'order_by';
    public $order_col_param = 'order';

    private $redirectOnNotFound = '';
    private $flashMessageOnNotFound = '';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Retorna todos os registros da tabela
     * 
     * @return object resultado da busca
     */
    public function all()
    {
        return $this->db->get($this->table)->result();
    }

    public function allAsSingleArray($fieldKey, $fieldValue)
    {
        $result = $this->all();

        if( !$result ){
            return [];
        }

        $vet = array();

        $result = (json_decode(json_encode($result), true));

        if( ! empty($result) ){
            foreach($result as $item){
                $vet[$item[$fieldKey]] = $item[$fieldValue];
            }
        }

        return $vet;

    }

    /**
     * Procura registros na tabela com base na chave primária
     * 
     * @param  mixed String ou array
     * @return object resultado da busca
     */
    public function find($primaryKeys = null)
    {

        if(!is_array($primaryKeys)){
            $temp = $primaryKeys;
            $primaryKeys = [];
            $primaryKeys[] = $temp;
        }

        $this->db->where_in($this->table.'.'.$this->primaryKey,  $primaryKeys);

        if(count($primaryKeys) > 1){
            $result = $this->db->get($this->table)->result();
        }else{
            $result = $this->db->get($this->table)->row();   
        }
        
        if ($result) {
            return $result;
        }

        if ($this->flashMessageOnNotFound) {
            $this->session->set_flashdata('msg', [['type' => 'warning', 'message' => $this->flashMessageOnNotFound]]);
        }

        if ($this->redirectOnNotFound) {
            redirect($this->redirectOnNotFound);
        }
    }

    /**
     * Acrescenta ao query builder a cláusula where
     * @param  string campo do tabela
     * @param  string valor a ser pesquisado
     * @return o próprio objeto
     */
    public function where($field, $value)
    {
        $this->db->where($field, $value);

        return $this;
    }

    /**
     * Acrescenta ao query builder a cláusula like
     * @param  string campo do tabela
     * @param  string valor a ser pesquisado
     * @return o próprio objeto
     */
    public function like($field, $value)
    {
        $this->db->like($field, $value);

        return $this;
    }

    /**
     * Acrescenta ao query builder a cláusula order
     * @param  string campo do tabela
     * @param  string ASC ou DESC
     * @return o próprio objeto
     */
    public function order($field, $value)
    {
        $this->db->order_by($field, $value);

        return $this;
    }

    /**
     * String com os campos que deseja retornar
     * @param  string campos da tabela
     * 
     * @return o próprio objeto
     */
    public function fields($fields)
    {
        $this->db->select($fields);

        return $this;
    }

    /**
     * Conta os resultados da query
     * @return INT total de resultados da query
     */
    public function count()
    {
        return $this->db->count_all_results($this->table);
    }

    /**
     * Acrescenta ao query builder a cláusula limit
     * @param  string|int  quantidade de registro
     * @param  string      apartir de que posição
     * @return o próprio objeto
     */
    public function limit($limit = null, $offset = null)
    {
        if ($limit) {
            $this->db->limit($limit, $offset);
        }

        return $this;
    }

    /**
     * Salva um registro
     * Se não houver o segundo parâmetro($primaryKey) realiza um insert
     * Se houver o segundo parâmetro($primaryKey) realiza um update
     * 
     * @param  array         chave => valor representando o campo do banco e o valor a ser inserido
     * @param  string|array  chave que identifica um registo na tabela
     * @return boolean
     */
    public function save($dados = null, $primaryKey = null)
    {
        if ($dados) {
            if ($primaryKey) {
                $this->db->where($this->primaryKey, $primaryKey);
                if ($this->db->update($this->table, $dados)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                if ($this->db->insert($this->table, $dados)) {
                    return $this->db->insert_id();
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * Remove um registro da tabela tendo como base o id
     * @param  string|int id do registro a ser removido
     * @return booblean
     */
    public function delete($pk = null)
    {
        if ($pk) {
            $this->db->where($this->primaryKey, $pk);
            $this->db->delete($this->table);
            if ($this->db->affected_rows() > 0) {
                return true;
            }

            return false;
        }

        return false;
    }

    /* ------------------------------------------------------ */
    /* RELACIONAMENTOS */
    /* ------------------------------------------------------ */

    /**
     * Retorna o regitro relacionado a relação um-para-um reclarada no model
     * @param  string       nome da relação
     * @param  string|int   id do registro ao qual a relação se refere( Um post possui um autor - então será o id do post )
     * @return boolean      registro da tabela
     */
    public function hasOne($relationName,$pkValue){
        $relation = $this->belongs_to[$relationName];

        $model = $relation[0];
        
        $modelName = explode('/', $model);
        $modelName = end($modelName);
        $this->load->model($model);

        $tableName = $this->{$modelName}->table;
        $fk = $this->{$modelName}->primaryKey;

        $relationTable = $tableName ;
        $relationKey = $fk;

        $this->db->where($fk,$pkValue);
        return $this->db->get($tableName)->row();

    }

    /**
     * Invoca vários joins tendo como base os nomes(separados pelo caractere | ) das relações criadas no nodel
     * Então se hoverem muitas relações que podem ser usadas como joins 
     * não será será necessário realizar várias chamadas em vários join assim:
     * 
     * join('nome_da_relacao')->join('nome_da_relacao')->join('nome_da_relacao')
     *
     * Usa-se assim:
     * joinMany('nome_da_relacao|nome_da_relacao|nome_da_relacao')
     * 
     * @param  string  nome da relação configurada no model
     * @return o próprio objeto
     */
    public function joinMany($relations)
    {
        $relations = explode('|', $relations);

        foreach ($relations as $relation) {
            $this->join($relation);
        }

        return $this;
    }

    /**
     * JOIN
     *
     * Gera o join tendo como base uma relação um-para-um
     * join('nome_da_relacao')
     * join('nome_da_relacao', 'LEFT')
     * 
     * @param   string  nome da relação configurada no model
     * @param   string  tipo do join LEFT, RIGHT, OUTER, INNER...
     * @return  o próprio objeto
     */
    public function join($relationName, $escape = null)
    {
        $relation = $this->belongs_to[$relationName];

        $model = $relation[0];
        
        $modelName = explode('/', $model);
        $modelName = end($modelName);
        $this->load->model($model);

        $tableName = $this->{$modelName}->table;
        $fk = $this->{$modelName}->primaryKey;

        $relationTable = $tableName ;
        $relationKey = $fk;
        $localKey = $relation[1];

        if ($escape) {
            $this->db->join($relationTable, $relationTable.'.'.$relationKey.' = '.$this->table.'.'.$localKey, $escape);

            return $this;
        }

        $this->db->join($relationTable, $relationTable.'.'.$relationKey.' = '.$this->table.'.'.$localKey);

        return $this;
    }

    /**
     * Retorna o regitro relacionado a relação um-para-muitos reclarada no model
     * @param  string       nome da relação
     * @param  string|int   id do registro ao qual a relação se refere( Um autor possui muitos posts - então será o id do autor )
     * @return boolean      registro da tabela
     */
    public function hasMany($relationName, $pkValue){
        $relation = $this->has_many[$relationName];

        $model = $relation[0];
        
        $modelName = explode('/', $model);
        $modelName = end($modelName);
        $this->load->model($model);

        $tableName = $this->{$modelName}->table;
        $fk = $this->{$modelName}->primaryKey;

        $relationTable = $tableName ;

        $localKey = $relation[1];

        $this->db->where($localKey, $pkValue);
        return $this->db->get($tableName)->result();

    }

    /**
     * Retorna o regitro relacionado a relação muitos-para-muitos reclarada no model
     * @param  string       nome da relação
     * @param  string|int   id do registro ao qual a relação se refere( Um post possui muitas categorias - então será o id do post )
     * @return boolean      registro da tabela
     * @return boolean      traz todos os registros da tabela e marca os registros que o 
     *                      relacionamento possui( Um post possui muitas categorias - então retornará todas as categorias e marcará as que o post possui )
     *                      
     */
    public function belongsToMany($relationName, $primaryKey, $returnAllAndCompareIfExist = false)
    {
        $relationsData = $this->belongs_to_many[$relationName];

        if (empty($relationsData)) {
            return [];
        }

        $model = $relationsData[0];
        $pivot = $relationsData[1];
        $pivotLeftFk = $relationsData[3];
        $fk = $relationsData[2];

        $modelName = explode('/', $model);
        $modelName = end($modelName);
        $this->load->model($model);

        $tableLeft = $this->{$modelName}->table;
        $idLeft = $this->{$modelName}->primaryKey;

        $this->db->where($pivot.'.'.$fk, $primaryKey);
        $this->db->join($pivot, $pivot.'.'.$pivotLeftFk.' = '.$tableLeft.'.'.$idLeft);

        $manyToManyResult = $this->db->get($tableLeft)->result();

        if(!$returnAllAndCompareIfExist){
            return $manyToManyResult;
        }
        
        $fullMany = $this->{$modelName}->all();
        return $this->prepareToCheck($manyToManyResult, $fullMany,  $idLeft);
  
    }

    public function attach($relationName, $idLocalValue, $pivotLeftFkValue, $deleteNotFounded = true)
    {
        if (!is_array($pivotLeftFkValue)) {
            $temp = $pivotLeftFkValue;
            $pivotLeftFkValue = [];
            $pivotLeftFkValue[] = $temp;
        }

        $relationsData = $this->belongs_to_many[$relationName];

        if (empty($relationsData)) {
            return false;
        }

        $model = $relationsData[0];
        $pivot = $relationsData[1];
        $pivotLeftFk = $relationsData[3];
        $fk = $relationsData[2];

        $updated = [];
        $inserted = [];
        $existing = [];

        $result = $this->db->where($fk, $idLocalValue)->get($pivot)->result();

        foreach ($result as $r) {
            // print_r($r);
            $existing[] = $r->{$pivotLeftFk};
        }

        foreach ($pivotLeftFkValue as $key => $val) {

            // se o valor for um array
            if (is_array($val)) {
                if (!in_array($key, $existing)) {
                    $insert = [
                        $pivotLeftFk => $key,
                        $fk => $idLocalValue,
                    ];

                    foreach ($val as $field => $value) {
                        $insert[$field] = $value;
                    }

                    $this->db->insert($pivot, $insert);
                    $inserted[] = $key;
                } else {
                    $insert = [
                        $pivotLeftFk => $key,
                        $fk => $idLocalValue,
                    ];

                    foreach ($val as $field => $value) {
                        $insert[$field] = $value;
                    }

                    $this->db->where($pivotLeftFk, $key);
                    $this->db->where($fk, $idLocalValue);
                    $this->db->update($pivot, $insert);
                    $updated[] = $key;
                }
            } else {
                if (!in_array($val, $existing)) {
                    $insert = [
                        $pivotLeftFk => $val,
                        $fk => $idLocalValue,
                    ];

                    $this->db->insert($pivot, $insert);
                    $inserted[] = $val;
                } else {
                    $updated[] = $val;
                }
            }
        }

        $toDelete = array_diff($existing, array_merge($updated, $inserted));

        if (!empty($toDelete) && $deleteNotFounded) {
            $this->db->where($fk, $idLocalValue)
                ->where_in($pivotLeftFk, $toDelete)
                ->delete($pivot);
        }
    }

    public function dettach($relationName, $idLocal, $fkValue){

        $relationsData = $this->belongs_to_many[$relationName];

        if (empty($relationsData)) {
            return false;
        }

        $model = $relationsData[0];
        $pivot = $relationsData[1];
        $pivotLeftFk = $relationsData[3];
        $fk = $relationsData[2];

        $this->db->where($fk, $idLocal)
                ->where_in($pivotLeftFk, $fkValue)
                ->delete($pivot);

    }

    /* ------------------------------------------------------ */
    /* FACILITADORES */
    /* ------------------------------------------------------ */

    public function prepareToCheck($arrayParcial, $fullArray, $fieldKey){

        $exitents = $this->ArraySingleField($arrayParcial, $fieldKey);

        foreach ($fullArray as $item) {
            if( in_array($item->{$fieldKey}, $exitents) ){
                $item->ckecked = true;
            }else{
                $item->ckecked = false;
            }
        }

        return $fullArray;
    }

    public function ArraySingleField($array, $fieldKey)
    {
        
        if( !$array ){
            return [];
        }

        $vet = array();

        $array = (json_decode(json_encode($array), true));

        foreach($array as $item){
            $vet[] = $item[$fieldKey];
        }

        return $vet;

    }

    public function orderCol()
    {
        $orderPossibilities = ['asc', 'desc'];

        $order_by = $this->input->get($this->order_by_parm, true);

        $order = $this->input->get($this->order_col_param, true);

        if (in_array($order_by, $this->order_col_fields) && in_array($order, $orderPossibilities)) {
            $this->db->order_by($order_by, $order);
        }

        return $this;
    }

    public function redirectNotFound($redirect)
    {
        $this->redirectOnNotFound = $redirect;

        return $this;
    }

    public function messageNotFound($message)
    {
        $this->flashMessageOnNotFound = $message;

        return $this;
    }

}// end class
