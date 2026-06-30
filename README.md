# Quadro de Avisos

Módulo para Zabbix 7.0 LTS para comunicação de incidentes, requisições de mudança e eventos
para as equipes diretamente dentro do Zabbix. Suporta Markdown/HTML, agendamento,
filtro por grupo de usuários e cards com temas.

---

## Funcionalidades

### Widget de Dashboard

* Widget disponível em **Dashboard > Notice Board**
* Exibe apenas avisos **ativos** dentro da janela de início/fim
* Respeita o **grupo de usuários** do usuário logado
* Modal com clique para expandir e visualizar o conteúdo completo
* CSS adaptado ao tema do usuário: escuro / azul / padrão / alto contraste

### Menu Administrativo

* Novo item de menu **Notice Board** em **Monitoring** para todos os usuários e em **Administration** para Super Admin
* Visível para **Admin** tipo 2 e **Super Admin** tipo 3 no modo administrativo
* Lista todos os avisos com filtros de busca, tipo e status
* Permite criar, editar e excluir cada card

### Formulário de Aviso

* Suporte a **Markdown e HTML** no conteúdo
* Editor com abas: Editor / Pré-visualização / Dividido
* Pré-visualização do card em tempo real antes de salvar
* Tipo de borda / severidade:

  * `info`    -- Informativo azul
  * `success` -- Resolvido verde
  * `warning` -- Atenção amarelo
  * `danger`  -- Crítico / Urgente vermelho
  * `mudanca` -- Requisição de Mudança roxo
  * `evento`  -- Evento / Manutenção ciano
* Agendamento: exibição de/até
* Seleção de grupo de usuários: único para Admin, múltiplo para Super Admin

### API REST v1.4.0

* `GET  /api/avisos`       -- Lista avisos com filtros e paginação
* `GET  /api/avisos/{id}`  -- Obtém aviso pelo ID
* `POST /api/avisos`       -- Cria aviso a partir de fonte externa
* Autenticação via Bearer Token / X-Api-Token
* Campo `source` para identificar a origem remota: Grafana, ServiceNow, etc.
* Swagger UI interativo em `api/docs.html`

---

## Estrutura de Arquivos

```text
modules/module-zbx-notice-board/
+-- manifest.json
+-- Module.php
+-- install.sql
+-- README.md
+-- actions/
|   +-- CControllerNoticeBoardCreate.php
|   +-- CControllerNoticeBoardDashboard.php
|   +-- CControllerNoticeBoardDelete.php
|   +-- CControllerNoticeBoardEdit.php
|   +-- CControllerNoticeBoardSave.php
|   +-- CControllerNoticeBoardView.php
+-- views/
|   +-- notice.board.create.php
|   +-- notice.board.dashboard.php
|   +-- notice.board.view.php
|   +-- widget.notice_board.view.php
+-- assets/
|   +-- css/notice_board.css
|   +-- js/notice_board.js
+-- locale/
|   +-- en_US/LC_MESSAGES/module.po
|   +-- pt_BR/LC_MESSAGES/module.po
+-- api/
    +-- index.php
    +-- docs.html
    +-- migrate_v1.2_to_v1.3.1.sql
    +-- migrate_v1.4.sql
```

---

## Instalação

### Instalação nova

#### 1. Banco de dados

```bash
mysql -u root -p zabbix < /path/to/modules/module-zbx-notice-board/install.sql
```

#### 2. Copiar o módulo

```bash
cp -r module-zbx-notice-board /usr/share/zabbix/modules/
chown -R www-data:www-data /usr/share/zabbix/modules/module-zbx-notice-board
```

#### 3. Habilitar no Zabbix

1. Acesse **Administration > General > Modules**
2. Localize **Notice Board** e clique em **Enable**

#### 4. Adicionar o widget ao Dashboard

1. Acesse um **Dashboard** > **Edit**
2. Clique em **Add Widget** > escolha **Notice Board**

---

## Atualização a partir do quadro-avisos

### Da versão v1.2 para v1.3.1 adiciona a coluna para_todos

```bash
mysql -u root -p zabbix < api/migrate_v1.2_to_v1.3.1.sql
```

### Da versão v1.3.1 para v1.4.0 renomeia a tabela e adiciona a coluna source

```bash
mysql -u root -p zabbix < api/migrate_v1.4.sql
```

---

## Permissões

| Ação                         | Usuário | Admin | Super Admin |
| ---------------------------- | ------- | ----- | ----------- |
| Visualizar widget            | v       | v     | v           |
| Visualizar menu Notice Board | x       | v     | v           |
| Criar / Editar aviso         | x       | v*    | v           |
| Excluir aviso                | x       | v*    | v           |
| Gerenciar qualquer grupo     | x       | x     | v           |

*Admin só pode gerenciar avisos criados por ele, dentro de seus próprios grupos.*

---

## API

Configure o token em `api/index.php`:

```php
$API_TOKENS = [
    'your-secret-token',
];
```

Abra o Swagger UI em:

```text
http://your-zabbix/zabbix/modules/module-zbx-notice-board/api/docs.html
```

---

## Dependências

* **marked.js** carregado via CDN para renderização de Markdown.
  Para ambientes air-gap, baixe e coloque em `assets/js/marked.min.js`
  e então atualize a URL do CDN em `assets/js/notice_board.js`.

---

## Compatibilidade

* **Zabbix:** 7.0 LTS
* **PHP:** 8.0+
* **MySQL / MariaDB:** 5.7+ / 10.3+
* **Navegadores:** Chrome 90+, Firefox 88+, Edge 90+

---

## Licença

Módulo livre — faça um fork e seja feliz

**Autor:** Rafael M. A. Leão Ereno
**Email:** [leao@leaoereno.com.br](mailto:leao@leaoereno.com.br)
**LinkedIn:** https://www.linkedin.com/in/leaoereno/
**GitHub:** https://github.com/leaoereno/module-zbx-notice-board

---

## Buy me a Coffee

Se este módulo foi útil para você ou para sua equipe, considere apoiar o desenvolvimento!

https://www.buymeacoffee.com/leaoereno

## Créditos

* **Mantenedor do fork:** Rafael M. A. Leão Ereno (MALE)
* **LinkedIn:** https://www.linkedin.com/in/leaoereno/

