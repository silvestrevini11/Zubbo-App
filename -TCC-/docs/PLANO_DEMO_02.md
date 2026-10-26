# Validar a consolidação e publicar no `demo.02`

## 1. Objetivo e coordenação

Retomar a branch `teste-alteracoes` a partir da consolidação já existente no commit `3fd7a49`, preservando alterações locais e conferindo diferenças antes de implementar pendências.

Os três agentes trabalharão em paralelo:

- **Agente 1 — Banco:** revisar SQL consolidados, criar testes de upgrade e atualizar a auditoria.
- **Agente 2 — Documentação:** corrigir planejamento, checkpoints, links e evidências.
- **Agente 3 — Ambiente:** validar verificadores locais, confirmar acesso ao Railway e preparar a configuração de `demo.02`.
- **Coordenador:** integrar entregas, ajustar dependências e workflows, executar validação completa, realizar commit, push e publicação.

Não refazer a consolidação já commitada. `main`, produção e demo atual serão preservadas.

## 2. Banco: revisar e comprovar os dois caminhos

### Instalação nova

Manter os três scripts atuais:

- `criacaotables.sql`: estrutura final, sem dados.
- `insertstables.sql`: oito esportes, 18 locais e associações com suas fontes.
- `querytables.sql`: consultas somente leitura, limitadas e sem informações sensíveis.

Instalar bancos novos somente com criação e inserts. Não aplicar migrations sobre a instalação nova durante o provisionamento.

### Atualização de bancos existentes

Manter as cinco migrations consolidadas e o arquivo histórico em `database/archive/pre-consolidacao/`.

Revisar se as migrations tratam estruturas parcialmente atualizadas, transformações legadas, índices, restrições e vínculos. A existência de uma tabela ou coluna não será considerada prova de que sua definição está correta.

Criar uma suíte de upgrade separada do teste de instalação nova. Usar exclusivamente bancos descartáveis, com autorização explícita e nomes de teste, cobrindo:

- Base legada com dados sintéticos representativos.
- Bases parcialmente atualizadas.
- Base atual e reexecução das cinco migrations.
- Duplicidades conflitantes, com interrupção identificável e preservação dos registros.
- Preservação de usuários, administradores, eventos, grupos, notificações e relacionamentos.
- Reexecução dos inserts, preservando campos e fontes previamente editados.
- Comparação semântica entre instalação nova e atualização legada, incluindo estrutura e catálogo esperado.

A regra de Futsal nos ginásios permanecerá nos inserts, conforme o fluxo atual. A validação de atualização que exigir o catálogo completo executará migrations e depois inserts.

### Auditoria

Atualizar `tests/audit-migrations.php` para apresentar os cinco domínios consolidados, mantendo o mapeamento histórico na documentação.

Adicionar a opção `--strict`: retornar `0` quando todas as verificações forem compatíveis, `1` diante de divergências e código diferente de zero em erro de execução. Sem essa opção, preservar o uso diagnóstico atual.

A auditoria continuará somente leitura e distinguirá compatibilidade observada de histórico de execução. Não introduzir um ledger retroativo.

## 3. Documentação, dependências e CI

### Documentação

Manter a documentação técnica consolidada no README principal. Retirar do planejamento a criação de `docs/guias/` e a movimentação dos quatro guias já removidos.

Atualizar plano, ambiente e relatório para distinguir:

- Implementação commitada.
- Arquivos locais ainda não versionados.
- Validação executada, com SHA e ambiente correspondentes.
- Pendências de homologação e publicação.

Resultados anteriores à consolidação serão identificados como evidência histórica. Eliminar checkpoints contraditórios, validar links e atualizar exemplos para execução a partir de `-TCC-`.

### Dependências

Retirar `vendor/` do versionamento e adicioná-lo ao ignore, preservando os arquivos físicos locais. A instalação no build será feita por `composer install`, usando o lock revisado.

Não incluir backups, segredos, logs ou dados locais nos commits. Novos logs locais usarão `.local/logs/`.

### Workflows

- Substituir a atualização automática com commit e push do Composer por uma rotina manual, sem escrita na branch.
- Executar instalação nova e suíte de upgrades em MySQL 8.4 com PHP 8.4.
- Incluir auditoria estrita, testes do mapa e validação de sintaxe dos novos verificadores.
- Executar `composer validate` e `composer check-platform-reqs`.
- Testar proteção HTTP com arquivos internos existentes, incluindo documentação real, além dos caminhos sensíveis atuais.
- Exigir aprovação dos workflows para o mesmo SHA que será publicado.

## 4. Ambiente e publicação no Railway

Revisar e versionar os arquivos locais de ambiente e verificação após sua validação.

Configuração pretendida:

| Item | Configuração |
|---|---|
| Environment | `demo.02` |
| Branch | `teste-alteracoes` |
| Root Directory | `/-TCC-` |
| Runtime | PHP 8.4 |
| Inicialização | `php -S 0.0.0.0:$PORT router.php` |
| Healthcheck | `/health.php` |
| Banco | MySQL 8.4, volume e credenciais próprios |
| Aplicação → banco | Rede privada |
| DBeaver | Endpoint externo, conexão “Zubbo — demo.02” |

Conferir acesso autenticado, projeto, serviços e autodeploys antes de qualquer push que possa afetar ambientes existentes.

Configurar `ZUBBO_ENV=demo`, `ZUBBO_DEMO_MODE=true`, base `/`, domínio HTTPS confirmado e referências `ZUBBO_DB_*` do novo serviço. Validar extensões exigidas pela aplicação, incluindo `fileinfo`, e requisitos do Composer.

Usar `ZUBBO_MAPBOX_PUBLIC_TOKEN`, já adotada no código. Conferir restrições de domínio e mapa no navegador. Importar somente credenciais vigentes de e-mail e VAPID; substituir credenciais revogadas ou expostas. Não copiar portas fixas, caminhos Windows ou autorizações de testes destrutivos.

Ordem da publicação:

1. Conferir e isolar os autodeploys existentes.
2. Preparar `demo.02` com autodeploy desabilitado.
3. Confirmar identidade do banco e volume novos; registrar backup inicial e validar o procedimento de restauração em banco descartável.
4. Inicializar o banco novo somente com criação e inserts.
5. Fazer commit e push da entrega revisada; aguardar os workflows do SHA escolhido.
6. Habilitar autodeploy com **Wait for CI** e publicar esse SHA.
7. Confirmar SHA implantado, logs, domínio, conectividade e persistência após reinício.

Se o acesso ao Railway estiver indisponível, concluir as etapas locais independentes e registrar a publicação como pendente. Não presumir configuração remota com base apenas nas respostas HTTP.

## 5. Critérios de conclusão e entrega

A publicação dependerá de:

- Instalação nova, upgrades e reexecução aprovados em MySQL 8.4.
- Testes PHP, segurança, Composer, mapa, SQL e HTTP aprovados.
- Links válidos e arquivos internos bloqueados por HTTP.
- Service Worker e manifesto respondendo 200.
- Cadastro, sessões, grupos, eventos, fila e notificações homologados com contas sintéticas.
- Verificador de ambiente aprovado sem `--skip-db`.
- E-mail e Web Push homologados na nova origem, incluindo recebimento e clique no aviso.
- Banco acessível pelo DBeaver e dados preservados após reinício.

Fixtures automatizadas permanecerão restritas a bancos descartáveis. A homologação publicada usará contas sintéticas, sem executar a suíte destrutiva contra `demo.02`.

Entregar relatório único com alterações, resultados por SHA, URL publicada, pendências e referências privadas dos backups. Falhas interromperão a publicação; rollback de código não será considerado reversão de migrations.

Uploads continuarão sujeitos ao armazenamento efêmero até uma futura configuração de persistência própria.

## 6. Checkpoint da retomada em 25/10/2026

- Branch conferida: `teste-alteracoes`; base da retomada: `3fd7a49` (`fix: validate inputs and consolidate database setup`). A consolidação já está commitada e não deve ser refeita.
- O README principal já concentra a documentação técnica. Os quatro guias separados foram removidos; não há criação nem migração para `docs/guias/` nesta entrega.
- No início da retomada, `docs/PLANO_DEMO_02.md`, `docs/AMBIENTE_DEMO_02.md`, `docs/RELATORIO_TESTES_2026-10-25.md`, `tests/environment-readiness.php` e `tests/railway-readonly.ps1` eram arquivos locais não versionados. Sua existência não comprovava validação ou publicação.
- As modificações físicas de `vendor/` serão preservadas ao retirar o diretório do índice Git. O build instalará as dependências pelo lock.
- Resultados anteriores à consolidação são evidência histórica, separada das validações desta retomada no [relatório único](RELATORIO_TESTES_2026-10-25.md). Um teste de arquivo modificado localmente não certifica automaticamente o SHA base.
- A configuração pretendida e os comandos de diagnóstico estão em [Ambiente demo.02](AMBIENTE_DEMO_02.md). Autenticação, projeto, serviços, autodeploys, domínio e SHA implantado precisam de evidência remota; respostas HTTP isoladas não os comprovam.
- A tentativa anterior de controle do navegador terminou antes de navegar ao Railway por falha da ferramenta em identificar a URL. Isso não comprovou falha de autenticação ou indisponibilidade do Railway. A retomada usa uma nova verificação de acesso e mantém a publicação pendente até confirmar isolamento e critérios de aprovação.

Os comandos da aplicação, Composer e testes documentados no [README principal](../../README.md#documentação-técnica) partem da pasta `-TCC-`. Não transportar configurações de teste, portas locais ou caminhos Windows para o runtime publicado.
