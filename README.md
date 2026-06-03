# Quadro de Avisos — Módulo Zabbix 7.0 LTS

Módulo para comunicados, requisições de mudança e eventos que impactam as equipes que utilizam o Zabbix.

---

## Funcionalidades

### Área de Visualização (Dashboard Widget)
- Widget disponível em **Dashboard → Quadro de Avisos**
- Exibe apenas avisos **ativos** (dentro do período inicio/fim)
- Respeita o **usergroup** do usuário logado
- Cards com data, hora, usuário criador e tipo de borda colorida
- CSS aderente ao tema do usuário (dark/blue/default/high-contrast)

### Menu Administrativo
- Novo menu **Quadro de Avisos** abaixo de **Dados Coletados**
- Visível apenas para **Admin** (tipo 2) e **Super Admin** (tipo 3)
- Lista todos os avisos do grupo com filtros de busca, tipo e status
- Ações de criar, editar e excluir por card

### Cadastro de Avisos
- Suporte a **Markdown e HTML** no conteúdo
- Editor com abas: Editor / Pré-visualização / Dividido
- Preview em tempo real do card antes de salvar
- Escolha de **tipo de contorno** do card:
  - `info` — Informativo (azul)
  - `success` — Concluído (verde)
  - `warning` — Atenção (amarelo)
  - `danger` — Crítico/Urgente (vermelho)
  - `mudanca` — Requisição de Mudança (roxo)
  - `evento` — Evento/Manutenção (ciano)
- **Agendamento** de início e fim da exibição
- Seleção do **grupo de usuários** que verá o aviso

---

## Estrutura de arquivos

```
modules/quadro_avisos/
├── manifest.json                          # Configuração do módulo
├── Module.php                             # Bootstrap: menu, init
├── install.sql                            # Script SQL de instalação
├── actions/
│   ├── CControllerQuadroAvisosView.php    # Listagem (admin)
│   ├── CControllerQuadroAvisosCreate.php  # Formulário novo aviso
│   ├── CControllerQuadroAvisosEdit.php    # Formulário editar aviso
│   ├── CControllerQuadroAvisosSave.php    # Salvar (insert/update)
│   └── CControllerQuadroAvisosDelete.php  # Excluir aviso
├── views/
│   ├── quadro_avisos.view.php             # View: listagem
│   ├── quadro_avisos.create.php           # View: formulário
│   └── widget.quadro_avisos.view.php      # View: widget dashboard
└── assets/
    ├── css/quadro_avisos.css              # Estilos (tema-aware)
    └── js/quadro_avisos.js                # Markdown, preview, filtros
```

---

## Instalação

### 1. Banco de dados

```bash
mysql -u root -p zabbix < /path/to/modules/quadro_avisos/install.sql
```

### 2. Copiar o módulo

```bash
cp -r quadro_avisos /usr/share/zabbix/modules/
chown -R www-data:www-data /usr/share/zabbix/modules/quadro_avisos
```

O caminho pode variar conforme sua instalação:
- Debian/Ubuntu: `/usr/share/zabbix/modules/`
- RHEL/Rocky: `/usr/share/zabbix/modules/`
- Docker (imagem oficial): `/var/www/html/modules/`

### 3. Ativar no Zabbix

1. Acesse **Administração → Geral → Módulos**
2. Localize **Quadro de Avisos** na lista
3. Clique em **Habilitar**

### 4. Adicionar widget ao Dashboard

1. Vá ao **Dashboard** desejado → **Editar**
2. Clique em **Adicionar Widget**
3. Escolha **Quadro de Avisos**
4. Salve e feche o modo de edição

> O widget exibe automaticamente apenas avisos ativos do grupo do usuário logado.

---

## Permissões

| Ação                            | User | Admin | Super Admin |
|---------------------------------|------|-------|-------------|
| Ver widget no Dashboard         | ✅    | ✅     | ✅           |
| Ver menu Quadro de Avisos       | ❌    | ✅     | ✅           |
| Criar / Editar aviso            | ❌    | ✅ *   | ✅           |
| Excluir aviso                   | ❌    | ✅ *   | ✅           |
| Gerenciar qualquer grupo        | ❌    | ❌     | ✅           |

*\* Admin pode gerenciar apenas avisos do próprio usergroup.*

---

## Dependências JavaScript

O módulo carrega **marked.js** via CDN para renderizar Markdown:
```
https://cdn.jsdelivr.net/npm/marked/marked.min.js
```

Se o servidor não tiver acesso à internet, baixe o arquivo e coloque em:
```
modules/quadro_avisos/assets/js/marked.min.js
```

E altere em `quadro_avisos.js` a linha de carregamento para o caminho local.

---

## Compatibilidade

- **Zabbix:** 7.0 LTS
- **PHP:** 8.0+
- **MySQL/MariaDB:** 5.7+ / 10.3+
- **Navegadores:** Chrome 90+, Firefox 88+, Edge 90+

---

## Registro do Widget no manifest.json (opcional)

Para habilitar o widget no dashboard, adicione ao `manifest.json`:

```json
"widgets": {
    "quadro_avisos": {
        "name": "Quadro de Avisos",
        "description": "Exibe avisos ativos para o grupo do usuário logado.",
        "class": "WidgetQuadroAvisos",
        "js_class": "CWidgetQuadroAvisos",
        "form": null,
        "view": "widget.quadro_avisos.view",
        "size": {"width": 4, "height": 4},
        "min_user_type": 1
    }
}
```

> Nota: A API de widgets do Zabbix 7.0 pode exigir uma classe PHP adicional  
> em `includes/classes/widget/`. Consulte a documentação oficial para detalhes.
