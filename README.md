<h1>Módulo de Integração ERP</h1>

**Compatível com a plataforma Magento CE versão 1.6 a 1.9**

<img src="https://dl.dropboxusercontent.com/s/o1e82sr4y162lay/gamuza-erp-box.png" alt="" title="Gamuza ERP - Magento - Box" />

<h2>Instalação</h2>

*Atenção! Sempre faça um backup antes de realizar qualquer modificação! Sempre utilize o módulo em ambiente de testes primeiro!"*

**Instalar usando o modgit:**

    $ cd /path/to/magento
    $ modgit init
    $ modgit add gamuza_erp https://github.com/gamuzabrasil/gamuza_erp-magento.git

**Instalação manual dos arquivos**

Baixe a ultima versão aqui do pacote Gamuza_ERP-xxx.tbz2 e descompacte o arquivo baixado para dentro do diretório principal do Magento

Limpe todos os caches em Sistema -> Gerenciamento de Cache

<h2>Conhecendo o módulo</h2>

**1 - Configurando os parâmetros da integração no Painel Administrativo**

<img src="https://dl.dropboxusercontent.com/s/pdmo9yvtfl21w88/gamuza-erp-config-painel-admin.png" alt="" title="Gamuza ERP - Magento - Configurando os parâmetros da integração no Painel Administrativo" />

**2 - Configurando os métodos disponíveis para o perfil do usuário no Webservice**

<img src="https://dl.dropboxusercontent.com/s/rhvjoyaqsmym7ot/gamuza-erp-conf-perfil-webservice.png" alt="" title="Gamuza ERP - Magento - Configurando os métodos disponíveis para o perfil do usuário no Webservice" />

<h2>Métodos</h2>

**Obtendo informações da loja**

    $client = new SoapClient ('http://magento/api/soap?wsdl=1');
    
    $session = $client->login ('user', 'pass');
    
    $result = $client->call ($session, 'erp_info.module_version'); // Versão do módulo
    
    $result = $client->call ($session, 'erp_info.store_name'); // Nome da loja
    
    $result = $client->call ($session, 'erp_info.store_phone'); // Telefone da loja
    
    $result = $client->call ($session, 'erp_info.store_address'); // Endereço da loja
    
    $client->endSession ($session);

**Obtendo lista de websites**

    $client = new SoapClient ('http://magento/api/soap?wsdl=1');
    
    $session = $client->login ('user', 'pass');
    
    $result = $client->call ($session, 'erp_website.list', $params = null);
    
    $client->endSession ($session);

*Parâmetros:*

Ex: Filtrando listagem por data de criação e atualização, excluindo o website com o ID = 0

    $timestamp = '2016-04-30 01:12:56';
    
    $params = array ('main_table.created_at, main_table.updated_at' => array(
        array ('gt' => $timestamp),
        array ('gt' => $timestamp)
      ),
      'website_id' => array ('gt' => 0)
    );

**Obtendo lista de lojas**

    $client = new SoapClient ('http://magento/api/soap?wsdl=1');
    
    $session = $client->login ('user', 'pass');
    
    $result = $client->call ($session, 'erp_store_group.list', $params = null);
    
    $client->endSession ($session);

*Parâmetros:*

Ex: Filtrando listagem por data de criação e atualização, excluindo a loja com o ID = 0

    $timestamp = '2016-04-30 01:17:34';
    
    $params = array ('main_table.created_at, main_table.updated_at' => array(
        array ('gt' => $timestamp),
        array ('gt' => $timestamp)
      ),
      'main_table.group_id' => array ('gt' => 0),
    );

**Obtendo lista de visões**

    $client = new SoapClient ('http://magento/api/soap?wsdl=1');
    
    $session = $client->login ('user', 'pass');
    
    $result = $client->call ($session, 'erp_store.list', $params = null);
    
    $client->endSession ($session);

*Parâmetros:*

Ex: Filtrando listagem por data de criação e atualização, excluindo a visão com o ID = 0

    $timestamp = '2016-04-30 01:17:34';
    
    $params = array ('main_table.created_at, main_table.updated_at' => array(
        array ('gt' => $timestamp),
        array ('gt' => $timestamp)
      ),
      'store_id' => array ('gt' => 0)
    );

**Criando um atributo**

*Os atributos e seus respectivos campos e valores serão automaticamente criados ou atualizados.*

    $client = new SoapClient ('http://magento/api/soap?wsdl=1');
    
    $session = $client->login ('user', 'pass');
    
    $result = $client->call ($session, 'erp_attribute.create', array(array(
      array(
        'entity_type' => 'catalog_product',
        'attribute_code' => 'color',
        'scope' => 'global',
        'add_option' => array(
          array(
            'default' => 1,
            'order' => 0,
            'label' => array(
              array('store_code' => 'admin', 'value' => 'red')
            )
          ),
          array(
            'order' => 1,
            'label' => array(
              array('store_code' => 'admin', 'value' => 'green')
            )
          ),
          array(
            'order' => 2,
            'label' => array(
              array('store_code' => 'admin', 'value' => 'blue')
            )
          )
        )
      )
    )));
    
    $client->endSession ($session);

**Obtendo lista de categorias**

*Este método retorna uma listagem de categorias com todos os produtos associados, atributos vinculados e seus respectivos valores*

    $client = new SoapClient ('http://magento/api/soap?wsdl=1');
    
    $session = $client->login ('user', 'pass');
    
    $result = $client->call ($session, 'erp_category.list', $params = null);
    
    $client->endSession ($session);

*Parâmetros:*

Ex: Filtrando listagem por data de criação e atualização, ordenando por data de criação e atualização, aplicando limite e excluindo categoria com o ID = 0

    $timestamp = '2016-04-30 19:34:31';
    $limit = 10;
    
    $filters = array(
      'entity_id' => array ('gt' => 1),
      'or' => array(
        array ('attribute' => 'created_at', 'gt' => $gmt_timestamp),
        array ('attribute' => 'updated_at', 'gt' => $gmt_timestamp)
      )
    );
    
    $params = array(
      $filters,
      'order' => array ('e.created_at ASC', 'e.updated_at ASC'),
      'limit' => $limit
    );

**Criando categorias**

*As categorias e seus respectivos campos, valores, associações e produtos vinculados serão automaticamente criados ou atualizados.*

    $client = new SoapClient ('http://magento/api/soap?wsdl=1');
    
    $session = $client->login ('user', 'pass');
    
    $result = $client->call($session, 'erp_category.create', array(array(
        array(
            'store_code' => 'default',
            'attribute_set_name' => 'Default',
            'include_in_menu' => true,
            'is_active' => true,
            'name' => 'test',
            'erp_CodigoCategoriaProduto' => 456, // Código ERP
            'parent_erp_CodigoCategoriaProduto' => 123, // Código ERP para cat. pai
            'assigned_products' => array(
                '123' => 0, // Código ERP + ordenação
                '456' => 1 // Código ERP + ordenação
            )
        ),
        array(
            'store_code' => 'default',
            'attribute_set_name' => 'Default',
            'include_in_menu' => true,
            'is_active' => true,
            'name' => 'test2',
            'erp_CodigoCategoriaProduto' => 789, // Código ERP
            'parent_erp_CodigoCategoriaProduto' => 456, // Código ERP para cat. pai
            'assigned_products' => array(
                '123' => 0, // Codigo ERP + ordenação
                '456' => 1 // Codigo ERP + ordenação
            )
        ),
    )));
    
    $client->endSession ($session);

**Obtendo lista de produtos**

*Este método retorna uma listagem de produtos com todos os websites, categorias, atributos vinculados e seus respectivos valores*

    $client = new SoapClient ('http://magento/api/soap?wsdl=1');
    
    $session = $client->login ('user', 'pass');
    
    $result = $client->call ($session, 'erp_product.list', $params = null);
    
    $client->endSession ($session);

*Parâmetros:*

Ex: Filtrando listagem por data de criação e atualização, ordenando por data de criação e atualização, e aplicando limite.

    $timestamp = '2016-04-30 01:22:53';
    $limit = 10;
    
    $filters = array(
      'or' => array(
        array ('attribute' => 'created_at', 'gt' => $gmt_timestamp),
        array ('attribute' => 'updated_at', 'gt' => $gmt_timestamp)
      )
    );
    
    $params = array(
      $filters,
      'order' => array ('e.created_at ASC', 'e.updated_at ASC'),
      'limit' => $limit
    );

**Criando produtos**

*Os produtos e seus respectivos campos, valores, imagens e associações serão automaticamente criados ou atualizados.*

    $client = new SoapClient ('http://magento/api/soap?wsdl=1');
    
    $session = $client->login ('user', 'pass');
    
    $result = $client->call ($session, 'erp_product.create', array(array(
        array(
            'erp_CodigoProduto' => 123, Codigo ERP
            'type_id' => 'configurable',
            'sku' => 'teste1',
            'name' => 'teste1',
            'price' => 199.99,
            'status' => 2,
            'website_codes' => array ('base'),
            'store_codes' => array ('default'),
            'category_codes' => array(),
        ),
        array(
            'erp_CodigoProduto' => 456, // Codigo ERP
            'type_id' => 'simple',
            'parent_sku' => 'teste1',
            'sku' => 'teste2',
            'name' => 'teste2',
            'price' => 299.99,
            'status' => 2,
            'website_codes' => array ('base'),
            'store_codes' => array ('default'),
            'category_codes' => array (),
            'color' => 'red',
            'media_gallery_upload' => true,
            'media_gallery' => array(
                'images' => array(
                    array(
                        'type' => 'thumbnail',
                        'content' => base64_encode('/home/eneias/Desktop/produto.png'),
                        'mime' => 'image/png',
                    ),
                ),
            ),
        ),
    )));

    $client->endSession ($session);

**Atualizando quantidade em estoque dos produtos**

    $client = new SoapClient ('http://magento/api/soap?wsdl=1');
    
    $session = $client->login ('user', 'pass');

    $result = $client->call($session, 'erp_stock.update', array(array(
        array(
            'erp_CodigoProduto' => 123, // Codigo ERP
            'qty' => 150,
        ),
        array(
            'erp_CodigoProduto' => 456, // Codigo ERP
            'qty' => 250,
        )
    )));

    $client->endSession ($session);

**Obtendo lista de grupos de clientes**

    $client = new SoapClient ('http://magento/api/soap?wsdl=1');
    
    $session = $client->login ('user', 'pass');
    
    $result = $client->call ($session, 'erp_customer_group.list', $params = null);
    
    $client->endSession ($session);

*Parâmetros:*

Ex: Filtrando listagem por data de criação e atualização, excluindo o grupo de cliente com o ID = 0

    $timestamp = '2016-04-30 20:31:29';
    
    $params = array ('created_at, updated_at' => array(
        array ('gt' => $timestamp),
        array ('gt' => $timestamp)
      ),
      'customer_group_id' => array ('gt' => 0)
    );

**Obtendo lista de clientes**

    $client = new SoapClient ('http://magento/api/soap?wsdl=1');
    
    $session = $client->login ('user', 'pass');
    
    $result = $client->call ($session, 'erp_customer.list', $params = null);
    
    $client->endSession ($session);

*Parâmetros:*

Ex: Filtrando listagem por data de criação e atualização, ordenando por data de criação e atualização, e aplicando limite.

    $timestamp = '2016-04-30 20:33:29';
    
    $filters = array(
        'or' => array(
            array ('attribute' => 'created_at', 'gt' => $timestamp),
            array ('attribute' => 'updated_at', 'gt' => $timestamp)
        )
    );
    
    $params = array ($filters,
        'order' => array ('e.created_at ASC', 'e.updated_at ASC'),
        'limit' => 100
    );

**Obtendo lista de endereços de clientes**

    $client = new SoapClient ('http://magento/api/soap?wsdl=1');
    
    $session = $client->login ('user', 'pass');
    
    $result = $client->call ($session, 'erp_customer_address.list', $params = null);
    
    $client->endSession ($session);

*Parâmetros:*

Ex: Filtrando listagem por data de criação e atualização, ordenando por data de criação e atualização, e aplicando limite.

    $timestamp = '2016-04-30 20:37:29';
    
    $filters = array(
        'or' => array(
            array ('attribute' => 'created_at', 'gt' => $timestamp),
            array ('attribute' => 'updated_at', 'gt' => $timestamp)
        )
    );
    
    $params = array ($filters,
        'order' => array ('e.created_at ASC', 'e.updated_at ASC'),
        'limit' => 100
    );

**Obtendo lista de pedidos**

Este método retorna uma listagem de pedidos incluindo informações sobre os itens e pagamento

*Obs: As informações de pagamento serão criptografadas utilizando a chave salva no painel administrativo.*

    $client = new SoapClient ('http://magento/api/soap?wsdl=1');
    
    $session = $client->login ('user', 'pass');
    
    $result = $client->call ($session, 'erp_order.list', $params = null);
    
    $client->endSession ($session);

*Parâmetros:*

Ex: Filtrando listagem por data de criação e atualização, ordenando por data de criação e atualização, e aplicando limite.

    $timestamp = '2016-04-30 20:50:29';
    $state = 'new';
    $status = 'pending';
    
    $filters = array (
        'state' => $state,
        'status' => $status,
        'created_at, updated_at' => array(
            array ('gt' => $timestamp),
            array ('gt' => $timestamp)
        )
    );
    
    $params = array ($filters,
        'order' => array ('main_table.created_at ASC', 'main_table.updated_at ASC'),
        'limit' => 100
    );

**Faturando pedidos**

    $client = new SoapClient ('http://magento/api/soap?wsdl=1');
    
    $session = $client->login ('user', 'pass');
    
    $result = $client->call ($session, 'erp_invoice.create', array(array(
        array(
            'invoice_order_increment_id' => 100000476,
            'invoice_id' => 123, // Codigo ERP
            'invoice_items_qty' => array(
                array ('order_item_id' => 123, 'qty' => 10),
                array ('order_item_id' => 456, 'qty' => 20),
            ),
            'invoice_comment' => 'Comentario para fatura',
            'invoice_email' => 'E-mail para envio da fatura',
            'invoice_include_comment' => true
        ),
        array(
            'invoice_order_increment_id' => 100000477,
            'invoice_id' => 456, // Codigo ERP
            'invoice_items_qty' => array(
                array ('order_item_id' => 789, 'qty' => 30),
                array ('order_item_id' => 0123, 'qty' => 40),
            ),
            'invoice_comment' => 'Comentario para fatura',
            'invoice_email' => 'E-mail para envio da fatura',
            'invoice_include_comment' => true
        )
    )));

    $client->endSession ($session);
