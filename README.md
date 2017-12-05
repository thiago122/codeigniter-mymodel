# codeigniter-my-model

Simples MY Model para o codeigniter, desenvolvido para evitar a repetição de código manter os controllers, models limpos, fazer o desenvolvimento ficar muito mais rápido.

Para ajudar a explicar vou usar um blog como exemplo:

### O básico

Model Post básico com a configuração mínima
```php

class ModelPost extends MY_model {

	public function __construct()
	{
		parent::__construct();
		$this->table 		= 'posts';
		$this->primaryKey 	= 'id_post';
	}

}

```

No controller
```php

// Listar todos
$this->ModelPost->all();

// retorna um
$this->ModelPost->find(1);

// retorna vários
$this->ModelPost->find([1,2,3]);

// Where básico
$this->ModelPost->where('author','1')->all();

// Like básico
$this->ModelPost->like('title','meu título')->all();

// Ordenação básico
$this->ModelPost->order('title','ASC')->all();

// String para o select
$this->ModelPost->fields('title')->all();

// Count
$this->ModelPost->where('author','1')->count();

// Limit
$this->ModelPost->limit($limit, $offset)->all();

// Salvar - Retorna o id
$insert = [
	'title' => 'Meu título'
	'content' => 'Meu conteúdo',
	'author_id'=> 1
];

$this->ModelPost->save($insert);

// Atualizar
$update = [
	'title' => 'Meu título'
	'content' => 'Meu conteúdo',
	'author_id'=> 1
];

$idPost = 1;
$this->ModelPost->save($update, $idPost);

// Excluir
$idPost = 1;
$this->ModelPost->delete($idPost);


```

### Relancionamentos



Model Post
```php

class ModelPost extends MY_model {

	public function __construct()
	{
		parent::__construct();
		$this->table 		= 'posts';
		$this->primaryKey 	= 'id_post';

		// -------------------------------------------------------------------------------------------------

		// Um post pertence a um autor
		//                apelido         model        fk
		$this->belongs_to['author'] = ['ModelAuthor','author_id'];
		
		// ONDE
		// apelido: nome dados ao model que se relaciona com o post 
		// model:   classe model com as configurações do autor( mais abaixo ) 
		// fk:      nome da chave que o post recebe para indicar o id do autor que publicou o post 

		// -------------------------------------------------------------------------------------------------
		// um post pertence a muitas categorias
		//                      apelido            model           tabela pivot       fk principal	 fk secundaria
		$this->belongs_to_many['categories'] = ['ModelCategory', 'posts_categories', 'post_id',     'category_id'];

		// apelido: 		nome dados ao model que se relaciona com o post 
		// model:   		classe model com as configurações do autor( mais abaixo ) 
		// tabela pivot:    tabela que comporta o relacionamento e recebe as chaves
		// fk principal:    chave que se refere ao model atual no caso o post_id
		// fk secundária    chave que se refere ao model model referenciado no caso category_id

	}


}// end class

```

Model Author
```php

	public function __construct()
	{
		parent::__construct();
		$this->table 	  = 'authors';
		$this->primaryKey = 'id_author';

		$this->has_many['posts'] = ['ModelPost','author_id'];

	}

	}// end class

```

Model Category
```php
	class ModelCategory extends MY_model {

		public function __construct()
		{
			parent::__construct();
			$this->table 	  = 'categories';
			$this->primaryKey = 'id_category';
		}

	}// end class

```
